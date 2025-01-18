echo '__config__' | sudo tee /etc/php/__version__/fpm/pool.d/__user__.conf
sudo service php__version__-fpm restart
