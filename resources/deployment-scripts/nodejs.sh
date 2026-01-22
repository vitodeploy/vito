cd $SITE_PATH

git pull origin $BRANCH

npm ci

npm run build

sudo supervisorctl restart all

echo "✅ Deployment completed successfully!"
