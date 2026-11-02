# Applications (Beta)

Applications deploy one repository to multiple servers, alongside the existing Sites feature. Nothing converts or replaces a Site. Applications currently use the dashboard; they do not have a public API.

## Application, environment, component

- An **application** belongs to a project and selects a repository, source control and runtime preset.
- An **environment** selects a branch and runtime version, and owns variables, deployment settings, domains and attached resources. Production and staging can use different settings.
- A **component** places part of an environment on a server. App components run code and can combine web, worker and scheduler capabilities. Load balancer components route traffic without checking out code.

Servers, source controls and attached resources must be available to the application's project. A global source control can also be used. Moving a source control to another project makes it unavailable for new deployments, queued commit resolution, clones and new deploy-key registration; select an available source before continuing.

## Presets and web modes

The deployment core is runtime-agnostic. Presets suggest editable scripts, commands and paths; they are not a separate framework-specific deployment engine.

| Preset | Web mode | Starting point |
| --- | --- | --- |
| PHP | FPM | PHP-FPM with the selected PHP version |
| Laravel | FPM | Suggested build/release scripts, public directory and shared storage |
| Node | Proxy | Per-component Node tooling and a managed application process |
| Bun | Proxy | Per-component Bun tooling and a managed application process |
| Static | Static | Files served directly by the webserver |
| Custom | Proxy | Your commands, for example a Python or Go service |

**FPM** sends requests to a PHP-FPM pool. **Proxy** forwards to a process listening on the component's `PORT`. **Static** serves files only. Plugins can register additional presets and must choose a web mode explicitly. Custom does not install an arbitrary runtime for you: prepare its tools on the server or provide a plugin preset.

## Getting started

1. Create an application with a project-available source control and repository.
2. Create an environment, choosing its branch and runtime version when required.
3. Add [components](components.md) on ready servers with the required services installed.
4. Configure [variables](variables.md), optional [resources](resources.md), and [deployment scripts](deployments.md).
5. Select a ready app component as primary and deploy. The release command runs there once.
6. Add [domains](domains.md), directly to web components or to a [load balancer](load-balancing.md).

The overview shows the latest run, component/domain status, preset warnings and a read-only topology. Server bands show placement; arrows show routing and resource dependencies. Click a node to open its details. These are Vito lifecycle statuses, **not live health checks**. “Redeploy to apply” means saved changes have not yet been applied by a successful deploy.

## Beta boundaries

Use disposable environments to validate your deployment and recovery procedure before relying on it for production. Deployment recovery cannot reverse database migrations or other external effects of scripts. Sites retain their existing workflows. Component moves between servers, Site conversion, application-specific raw vhost editing and a public Applications API are not provided.
