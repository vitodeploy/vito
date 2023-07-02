sudo rm -rf __path__

sudo rm /etc/nginx/sites-available/phpmyadmin

sudo rm /etc/nginx/sites-enabled/phpmyadmin

if ! sudo service nginx restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "PHPMyAdmin deleted"
