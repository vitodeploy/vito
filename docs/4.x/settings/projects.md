# Projects

## Introduction

Vito enables you to handle your different projects.

Every user in Vito can have multiple projects and the servers belong to projects. The relationship between servers and
projects is one-to-many

## Creating a Project

To create a project you just need a unique name! Go to the Projects page and create a new one. It will appear in the
left sidebar, Under Projects dropdown.

:::info
Only admins can create projects.
:::

## Project Roles

Within a project, each user has one of three roles:

- **Owner**: the user who created the project. The owner has full control and cannot be removed.
- **Admin**: can manage the project, including inviting and removing users.
- **User**: has access to the project's resources according to their permissions.

:::info
These per-project roles are separate from the instance-level [user roles](/docs/4.x/admin/users)
(Admin/User). A user who is not an instance-level admin can still be the owner or admin of a project.
:::

## Inviting Users

Admins (and the owner) can invite users to a project by email. Go to the project's user management,
enter the user's email address, and choose whether they join as an **Admin** or a **User**. Vito
sends an invitation email with a link to accept.

The invited person accepts the invitation from the link in the email to join the project. Once they
accept, the project appears in their Projects dropdown.

## Leaving a Project

A user can leave a project they were invited to. The project owner cannot leave their own project
(delete the project instead).

## Project User Management

Admins can manage the users of the project. They can invite, change, or remove users from the project.

## Deleting a Project

Deleting a project is as easy as hitting the delete button and confirming that you want to delete it.

:::danger
If you delete a project it will delete all of its servers! If you are using
a [cloud provider](./server-providers) to create the servers, By deleting the project, It will also delete it
from the provider.
:::

## Switching Projects

In the left sidebar you can see the current Project which is already selected in the dropdown. To change it to another
project just open it and select the project you want to switch to. Then Vito will change your current project and will
load the selected project's servers.
