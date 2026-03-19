@if($passphrase)
sudo openssl rsa -in {!! $pkPath !!} -out {!! $decryptedPath !!} -passin pass:{!! escapeshellarg($passphrase) !!} && \
sudo mv {!! $decryptedPath !!} {!! $pkPath !!} && \
sudo chmod 600 {!! $pkPath !!} && \
@endif
echo "SSL ACTIVATED SUCCESSFULLY"
