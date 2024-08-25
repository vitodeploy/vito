#!/bin/bash

# Configure AWS CLI with provided credentials
/usr/local/bin/aws configure set aws_access_key_id "__key__"
/usr/local/bin/aws configure set aws_secret_access_key "__secret__"
/usr/local/bin/aws configure set default.region "__region__"

# Use the provided endpoint in the correct format
ENDPOINT="https://s3.__region__.wasabisys.com"

# Download the file from S3
echo "Downloading s3://__bucket____src__ to __dest__"
download_output=$(/usr/local/bin/aws s3 cp "s3://__bucket____src__" "__dest__" --endpoint-url="$ENDPOINT" --region "__region__" 2>&1)
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
