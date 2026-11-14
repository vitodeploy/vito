if [ -f composer.json ]; then
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi
