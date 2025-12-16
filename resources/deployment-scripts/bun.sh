cd $SITE_PATH

git pull origin $BRANCH

bun install

bun --bun run build

sudo supervisorctl restart all

echo "✅ Deployment completed successfully!"
