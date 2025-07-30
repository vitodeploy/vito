cd $SITE_PATH

git pull origin $BRANCH

npm install --production

npm run build

sudo service supervisor restart

echo "✅ Deployment completed successfully!"
