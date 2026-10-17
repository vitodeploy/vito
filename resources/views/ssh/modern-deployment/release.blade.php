ln -sfn {{ $releasePath }} {{ $basePath }}/current

echo "Version {{ basename($releasePath) }} activated! 🎉"
