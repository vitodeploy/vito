set -e

cd $SITE_PATH

git pull origin $BRANCH

npm install

npm run build

sudo supervisorctl restart all

echo "✅ Deployment completed successfully!"
