# Site Isolation

:::warning
Site isolation is still in beta.
:::

Site Isolation is a feature that allows you to create isolated websites. This means, everytime you create a website, you
can specify a custom system
user for that website. This way, you can isolate your websites from each other.

## How to use

When you create a new site, you will see a new field called `User`. You can specify a username (unique) for your
website. If you don't specify a system user, it will create the website in the main ssh user of your server which is
`vito` user.

## Why use Site Isolation

Site Isolation is a good practice to isolate your websites from each other. This way, if one of your websites gets
hacked, and the hacker gains access to your website's user, they can't access other websites on your server.