# Variables and environment files (Beta)

Environment variables are shared by the app components of one environment. Save them under **Variables**, or paste an env file into **Import**. Saving, importing or deleting variables marks components “Redeploy to apply”; it does not immediately change running servers.

## Names, values and secrets

Keys use uppercase letters, digits and underscores, cannot start with a digit, and are at most 255 characters. Bash control/special variables and startup hooks are reserved. Values can contain spaces, quotes, dollar signs and multiple lines. Line endings are normalized to LF; NUL bytes are rejected.

Mark sensitive values as secret. Stored values are encrypted, and secret values are not returned to the page. Editing a secret lets you keep the existing value or explicitly replace it, including with an empty string. Making a secret public requires a replacement value; it does not reveal the old one.

Import previews keys and suggested secret flags, not values. Names containing `PASSWORD`, `SECRET`, `TOKEN`, `KEY` or `PRIVATE`, and names ending in `_URL`, are suggested as secret. These guesses are conservative: confirm the flags before importing. Import overwrites matching keys and keeps others; duplicate keys or invalid input reject the entire import. Values are literal, not interpolated from other variables.

## Resolution order

From lowest to highest priority:

1. [Resource connection variables](resources.md) and optional preset aliases.
2. Explicit environment variables.
3. Vito-computed values, such as `PORT` for a proxy web component.

The Variables page labels read-only resource/Vito values and component-specific overrides. Connection addresses can differ per component because a local resource uses loopback while a remote one uses a private network. Resource passwords, URLs and preset aliases are masked.

## On the server

Each component has its own protected env file, linked as `.env` in releases. Vito writes it with mode `600` as the component user, and writes its `vito-env-exec` loader with mode `700`. Proxy processes, workers, scheduler commands and deployment scripts use this loader. It parses values as data, never `source`s or `eval`s the file.

PHP-FPM pools are shared per Linux user/PHP version, including with Sites. Vito does **not** put application variables in FPM pool or vhost configuration. FPM applications must read the release's `.env` using their own dotenv support. Vito also keeps variable values out of Supervisor and cron configurations; user-authored commands must not echo secrets into logs.

The generated file uses literal quoting. Vito's loader preserves the supported values exactly; third-party dotenv readers differ, particularly for escaped quotes, dollar signs, backslashes and multiline values in double quotes. Test your runtime's parser rather than assuming Node `--env-file`, PHP, Ruby and shell parsing are interchangeable. Never source the generated file yourself.

A full deployment snapshots variables when it starts. Build and release-command scripts load the staged file; release activates it and post-release uses the live file. Edits during a run stay flagged for the next deployment. Automatic release recovery restores the previous file with the previous code. An explicit environment rollback is **code-only** and keeps the currently live variables.
