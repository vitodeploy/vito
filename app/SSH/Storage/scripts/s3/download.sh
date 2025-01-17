#!/bin/bash

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

install_aws_cli() {
    echo "Installing AWS CLI"
    curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "aws.zip"
    unzip -q aws.zip
    sudo ./aws/install --bin-dir /usr/local/bin --install-dir /usr/local/aws-cli --update
    rm -rf aws.zip aws
}

if ! command_exists aws; then
    install_aws_cli
fi

if ! command_exists aws; then
    echo "Error: AWS CLI installation failed"
    exit 1
fi

export AWS_ACCESS_KEY_ID=__key__
export AWS_SECRET_ACCESS_KEY=__secret__
export AWS_DEFAULT_REGION=__region__
export AWS_ENDPOINT_URL=__endpoint__

if aws s3 cp s3://__bucket__/__src__ __dest__; then
    echo "Download successful"
fi
