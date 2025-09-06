# post-update script is here to cover extra commands in case of an update requires it.
echo "Running post-update script..."

echo "Removing legacy plugins..."
rm -rf storage/plugins/*/
