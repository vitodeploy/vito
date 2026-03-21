echo "Starting Certbot Process"
sudo nohup certbot certonly --manual --preferred-challenges dns \
  --agree-tos --non-interactive --force-renewal \
  -m {!! escapeshellarg($email) !!} \
  --cert-name {!! escapeshellarg($certName) !!} \
  -d {!! escapeshellarg('*.' . $domain) !!} -d {!! escapeshellarg($domain) !!} \
  --manual-auth-hook {!! $basePath !!}/auth-hook.sh \
  --manual-cleanup-hook {!! $basePath !!}/cleanup-hook.sh \
  > {!! $basePath !!}/certbot.log 2>&1 & echo $!
