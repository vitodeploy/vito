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
| Go | Proxy | Per-component Go toolchain; builds `./app` from the root package |
| Rust | Proxy | Per-component Rust toolchain; builds with Cargo in release mode |
| Python | Proxy | Per-component Python tooling and a virtual environment in each release |
| Static | Static | Files served directly by the webserver |
| Custom | Proxy | Your commands for a runtime not covered by a preset |

**FPM** sends requests to a PHP-FPM pool. **Proxy** forwards to a process listening on the component's `PORT`. **Static** serves files only. Plugins can register additional presets and must choose a web mode explicitly. Custom does not install an arbitrary runtime for you: prepare its tools on the server or provide a plugin preset.

Go is detected from `go.mod`, Rust from `Cargo.toml`, and Python from `requirements.txt`, `pyproject.toml` or `setup.py`. Version hints come from `.go-version` (or the minimum Go version in `go.mod`), the channel in `rust-toolchain` or `rust-toolchain.toml`, and `.python-version` (or `[project].requires-python` in `pyproject.toml` when the pin file is absent). Rust's legacy plain-text `rust-toolchain` format is also supported, and that filename takes precedence when both toolchain files exist. TOML hints are read from single-line quoted values. Missing or unsupported hints leave all supported versions available. PHP/Composer repositories keep their existing detection priority, even when they include tooling for another language.

These presets are framework-neutral starting points. Go builds the repository root with `go build -o app .`; change the package path for projects using a layout such as `./cmd/server`. Rust respects an existing `Cargo.lock`; change the suggested `./target/release/app` start command to your Cargo binary's name. Python creates `.venv` inside each release and installs `requirements.txt`, or installs the project with pip when using `pyproject.toml` or `setup.py`. Its default start command is `.venv/bin/python main.py`; replace it with your production WSGI/ASGI command when needed, and include its server package in your dependencies. Poetry/uv-only projects need an adapted build script. Do not share `.venv` between releases. All web processes must listen on `127.0.0.1` and the supplied `PORT`. Native dependencies may require additional system packages on the server.

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
