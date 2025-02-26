#!/bin/bash

TAG=$1

if [ -z "$TAG" ]; then
    echo "No tag provided"
    exit 1
fi

rm -rf /tmp/vito

git clone git@github.com:vitodeploy/vito.git /tmp/vito

cd /tmp/vito || exit

docker buildx build . \
    -f docker/Dockerfile \
    -t vitodeploy/vito:"$TAG" \
    --platform linux/amd64,linux/arm64 \
    --no-cache \
    --push
