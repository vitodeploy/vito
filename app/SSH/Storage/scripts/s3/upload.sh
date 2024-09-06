#!/bin/bash

# Check if AWS CLI is installed
if ! command -v aws &> /dev/null
then
    echo "AWS CLI is not installed. Installing..."

    # Detect system architecture
    ARCH=$(uname -m)
    if [ "$ARCH" == "x86_64" ]; then
        CLI_URL="https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip"
    elif [ "$ARCH" == "aarch64" ]; then
        CLI_URL="https://awscli.amazonaws.com/awscli-exe-linux-aarch64.zip"
    else
        echo "Unsupported architecture: $ARCH"
        exit 1
    fi

    # Download and install AWS CLI
    sudo curl "$CLI_URL" -o "awscliv2.zip"
    sudo unzip awscliv2.zip
    sudo ./aws/install --update
    sudo rm -rf awscliv2.zip aws

    echo "AWS CLI installation completed."
else
    echo "AWS CLI is already installed."
    /usr/local/bin/aws --version
fi

# Configure AWS CLI with provided credentials
/usr/local/bin/aws configure set aws_access_key_id "__key__"
/usr/local/bin/aws configure set aws_secret_access_key "__secret__"

# Use the provided endpoint in the correct format
ENDPOINT="__endpoint__"
BUCKET="__bucket__"
REGION="__region__"

# Ensure that DEST does not have a trailing slash
SRC="__src__"
DEST="__dest__"

# Upload the file
echo "Uploading __src__ to s3://$BUCKET/$DEST"
upload_output=$(/usr/local/bin/aws s3 cp "$SRC" "s3://$BUCKET/$DEST" --endpoint-url="$ENDPOINT" --region "$REGION" 2>&1)
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
