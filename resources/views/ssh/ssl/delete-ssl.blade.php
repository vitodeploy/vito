@if($isWildcard ?? false)
sudo certbot delete --cert-name {!! $sslId !!} -n 2>/dev/null || true
@endif
sudo rm -rf /etc/ssl/vito/{!! $sslId !!} && \
echo "SSL DELETED SUCCESSFULLY"
