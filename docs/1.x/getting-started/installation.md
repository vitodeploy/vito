# Installation

## Install via Docker

Vito provides a single docker image that you can install it easily!

### Docker Command

```sh
docker run -v vito_storage:/var/www/html/storage \
    -e APP_KEY=your_32_character_app_key  \
    -e NAME=your_name \
    -e EMAIL=your_email \
    -e PASSWORD=your_password \
    -p 80:80 vitodeploy/vito:1.x
```

:::warning
Make sure you modify the env variables when running the command.
:::

### Docker Compose

```yaml
version: "3"
services:
  vito:
    image: vitodeploy/vito:1.x
    ports:
      - "8000:80"
    environment:
      APP_KEY: "your-32-character-app-key"
      NAME: "vito"
      EMAIL: "vito@example.com"
      PASSWORD: "password"
    volumes:
      - "vito-storage:/var/www/html/storage"
volumes:
  vito-storage:
    driver: local
```

### Environment Variables

`APP_KEY`: A 32-character string app key used for encryption within the app.

`APP_URL`: The URL of your Vito instance, by default is http://localhost

`NAME`: Your account's name

`EMAIL`: Your account's email for login

`PASSWORD`: Your account's password for login (You can change it after login)

## Install on VPS

### Requirements

- Ubuntu 22.04 LTS or Higher
- 1 GB Memory
- 1 CPU
- 80 and 443 ports should be open

:::info
Other Ubuntu versions might also work but we don't recommend them.
:::

:::warning
You cannot use Vito to install applications into the same server as Vito. Otherwise, It might crash your Vito instance.
:::

### Installation

Vito can be installed with only one single command on your server:

```sh
bash <(curl -Ls https://raw.githubusercontent.com/vitodeploy/vito/1.x/scripts/install.sh)
```

:::warning
The command should be ran as `root`
:::

The installation will ask you for these inputs:

**Email**

This is the email that you will use to login.

You can also pass this input as an env variable `ADMIN_EMAIL`

**Password**

This is the password you will use to login.

You can also pass this input as an env variable `ADMIN_PASSWORD`

### Ready

The installation can take several minutes and after it is done, It will print an output like bellow:

```txt
🎉 Congratulations!
✅ SSH User: SSH-USER
✅ SSH Password: SSH-PASSWORD
✅ Admin Email: ADMIN-EMAIL
✅ Admin Password: ADMIN-PASSWORD
```

Now open your server's IP address and enjoy Vito!

Now you can [attach a domain and secure it](./securing).

## Migrating 

To migrate from a previous installation and import an existing VitoDeploy configuration you can simply overwrite the `storage` folder with the contents of the old one. For this to work you will also need to use the same Laravel `APP_KEY` in your .env file. You can subsequently generate a new one using `php artisan key:generate`.

## Install Locally

To contribute to VitoDeploy and run it locally we have Laravel Sail ready!

VitoDeploy is a Laravel project which means you can follow Laravel's documentation to see how you can run it locally but here we will recommend only one way.

VitoDeploy uses OpenSSL keys to connect to your servers so you can manage them, Every Vito instance should have its own public and private key to do so. With the following command, you can generate a key pair.

If you're using Mac or Linux, You can run it on your terminal. If you're using Windows, You can run it on the app container after bringing up the Sail.

```sh
openssl genpkey -algorithm RSA -out /PATH_TO_VITO/storage/ssh-private.pem
chmod 600 /PATH_TO_VITO/storage/ssh-private.pem
ssh-keygen -y -f /PATH_TO_VITO/storage/ssh-private.pem > /PATH_TO_VITO/storage/ssh-public.key
```

### Laravel Sail

To run the app locally via Laravel Sail clone the repository into your local machine and then run the following commands:

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
