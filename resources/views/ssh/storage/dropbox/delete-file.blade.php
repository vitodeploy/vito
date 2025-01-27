curl --location --request POST 'https://api.dropboxapi.com/2/files/delete_v2' \
--header 'Authorization: Bearer {{ $token }}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "path": "{{ $src }}"
}'
