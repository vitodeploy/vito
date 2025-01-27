curl -o {{ $dest }} --location --request POST 'https://content.dropboxapi.com/2/files/download' \
--header 'Accept: application/json' \
--header 'Dropbox-API-Arg: {"path":"{{ $src }}"}' \
--header 'Authorization: Bearer {{ $token }}'
