# Update

## Update VPS

If you've installed VitoDeploy on a VPS, you can update it by running the bundled update script from the
project directory as the `vito` user:

:::warning
Make sure you run this as the `vito` user. If you logged in via the `root` user then switch to it first with
`su vito`.
:::

```sh
cd /home/vito/vito
bash scripts/update.sh
```

The script discards any local changes, checks out the latest stable `4.x` tag, installs dependencies, runs
migrations, clears and rebuilds the cache, and restarts the workers.

:::info
By default the script only updates to stable releases. To update to pre-releases, pass the `--beta` or
`--alpha` flag:

```sh
bash scripts/update.sh --beta
```
:::

## Update Docker

If you've installed VitoDeploy on a docker container, You just need to pull the latest version and recreate the
container

Pull the latest version:

```sh
docker pull vitodeploy/vito:latest
```

And then recreate the container!

:::info
To get the `alpha` or `beta` releases of VitoDeploy you can use the version tags like `vitodeploy/vito:4.0.0-beta-10` or use the `4.x` tag to get the latest code on the `4.x` branch.
:::
