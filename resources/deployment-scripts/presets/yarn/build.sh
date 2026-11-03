YARN_VERSION=$(node -p "const manager = require('./package.json').packageManager; typeof manager === 'string' && /^yarn@\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+.*)?$/.test(manager) ? manager.slice(5).split('+')[0] : ''")
if [ -n "$YARN_VERSION" ]; then
  YARN_IGNORE_PATH=1 yarn set version "$YARN_VERSION" --yarn-path --only-if-needed
elif [ -f yarn.lock ] && grep -q '^# yarn lockfile v1' yarn.lock; then
  YARN_IGNORE_PATH=1 yarn set version 1.22.22 --yarn-path --only-if-needed
fi
case "$(yarn --version)" in
  1.*) yarn install --frozen-lockfile --production=false ;;
  *)
    if [ -f yarn.lock ]; then
      yarn install --immutable
    else
      yarn install --no-immutable
    fi
    ;;
esac
if node -e "process.exit(require('./package.json').scripts?.build ? 0 : 1)"; then
  yarn run build
fi
