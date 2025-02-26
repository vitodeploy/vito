#!/bin/bash

BRANCH=""
TAGS=()

# Parse arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        --branch)
            BRANCH="$2"
            shift 2
            ;;
        --tags)
            IFS=',' read -r -a TAGS <<< "$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Validate inputs
if [ -z "$BRANCH" ]; then
    echo "No branch provided. Use --branch <git_branch>"
    exit 1
fi

if [ ${#TAGS[@]} -eq 0 ]; then
    echo "No tags provided. Use --tags tag1,tag2,tag3"
    exit 1
fi

# Clone the specified branch of the repo
rm -rf /tmp/vito
git clone --branch "$BRANCH" --depth 1 git@github.com:vitodeploy/vito.git /tmp/vito
cd /tmp/vito || exit

# Prepare tag arguments for docker buildx
TAG_ARGS=()
for TAG in "${TAGS[@]}"; do
    # Trim whitespace to avoid invalid tag formatting
    TAG_CLEANED=$(echo -n "$TAG" | xargs)
    TAG_ARGS+=("-t" "vitodeploy/vito:$TAG_CLEANED")
done

# Build and push the image
docker buildx build . \
    -f docker/Dockerfile \
    "${TAG_ARGS[@]}" \
    --platform linux/amd64,linux/arm64 \
    --no-cache \
    --push
