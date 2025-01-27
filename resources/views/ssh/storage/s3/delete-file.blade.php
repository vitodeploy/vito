#!/bin/bash

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

install_aws_cli() {
    echo "Installing AWS CLI"
    ARCH=$(uname -m)
    curl "https://awscli.amazonaws.com/awscli-exe-linux-$ARCH.zip" -o "aws.zip"
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

export AWS_ACCESS_KEY_ID={{ $key }}
export AWS_SECRET_ACCESS_KEY={{ $secret }}
export AWS_DEFAULT_REGION={{ $region }}
export AWS_ENDPOINT_URL={{ $endpoint }}

aws s3 rm s3://{{ $bucket }}/{{ $src }}
