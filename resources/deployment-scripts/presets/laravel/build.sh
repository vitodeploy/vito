composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
if [ -f package.json ]; then
  if [ -f package-lock.json ] || [ -f npm-shrinkwrap.json ]; then
    npm ci
  else
    npm install
  fi
  npm run build --if-present
fi
