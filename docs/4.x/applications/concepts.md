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

Click **New application**, pick a repository and a server, then click **Deploy**. Vito:

1. Reads the repository to suggest its default branch, runtime and a compatible version. You can change any of them, and you pick the runtime yourself when it can't be detected.
2. Creates a `Production` environment and a `web` [component](components.md) on the server. Name, Linux user, capabilities and other details are under **Advanced**.
3. Optionally creates a [database](resources.md) on the same server. It is on by default for presets that suggest one, such as Laravel, when the server has a ready database and firewall.
4. Seeds preset [variables](variables.md). Laravel gets a generated `APP_KEY`, `APP_ENV` and `APP_DEBUG=false`. When moving an existing app, paste its `APP_KEY` under **Advanced**, otherwise it can't read data encrypted with the old key.
5. Turns on deploy on push, then starts the first deploy as soon as the component and database are ready. If the repository webhook can't be installed, deploy on push is turned off and the environment shows a warning.
6. Add [domains](domains.md) when it is live, directly to the web component or to a [load balancer](load-balancing.md).

Creating an application without a server is still possible when the project has no ready servers. Add components from the graph, then deploy. Additional environments, components and resources are added from the graph, and these do not deploy automatically.

The overview shows the latest run, component/domain status, preset warnings and a read-only topology. Server bands show placement; arrows show routing and resource dependencies. Click a node to open its details. These are Vito lifecycle statuses, **not live health checks**. “Redeploy to apply” means saved changes have not yet been applied by a successful deploy.

## Beta boundaries

Use disposable environments to validate your deployment and recovery procedure before relying on it for production. Deployment recovery cannot reverse database migrations or other external effects of scripts. Sites retain their existing workflows. Component moves between servers, Site conversion, application-specific raw vhost editing and a public Applications API are not provided.
