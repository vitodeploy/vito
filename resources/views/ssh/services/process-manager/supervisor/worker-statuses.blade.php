output=$(sudo supervisorctl status 2>&1) || true
echo "$output"
