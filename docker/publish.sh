#!/bin/bash

BRANCH=""
TAGS=()

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

if [ -z "$BRANCH" ]; then
    echo "No branch provided. Use --branch <git_branch>"
    exit 1
fi

if [ ${#TAGS[@]} -eq 0 ]; then
    echo "No tags provided. Use --tags tag1,tag2,tag3"
    exit 1
fi

WORK_DIR=$(mktemp -d) || exit 1
trap 'rm -rf "$WORK_DIR"' EXIT

if ! git clone --branch "$BRANCH" --depth 1 git@github.com:vitodeploy/vito.git "$WORK_DIR/vito"; then
    echo "Failed to clone branch $BRANCH"
    exit 1
fi
cd "$WORK_DIR/vito" || exit 1

TAG_ARGS=()
for TAG in "${TAGS[@]}"; do
    TAG_CLEANED=$(echo -n "$TAG" | xargs)
    TAG_ARGS+=("-t" "vitodeploy/vito:$TAG_CLEANED")
done

docker buildx build . \
    -f docker/Dockerfile \
    "${TAG_ARGS[@]}" \
    --platform linux/amd64,linux/arm64 \
    --no-cache \
    --push
