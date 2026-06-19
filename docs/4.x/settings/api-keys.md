# API Keys

## Introduction

Vito exposes a REST API that lets you interact with your Vito instance programmatically. To use the API, you need to generate an API key.

For a full overview of the API, authentication, and the interactive reference, see the [API documentation](../api.md).

## How to Generate an API Key

1. Go to the **Settings** section in the top navigation bar.
2. Click on the **API Keys** menu.
3. Click on the **Create** button.
4. Set a name for your API key.
5. Choose the permission level and (optionally) the projects the key can access (see below).
6. Click on the **Create** button.
7. Copy the generated API key and store it securely. You won't be able to see it again.

## Permissions

Each API key has a permission level:

- **Read**: the key can only perform read operations.
- **Read & Write**: the key can also create, update, and delete resources.

## Project Scope

API keys are scoped to projects:

- If you select one or more projects when creating the key, it can only access resources within those projects.
- If you leave the project selection empty, the key can access all projects you have access to.

## How to Use the API Key

Send the key as a Bearer token in the `Authorization` header of your requests:

```sh
curl -H "Authorization: Bearer YOUR_API_KEY" https://your-vito-instance/api/projects
```

VitoDeploy's interactive API documentation is hosted on your Vito instance at `/api/docs`.
