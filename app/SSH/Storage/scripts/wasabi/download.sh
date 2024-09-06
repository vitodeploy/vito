#!/bin/bash

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

# Download the file from S3
echo "Downloading s3://__bucket____src__ to __dest__"
download_output=$(/usr/local/bin/aws s3 cp "s3://$BUCKET/$SRC" "$DEST" --endpoint-url="$ENDPOINT" --region "$REGION" 2>&1)
download_exit_code=$?

# Log output and exit code
echo "Download command output: $download_output"
echo "Download command exit code: $download_exit_code"

# Check if the download was successful
if [ $download_exit_code -eq 0 ]; then
    echo "Download successful"
else
    echo "Download failed"
    exit 1
fi
