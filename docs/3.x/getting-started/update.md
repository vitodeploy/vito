# Update

## Update VPS

If you've installed VitoDeploy on a linux virtual machine, To update it to the latest version you just need to run the
`update.sh` file inside the root of the project.

:::warning
Make sure you SSH to your Vito server via `vito` user. If you logged in via the `root` user then change it to `vito` (
`su vito`)
:::

```sh
cd /home/vito/vito

bash scripts/update.sh
```

If the above command didn't update your VitoDeploy to the latest version, You can try the following commands:

```sh
git stash
git clean -f
bash scripts/update.sh
```

:::info
To get the `alpha` or `beta` releases of VitoDeploy you can simply pass `--alpha` or `--beta` flags to the update script.
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
To get the `alpha` or `beta` releases of VitoDeploy you can use the version tags like `vitodeploy/vito:3.0.0-alpha-1` or use the `3.x` tag to get the latest code on the `3.x` branch.
:::
