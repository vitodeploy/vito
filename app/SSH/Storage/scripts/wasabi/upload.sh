#!/bin/bash

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null
then
    echo "AWS CLI is not installed. Installing..."

    # Install AWS CLI v2 with the --update flag
    sudo curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
    sudo unzip awscliv2.zip
    sudo ./aws/install --update
    sudo rm -rf awscliv2.zip aws

    echo "AWS CLI installation completed."
else
    echo "AWS CLI is already installed."
    aws --version
fi

# Configure AWS CLI with provided credentials
/usr/local/bin/aws configure set aws_access_key_id "__key__"
/usr/local/bin/aws configure set aws_secret_access_key "__secret__"
/usr/local/bin/aws configure set default.region "__region__"

# Construct the endpoint URL with the bucket name
ENDPOINT="https://__bucket__.s3.__region__.wasabisys.com"

# Ensure that DEST does not have a trailing slash
DEST="${DEST%/}"

# Upload the file
echo "Uploading __src__ to s3://__bucket__/$DEST"
upload_output=$(/usr/local/bin/aws s3 cp "__src__" "s3://__bucket__/$DEST" --endpoint-url="$ENDPOINT" --region "__region__" 2>&1)
upload_exit_code=$?

# Log output and exit code
echo "Upload command output: $upload_output"
echo "Upload command exit code: $upload_exit_code"

# Check if the upload was successful
if [ $upload_exit_code -eq 0 ]; then
    echo "Upload successful"
else
    echo "Upload failed"
    exit 1
fi
