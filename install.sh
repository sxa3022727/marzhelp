#!/bin/bash

if [ "$(id -u)" -ne 0 ]; then
    echo "Please run as root"
    exit
fi

clear

if dpkg -l | grep -q "^ii  nginx"; then
    echo "Nginx is already installed. Retrieving current configuration..."

    nginx_conf_file=$(nginx -t 2>&1 | grep -oP '/etc/nginx/[^:]+')
    if [ -f "$nginx_conf_file" ]; then
        root_dir=$(grep -oP 'root\s+\K[^;]+' "$nginx_conf_file" | head -1)
        port=$(grep -oP 'listen\s+\K[0-9]+' "$nginx_conf_file" | head -1)

        if [ -z "$root_dir" ]; then
            echo "Failed to retrieve the root directory from Nginx configuration."
            exit 1
        fi

        echo "Root directory for Nginx: $root_dir"
        echo "Port for Nginx: $port"

        if [ -d "$root_dir/marzhelp" ]; then
            echo "Marzhelp directory already exists in $root_dir. Pulling latest changes..."
            cd "$root_dir/marzhelp"
            git reset --hard HEAD
            git pull origin main
        else
            echo "Cloning Marzhelp repository in $root_dir..."
            git clone https://github.com/ppouria/marzhelp.git "$root_dir/marzhelp"
        fi

        sudo chown -R www-data:www-data "$root_dir/marzhelp/"
        sudo chmod -R 755 "$root_dir/marzhelp/"

        read -p "Enter the domain for your server (e.g., example.com): " botDomain
        read -p "Enter your server's public IP address: " serverIP

        curl "https://api.telegram.org/bot$botToken/setWebhook?url=https://$botDomain:$port/marzhelp/webhook.php&ip_address=$serverIP&max_connections=40"

        echo "Webhook set to https://$botDomain:$port/marzhelp/bot.php"
        echo "Your Marzhelp project has been updated successfully."
        exit 0
    else
        echo "Could not find the Nginx configuration file."
        exit 1
    fi
else
    echo "Nginx is not installed. Proceeding with installation..."

    sudo apt update && sudo apt upgrade -y
    sudo apt install software-properties-common -y
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update
    apt install mysql-client-core-8.0
    sudo apt install -y nginx php php-cli php-fpm php-curl php-mbstring php-xml php-zip php-soap libssh2-1-dev libssh2-1 git wget unzip curl certbot python3-certbot-nginx

    sleep 15

    sudo systemctl enable nginx
    sudo systemctl start nginx

    sudo apt-get install -y ufw
    sudo ufw allow 80

# Stop & disable Apache if installed
if systemctl is-active --quiet apache2; then
    echo "Apache is currently running. Stopping Apache..."
    sudo systemctl stop apache2
    sudo systemctl disable apache2

    if lsof -i :80 | grep -q 'apache2'; then
        echo "Apache was using port 80. Killing processes to free the port..."
        sudo kill -9 $(lsof -t -i :80)
        echo "Port 80 has been freed."
    else
        echo "Port 80 was not in use by Apache or already free."
    fi
else
    echo "Apache is not running or installed."
fi

sleep 10

clear

if lsof -i :80 | grep -q 'LISTEN'; then
    echo "Port 80 is currently in use by the following service(s):"
    lsof -i :80 | grep 'LISTEN' | awk '{print "Process ID (PID): " $2 ", Command: " $1}'
    echo "Please free port 80 and restart the script."
    exit 1
else
    echo "Port 80 is free."
fi

read -p "Enter the domain for your server (e.g., example.com): " botDomain
read -p "Enter your server's public IP address: " serverIP

echo "Configuring Nginx to run temporarily on port 80 for SSL..."
sudo sed -i 's/listen 80 default_server;/listen 80 default_server;/' /etc/nginx/sites-available/default
sudo sed -i 's/listen \[::\]:80 default_server;/listen \[::\]:80 default_server;/' /etc/nginx/sites-available/default

sudo systemctl restart nginx

echo "Obtaining SSL certificate..."
sudo certbot certonly --nginx --agree-tos --no-eff-email --redirect --hsts --staple-ocsp --preferred-challenges http -d $botDomain --http-01-port 80

