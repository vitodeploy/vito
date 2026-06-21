# Upgrade Guide

:::warning
Before upgrade first make a backup of `/home/vito/storage` folder for VPS installations and the volumes for the docker
installations.
:::

## Upgrading to 2.x from 1.x

### Upgrade Docker Installation

If you're using the latest tag, just do the [Update](../getting-started/update#update-docker) steps.

If you're using the `1.x` tag, You need to change it to `2.x` or `latest` tag.

:::info
We recommend using the `latest`.
:::

### Upgrade VPS Installation

SSH to your Vito instance with user `vito` and continue the steps:

Go to the root of the project:

```sh
cd /home/vito/vito
```

Pull the latest changes:

```sh
git pull
```

Switch to the `2.x` branch:

```sh
git checkout 2.x
```

Run the update script:

```sh
bash scripts/update.sh
```