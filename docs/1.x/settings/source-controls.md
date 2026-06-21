# Source Controls

Currently Vito supports the top 3 Source Control providers

- Github
- Gitlab
- Bitbucket

Vito can connect to those providers via their APIs.

## Github

Generate a personal access token on Github settings of your account and give it full repository control and git hook admin.

## Gitlab

Generate a personal access token on your Gitlab profie and give it repository and webhook admin.

## Bitbucket

Create an App Password on your Bitbucket account and give it repository and webhook admin permissions.

## Source Control Scopes

Source controls can be created under a specific project or globally.

If you create a source control under a project, it will only be available for that project.

If you create a source control globally, it will be available for all projects.

The reason of this feature is when you add a new user to VitoDeploy, you can control which source controls they can access.