if [ ! -f /etc/letsencrypt/live/$botDomain/fullchain.pem ]; then
    echo "SSL certificate not issued. Please check certbot logs."
    exit 1
fi

echo "Configuring Nginx to run on port 88 with SSL..."

sudo sed -i 's/listen 80 default_server;/listen 88 ssl default_server;/' /etc/nginx/sites-available/default
sudo sed -i 's/listen \[::\]:80 default_server;/listen \[::\]:88 ssl default_server;/' /etc/nginx/sites-available/default
sleep 10

php_version=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+' | head -1)

# Define required PHP packages

required_packages=(
    "php${php_version}-mysql"
    "php${php_version}-curl"
    "php${php_version}-mbstring"
    "php${php_version}-xml"
    "php${php_version}-zip"
    "php${php_version}-soap"
)
for package in "${required_packages[@]}"; do
    if dpkg -l | grep -q "^ii  $package "; then
        echo "$package is already installed."
    else
        echo "Installing $package..."
        sudo apt-get install -y $package
    fi
done

sudo sed -i "/listen \[::\]:88 ssl default_server;/a \
    ssl_certificate /etc/letsencrypt/live/$botDomain/fullchain.pem;\n\
    ssl_certificate_key /etc/letsencrypt/live/$botDomain/privkey.pem;\n\
    ssl_protocols TLSv1.2 TLSv1.3;\n\
    ssl_ciphers HIGH:!aNULL:!MD5;\n\
    location ~ \.php$ {\n\
        include snippets/fastcgi-php.conf;\n\
        fastcgi_pass unix:/var/run/php/php${php_version}-fpm.sock;\n\
    }" /etc/nginx/sites-available/default

sudo ufw allow 88
sudo systemctl restart nginx

if systemctl is-active --quiet nginx; then
    echo "Nginx is running on port 88 with SSL."
else
    echo "Nginx failed to start. Please check configuration or logs."
    exit 1
fi
sleep 10

clear

read -p "Enter the bot token: " botToken
read -p "Enter the allowed admin IDs (comma separated): " adminIds

read -p "Enter the MySQL root password for Docker: " -r dbRootPass

mysql -h 127.0.0.1 -u root -p"$dbRootPass" $vpnDbName <<EOF
CREATE TABLE IF NOT EXISTS user_deletions (
  user_id int DEFAULT NULL,
  admin_id int DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  used_traffic bigint DEFAULT NULL,
  reseted_usage bigint DEFAULT NULL
);
EOF


if [ -d "/var/www/html/marzhelp" ]; then
    echo "Marzhelp directory already exists. Pulling latest changes from GitHub..."
    cd /var/www/html/marzhelp
    git reset --hard HEAD 
    git pull origin main  
else
    echo "Cloning Marzhelp repository from GitHub..."
    git clone https://github.com/ppouria/marzhelp.git /var/www/html/marzhelp
fi

sudo chown -R www-data:www-data /var/www/html/marzhelp/
sudo chmod -R 755 /var/www/html/marzhelp/

allowed_users_formatted=$(IFS=', '; echo "${adminIds[*]}")

if [[ $dbRootPass == *\\ ]]; then
    dbRootPass="${dbRootPass}\\"
fi

cat <<EOL > /var/www/html/marzhelp/config.php
<?php
\$botToken = '$botToken';
\$apiURL = "https://api.telegram.org/bot\$botToken/";

\$allowedUsers = [$allowed_users_formatted];

\$botDbHost = '127.0.0.1';
\$botDbUser = 'root';
\$botDbPass = '$dbRootPass';
\$botDbName = 'marzhelp';

\$vpnDbHost = '127.0.0.1'; 
\$vpnDbUser = 'root';
\$vpnDbPass = '$dbRootPass';
\$vpnDbName = 'marzban';
?>
EOL

if [ -f "/var/www/html/marzhelp/table.php" ]; then
    php /var/www/html/marzhelp/table.php
    echo "table.php executed successfully."
else
    echo "table.php not found."
fi


curl "https://api.telegram.org/bot$botToken/setWebhook?url=https://$botDomain:88/marzhelp/webhook.php&ip_address=$serverIP&max_connections=40"

sudo systemctl restart nginx
clear
echo "Webhook set to https://$botDomain:88/marzhelp/bot.php"

echo "Your Marzhelp has been successfully installed."
