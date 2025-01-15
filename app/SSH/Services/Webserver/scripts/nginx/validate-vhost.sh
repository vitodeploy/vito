sudo mkdir -p /etc/nginx/templates/output

echo 'events {} http { __vhost__ }' | sudo tee /etc/nginx/templates/output/__domain__.conf

if ! sudo nginx -t -c /etc/nginx/templates/output/__domain__.conf; then
    echo "VITO_SSH_ERROR"
    exit 1
fi

sudo rm -f /etc/nginx/templates/output/__domain__.conf
