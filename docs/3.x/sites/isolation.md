# Site Isolation

Every site in VitoDeploy is isolated with its own system user. When you create a website, you must specify a unique
system user for that website. This ensures all sites on a server are fully isolated from each other.

## How it works

When you create a new site, you need to provide a `User` field with a unique username. VitoDeploy will create a
dedicated system user on the server for that site. Each site runs under its own user, ensuring complete isolation.

The site's files will be stored under the user's home directory (e.g., `/home/your-user/domain.com`).

## Why Site Isolation

Site Isolation is a security best practice. By running each site under its own system user, if one of your websites gets
hacked and the attacker gains access to that site's user, they cannot access other websites on your server.
