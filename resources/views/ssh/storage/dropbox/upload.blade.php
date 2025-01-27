curl -sb --location --request POST 'https://content.dropboxapi.com/2/files/upload' \
--header 'Accept: application/json' \
--header 'Dropbox-API-Arg: {"path":"{{ $dest }}"}' \
--header 'Content-Type: text/plain; charset=dropbox-cors-hack' \
--header 'Authorization: Bearer {{ $token }}' \
--data-binary '@{{ $src }}'
