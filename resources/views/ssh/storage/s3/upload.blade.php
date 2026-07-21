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

export AWS_REQUEST_CHECKSUM_CALCULATION=when_required
export AWS_RESPONSE_CHECKSUM_VALIDATION=when_required
export AWS_ACCESS_KEY_ID={{ $key }}
export AWS_SECRET_ACCESS_KEY={{ $secret }}
export AWS_DEFAULT_REGION={{ $region }}
export AWS_ENDPOINT_URL={{ $endpoint }}

aws s3 cp --no-progress "{{ $src }}" "s3://{{ $bucket }}/{{ $dest }}" &
upload_pid=$!

elapsed=0
while kill -0 "$upload_pid" 2>/dev/null; do
    sleep 1
    elapsed=$((elapsed + 1))
    if [ $((elapsed % 30)) -eq 0 ] && kill -0 "$upload_pid" 2>/dev/null; then
        echo "Upload in progress... (${elapsed}s elapsed)"
    fi
done

if wait "$upload_pid"; then
    echo "Upload successful"
else
    echo "Error: Upload failed"
    exit 1
fi
