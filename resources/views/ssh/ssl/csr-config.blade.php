[req]
default_bits = {{ $keySize }}
prompt = no
default_md = sha256
distinguished_name = dn

[dn]
CN = {{ $commonName }}
O = {{ $organization }}
@if($organizationalUnit)
OU = {{ $organizationalUnit }}
@endif
L = {{ $city }}
ST = {{ $state }}
C = {{ $country }}
@if($email)
emailAddress = {{ $email }}
@endif
