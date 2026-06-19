# API

## Introduction

VitoDeploy ships with a REST API so you can automate everything you can do in the dashboard — creating servers and sites, managing databases, deploying, running workflows, and more.

## Interactive Reference

Your Vito instance hosts an interactive API reference (Swagger UI) at:

```
https://your-vito-instance/api/docs
```

The raw OpenAPI specification is available at `https://your-vito-instance/api.yaml`.

## Authentication

The API authenticates with a Bearer token. First, generate an [API key](settings/api-keys.md), then send it in the `Authorization` header of every request:

```sh
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://your-vito-instance/api/projects
```

## Permissions & Scope

API keys carry their permissions and project scope:

- **Read** keys can only perform read (`GET`) operations; **Read & Write** keys can also create, update, and delete.
- Keys scoped to specific projects can only access those projects. Unscoped keys can access all projects you have access to.

See [API Keys](settings/api-keys.md) for how to create and scope a key.

## Base URL & Endpoints

All endpoints are served under `/api` on your instance, and most are nested under a project, for example:

```
GET  /api/projects
GET  /api/projects/{project}/servers
POST /api/projects/{project}/servers/{server}/sites
```

The API covers resources including projects, servers, sites, databases and database users, cron jobs, workers, firewall rules, domains and DNS records, services, SSH keys, SSL certificates, providers (server/source-control/storage/DNS), and workflows.

Refer to the [interactive reference](#interactive-reference) on your instance for the complete, always-up-to-date list of endpoints, parameters, and response schemas.

## Health Check

A health endpoint is available at `/api/health` to verify the instance is up.
