# Installation

## Install via Docker

Vito provides a single docker image that you can install it easily!

### Docker Command

```sh
docker run -v vito_storage:/var/www/html/storage \
    -e APP_KEY=your_32_character_app_key  \
    -e NAME=admin_name \
    -e EMAIL=admin_email \
    -e PASSWORD=admin_password \
    -p 80:80 vitodeploy/vito:latest
```

:::info
`APP_KEY`
is [Laravel's encryption key](https://github.com/laravel/laravel/blob/3622d746fde67ebaff3dd3fdde3676599434692f/config/app.php#L20-L25)
and should be a 32-character string.

<GenerateAppKey />
:::

:::warning
Make sure you modify the env variables when running the command.
:::

### Docker Compose

```yaml
version: '3'
services:
  vito:
    image: vitodeploy/vito:latest
    ports:
      - '8000:80'
    environment:
      APP_KEY: 'your-32-character-app-key'
      NAME: 'vito'
      EMAIL: 'vito@example.com'
      PASSWORD: 'password'
    volumes:
      - 'vito-storage:/var/www/html/storage'
volumes:
  vito-storage:
    driver: local
```

### Environment Variables

`APP_KEY`: A 32-character string app key used for encryption within the app.

`APP_URL`: The URL of your Vito instance, by default is `http://localhost`

`NAME`: Your account's name

`EMAIL`: Your account's email for login

`PASSWORD`: Your account's password for login (You can change it after login)

### Beta Releases on Docker

If you want to test the beta version of Vito, You can use the `2.x` tag.

## Install on VPS

Vito can be installed on a **fresh** Ubuntu server with only one single command.

### Requirements

- Ubuntu 22.04 LTS or Higher
- 1 GB Memory
- 1 CPU
- 80 and 443 ports should be open

:::info
Other Ubuntu versions have not been tested, and we don't recommend them.
:::

:::warning
You cannot use Vito to install applications into the same server as Vito. Otherwise, It might crash your Vito instance.
:::

### Installation Process

Login to the `root` user of your server via SSH and run the following command:

```sh
bash <(curl -Ls https://raw.githubusercontent.com/vitodeploy/vito/2.x/scripts/install.sh)
```

:::warning
You need to run the command via `root` user!
:::

The installation will ask you for these inputs:

**Email**

This is the admin email that you will use to log into VitoDeploy.

You can also pass this input as an env variable `ADMIN_EMAIL`

**Password**

This is the admin user's password.

You can also pass this input as an env variable `ADMIN_PASSWORD`

### Ready

The installation can take several minutes, and after it is done, It will print an output like bellow:

```txt
🎉 Congratulations!
✅ SSH User: SSH-USER
✅ SSH Password: SSH-PASSWORD
✅ Admin Email: ADMIN-EMAIL
✅ Admin Password: ADMIN-PASSWORD
```

At this step, You can ask VitoDeploy by visiting the IP address of your server on a browser.

:::warning
Make sure that your server is accessible via port 80 and 443.
:::

Now you can [attach a domain and secure it](./securing).

### Beta Releases on VPS

To install beta releases you can pass `BETA=1` as an env variable.

```sh
BETA=1 bash <(curl -Ls https://raw.githubusercontent.com/vitodeploy/vito/2.x/scripts/install.sh)
```

## Install Locally

To contribute to VitoDeploy and run it locally we have Laravel Sail ready!

VitoDeploy is a Laravel project which means you can follow [Laravel Documentation](https://laravel.com) to see how you can run it locally but
here we will recommend only one way.

VitoDeploy uses OpenSSL keys to connect to your servers so you can manage them, Every Vito instance should have its own
public and private key to do so. With the following command, you can generate a key pair.

If you're using Mac or Linux, You can run it on your terminal. If you're using Windows, You can run it on the app
container after booting up the app via Laravel Sail.

```sh
openssl genpkey -algorithm RSA -out /PATH_TO_VITO/storage/ssh-private.pem
chmod 600 /PATH_TO_VITO/storage/ssh-private.pem
ssh-keygen -y -f /PATH_TO_VITO/storage/ssh-private.pem > /PATH_TO_VITO/storage/ssh-public.key
```

### Laravel Sail

To run the app locally via Laravel Sail clone the repository into your local machine and then run the following
commands:

Set the envs

```sh
cp .env.sail .env
```

Fill the `.env` file and then

```sh
composer install
```

And then boot up with Sail

```sh
./vendor/bin/sail up -d
```

Generate App Key

```sh
./vendor/bin/sail artisan key:generate
```

Create the `database.sqlite` in the `storage` folder.

Run the migrations

```sh
./vendor/bin/sail artisan migrate
```

Create a new user

```sh
./vendor/bin/sail artisan user:create {name} {email} {password}
```

Vito by default will run on localhost:8000. You can change the port on your `.env` file.

[Sail Documentation](https://laravel.com/docs/10.x/sail)

## Migrating

To migrate from a previous installation and import an existing VitoDeploy configuration you can simply overwrite the
`storage` folder with the contents of the old one. For this to work you will also need to use the same Laravel `APP_KEY`
in your .env file. You can subsequently generate a new one using `php artisan key:generate`.
