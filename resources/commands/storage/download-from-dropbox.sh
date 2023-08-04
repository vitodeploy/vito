curl -o __dest__ --location --request POST 'https://content.dropboxapi.com/2/files/download' \
--header 'Accept: application/json' \
--header 'Dropbox-API-Arg: {"path":"__src__"}' \
--header 'Authorization: Bearer __token__'
