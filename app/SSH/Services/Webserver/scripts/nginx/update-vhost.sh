echo '__vhost__' | sudo tee /etc/nginx/sites-available/__domain__

sudo service nginx restart
