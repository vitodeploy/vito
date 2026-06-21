# Users

## Introduction

The Users page in the admin area lets you manage who can log in to your Vito instance. You can
create, edit, and delete users, and assign each a role.

## Roles

Each user has one of two roles:

- **Admin** - full access to the instance, including the admin area (users, plugins, GitHub App,
  and export/import).
- **User** - standard access to manage the servers and sites within the projects they belong to,
  without access to the admin area.

## Creating a user

From **Admin > Users**, create a user by providing:

- **Name**
- **Email** - used to log in.
- **Password**
- **Role** - User or Admin.

## Editing and deleting users

You can edit a user to change their details or role, or delete a user that should no longer have
access.

:::info
You can also create users from the command line with `php artisan user:create`. See
[Terminal](/docs/4.x/admin/terminal#create-user).
:::
