if [ -f pnpm-lock.yaml ]; then
  pnpm install --frozen-lockfile --prod=false
else
  pnpm install --prod=false
fi
pnpm run --if-present build
