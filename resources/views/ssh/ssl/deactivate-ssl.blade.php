sudo rm -f {!! $basePath !!}/cert.pem {!! $basePath !!}/ca.pem && \
@if($passphrase)
sudo openssl rsa -aes256 -in {!! $pkPath !!} -out {!! $encryptedPath !!} -passout pass:{!! escapeshellarg($passphrase) !!} && \
sudo mv {!! $encryptedPath !!} {!! $pkPath !!} && \
sudo chmod 600 {!! $pkPath !!} && \
@endif
echo "SSL DEACTIVATED SUCCESSFULLY"
