ln -sfn {{ $releasePath }} {{ $site->basePath() }}/current

echo "Version {{ basename($releasePath) }} activated! 🎉"
