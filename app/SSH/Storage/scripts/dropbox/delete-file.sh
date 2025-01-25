curl --location --request POST 'https://api.dropboxapi.com/2/files/delete_v2' \
--header 'Authorization: Bearer __token__' \
--header 'Content-Type: application/json' \
--data-raw '{
    "path": "__src__"
}'
