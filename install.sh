#!/bin/bash

if [ "$(id -u)" -ne 0 ]; then
    echo "Please run as root"
    exit
fi
clear

# Check if Nginx is installed, and remove if it is
if dpkg -l | grep -q "^ii  nginx"; then
    echo "Nginx is installed. Removing Nginx..."
    sudo systemctl stop nginx
    sudo systemctl disable nginx
    sudo apt-get remove --purge nginx nginx-common nginx-full -y
    sudo apt-get autoremove -y
    sudo rm -rf /etc/nginx
    sudo rm -rf /var/log/nginx
    sudo rm -rf /var/www/html/marzhelp/*
    echo "Nginx has been removed."
fi

sudo apt update && sudo apt upgrade -y
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
apt install mysql-client-core-8.0
sudo apt install -y nginx php php-cli php-fpm php-curl php-mbstring php-xml php-zip php-soap libssh2-1-dev libssh2-1 git wget unzip curl certbot python3-certbot-nginx

sleep 15

clear

if ! systemctl list-units --type=service | grep -q "nginx.service"; then
    echo "Nginx service is not found. Reinstalling Nginx..."
    sudo apt remove --purge nginx nginx-common -y
    sudo apt install nginx -y
fi

sudo systemctl enable nginx
sudo systemctl start nginx

sudo apt-get install -y ufw
sudo ufw allow 80

# Remove Apache if installed
sudo systemctl stop apache2
sudo systemctl disable apache2
sudo apt-get remove --purge apache2 apache2-utils apache2-bin apache2.2-common -y
sudo apt-get autoremove -y
sleep 10
clear
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
read -p "Enter the bot database name (default: marzhelp): " botDbName
botDbName=${botDbName:-marzhelp}
read -p "Enter the VPN database name (default: marzban): " vpnDbName
vpnDbName=${vpnDbName:-marzban}
read -p "Enter the MySQL root password for Docker: " -r dbRootPass

mysql -h 127.0.0.1 -u root -p"$dbRootPass" -e "CREATE DATABASE IF NOT EXISTS $botDbName;"
mysql -h 127.0.0.1 -u root -p"$dbRootPass" $botDbName <<EOF
CREATE TABLE IF NOT EXISTS admin_settings (
  admin_id int NOT NULL,
  total_traffic bigint DEFAULT NULL,
  expiry_date date DEFAULT NULL,
  status varchar(50) DEFAULT 'active',
  user_limit bigint DEFAULT NULL,
  PRIMARY KEY (admin_id)
);
CREATE TABLE IF NOT EXISTS allowed_users (
  id int NOT NULL AUTO_INCREMENT,
  telegram_id bigint NOT NULL,
  username varchar(255) NOT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY telegram_id (telegram_id)
);
CREATE TABLE IF NOT EXISTS user_states (
  user_id bigint NOT NULL,
  state varchar(50) DEFAULT NULL,
  admin_id int DEFAULT NULL,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  data text,
  message_id int DEFAULT NULL,
  PRIMARY KEY (user_id)
);
CREATE TABLE IF NOT EXISTS user_temporaries (
  user_id int NOT NULL,
  user_key varchar(50) NOT NULL,
  value text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

EOF

IFS=',' read -ra ADMIN_IDS_ARRAY <<< "$adminIds"
for adminId in "${ADMIN_IDS_ARRAY[@]}"; do
  existingUser=$(mysql -h 127.0.0.1 -u root -p"$dbRootPass" $botDbName -se "SELECT COUNT(*) FROM allowed_users WHERE telegram_id = $adminId;")
  if [ "$existingUser" -eq 0 ]; then
    mysql -h 127.0.0.1 -u root -p"$dbRootPass" $botDbName -e "INSERT INTO allowed_users (telegram_id, username) VALUES ($adminId, '');"
  fi
done
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

allowed_users_formatted=$(printf "'%s'," "${adminIds[@]}")
allowed_users_formatted=${allowed_users_formatted%,} 

cat <<EOL > /var/www/html/marzhelp/config.php
<?php
\$botToken = '$botToken';
\$apiURL = "https://api.telegram.org/bot\$botToken/";

\$allowedUsers = [$allowed_users_formatted];

\$botDbHost = '127.0.0.1';
\$botDbUser = 'root';
\$botDbPass = '$dbRootPass';
\$botDbName = '$botDbName';

\$vpnDbHost = '127.0.0.1'; 
\$vpnDbUser = 'root';
\$vpnDbPass = '$dbRootPass';
\$vpnDbName = '$vpnDbName';
?>
EOL

curl "https://api.telegram.org/bot$botToken/setWebhook?url=https://$botDomain:88/marzhelp/bot.php&ip_address=$serverIP&max_connections=40"

sudo systemctl restart nginx
clear
echo "Webhook set to https://$botDomain:88/marzhelp/bot.php"

echo "Your Marzhelp has been successfully installed."
