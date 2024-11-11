#!/bin/bash

set -e

read_env_variable() {
    local var_name="$1"
    local env_file="$2"
    grep -E "^\s*${var_name}=" "$env_file" | grep -v '^\s*#' | head -n1 | cut -d'=' -f2- | tr -d '"'
}

if [ "$(id -u)" -ne 0 ]; then
    echo "Please run the script as root."
    exit 1
fi

clear

ENV_FILE="/opt/marzban/.env"

if [ -f "$ENV_FILE" ]; then
    MYSQL_ROOT_PASSWORD=$(read_env_variable "MYSQL_ROOT_PASSWORD" "$ENV_FILE")
else
    MYSQL_ROOT_PASSWORD=""
fi

while [ -z "$MYSQL_ROOT_PASSWORD" ]; do
    if [ -f "$ENV_FILE" ]; then
        echo "Warning: MYSQL_ROOT_PASSWORD is empty in $ENV_FILE."
    else
        echo "Warning: .env file not found at $ENV_FILE."
    fi
    read -s -p "Enter the MySQL root password: " MYSQL_ROOT_PASSWORD
    echo
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        echo "Error: MySQL root password cannot be empty. Please try again."
    fi
done

read -p "Enter the Telegram bot token: " botToken
if [ -z "$botToken" ]; then
    echo "Error: Telegram bot token cannot be empty. Exiting."
    exit 1
fi

read -p "Enter the admin user IDs separated by commas (e.g., 123456,789012): " adminIds
if [ -z "$adminIds" ]; then
    echo "Error: Admin user IDs cannot be empty. Exiting."
    exit 1
fi

vpnDbName="marzban"

IFS=',' read -r -a adminIdsArray <<< "$adminIds"
allowed_users_formatted=$(printf ",%s" "${adminIdsArray[@]}")
allowed_users_formatted=${allowed_users_formatted:1}

if ! dpkg -l | grep -q "^ii  nginx"; then
    echo "Nginx is not installed. Proceeding with installation..."
    apt update && apt upgrade -y
    apt install software-properties-common -y
    add-apt-repository ppa:ondrej/php -y
    apt update
    apt install mysql-client-core-8.0 -y
    apt install -y nginx php php-cli php-fpm php-curl php-mbstring php-xml php-zip php-soap libssh2-1-dev libssh2-1 git wget unzip curl certbot python3-certbot-nginx
    sleep 15
    systemctl enable nginx
    systemctl start nginx
    apt-get install -y ufw
    ufw allow 80
    if systemctl is-active --quiet apache2; then
        echo "Apache is running. Stopping Apache..."
        systemctl stop apache2
        systemctl disable apache2
        if lsof -i :80 | grep -q 'apache2'; then
            echo "Killing Apache processes to free port 80..."
            kill -9 $(lsof -t -i :80)
            echo "Port 80 freed."
        else
            echo "Port 80 not used by Apache or already free."
        fi
    else
        echo "Apache is not running or installed."
    fi
    sleep 10
fi

if lsof -i :80 | grep -q 'LISTEN' && ! lsof -i :80 | grep -q 'nginx'; then
    echo "Port 80 is in use by other services:"
    lsof -i :80 | grep 'LISTEN' | grep -v 'nginx' | awk '{print "PID: " $2 ", Command: " $1}'
    echo "Please free port 80 and restart the script."
    exit 1
else
    echo "Port 80 is free or used by Nginx."
fi

read -p "Enter the domain for your server (e.g., example.com): " botDomain
read -p "Enter your server's public IP address: " serverIP

echo "Configuring Nginx to run temporarily on port 80 for SSL..."
sudo sed -i 's/listen 80 default_server;/listen 80 default_server;/' /etc/nginx/sites-available/default
sudo sed -i 's/listen \[::\]:80 default_server;/listen \[::\]:80 default_server;/' /etc/nginx/sites-available/default

sudo systemctl restart nginx

echo "Obtaining SSL certificate..."
sudo certbot certonly --nginx --agree-tos --no-eff-email --redirect --hsts --staple-ocsp --preferred-challenges http -d $botDomain --http-01-port 80

if [ ! -f "/etc/letsencrypt/live/$botDomain/fullchain.pem" ]; then
    echo "SSL certificate not issued. Check Certbot logs."
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

mysql -h 127.0.0.1 -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS marzhelp;"

mysql -h 127.0.0.1 -u root -p"$MYSQL_ROOT_PASSWORD" "$vpnDbName" <<EOF
CREATE TABLE IF NOT EXISTS user_deletions (
  user_id int DEFAULT NULL,
  admin_id int DEFAULT NULL,
  deleted_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  used_traffic bigint DEFAULT NULL,
  reseted_usage bigint DEFAULT NULL
);
EOF

if [ -d "/var/www/html/marzhelp" ]; then
    rm -rf /var/www/html/marzhelp
fi
    echo "Cloning Marzhelp repository from GitHub..."
    git clone https://github.com/ppouria/marzhelp.git /var/www/html/marzhelp

chown -R www-data:www-data /var/www/html/marzhelp/
chmod -R 755 /var/www/html/marzhelp/

cat <<EOL > /var/www/html/marzhelp/config.php
<?php
\$botToken = '$botToken';
\$apiURL = "https://api.telegram.org/bot\$botToken/";

\$allowedUsers = [$allowed_users_formatted];

\$botDbHost = '127.0.0.1';
\$botDbUser = 'root';
\$botDbPass = '$MYSQL_ROOT_PASSWORD';
\$botDbName = 'marzhelp';

\$vpnDbHost = '127.0.0.1'; 
\$vpnDbUser = 'root';
\$vpnDbPass = '$MYSQL_ROOT_PASSWORD';
\$vpnDbName = '$vpnDbName';
?>
EOL

if [ -f "/var/www/html/marzhelp/table.php" ]; then
    php /var/www/html/marzhelp/table.php
    echo "table.php executed successfully."
else
    echo "table.php not found."
fi

curl "https://api.telegram.org/bot$botToken/setWebhook?url=https://$botDomain:88/marzhelp/webhook.php&ip_address=$serverIP&max_connections=40"

systemctl restart nginx
clear
echo "Webhook set to https://$botDomain:88/marzhelp/webhook.php"
echo "Marzhelp has been successfully installed."
