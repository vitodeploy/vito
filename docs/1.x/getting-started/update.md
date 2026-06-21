# Update

## Update VM

If you've installed VitoDeploy on a linux virtual machine, To update it to the latest version you just need to run the `update.sh` file inside the root of the project.

:::warning
Make sure you SSH to your Vito server via `vito` user. If you logged in via the `root` user then change it to `vito` (`su vito`)
:::

```sh
cd /home/vito/vito

bash scripts/update.sh
```

## Update Docker

If you've installed VitoDeploy on a docker container, You just need to pull the latest version and recreate the container

Pull the latest version:

```sh
docker pull vitodeploy/vito:latest
```

And then recreate the container!
