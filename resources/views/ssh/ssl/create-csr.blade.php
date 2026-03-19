sudo openssl req -new -newkey rsa:{!! $keySize !!} {!! $passphrase ? '-passout pass:'.escapeshellarg($passphrase) : '-nodes' !!} \
  -keyout /etc/ssl/vito/{!! $sslId !!}/private.key \
  -out /etc/ssl/vito/{!! $sslId !!}/request.csr \
  -config /etc/ssl/vito/{!! $sslId !!}/openssl.cnf 2>&1 && \
echo "CSR GENERATED SUCCESSFULLY"
