# Deployments (Beta)

Deploy an environment from its overview or Deployments page. Select a ready app component as **primary** first, and wait for attached resources to finish synchronizing. Repository access must be available to the application's project.

## Stages

A full run pins one complete commit SHA when it starts and gives every participating app component the same release name. Moving the branch head during a run does not change that commit.

| Stage | Participants | Work |
| --- | --- | --- |
| Clone | Ready app components | Check out the pinned commit, stage variables and link shared paths |
| Build | App components | Run the effective build script in the new release |
| Release command | Primary app component only | Run the pre-release script once, for example a migration |
| Release | All participants | Activate code/env and restart app processes; reload load balancers |
| Post-release | App components | Run the effective post-release script |

A stage waits for its participants before advancing. Load balancers only participate in release, with no repository, runtime or release directory. The deployment detail page shows stage/status per component and links to its logs.

## Scripts and shared paths

Deployment settings contain editable plain-shell build, release-command and post-release scripts, initially suggested by the preset. Component overrides replace an inherited script rather than appending; an explicitly empty override skips it. Scripts receive `COMMIT_ID`, `RELEASE_PATH` and `BRANCH` alongside environment variables. Do not print credentials into logs.

Shared paths are release-relative files/directories whose contents survive deployments. They cannot contain `..` or name `.env`, which Vito manages separately. The first release seeds a missing shared path. Configure release retention here too; active releases are preserved.

## Queueing and cancellation

One run is active per environment. Deploying while it runs keeps only the newest pending request; older pending requests become superseded. A queued full run resolves the branch head when it starts. Cancellation stops new stages, but already running work must settle before the next run begins. Deadlines prevent an abandoned stage from blocking forever; inspect failures before retrying.

Repository, runtime and capability changes that conflict with a run are blocked. Variable edits are allowed and remain pending for the next deploy.

## Failure and rollback

- Failure before release leaves the live code/env unchanged.
- Failure during release requests recovery for participants: restore previous code/env and restart processes. The run becomes **rolled back** on successful recovery or **partial** if recovery fails. This is coordinated recovery, not an instantaneous cross-server transaction.
- Failure after release leaves the new release live and reports the post-release failure.

From a finished deployment, **Roll back** creates a code-only environment run if that release remains available on every deployed component and the environment is ready. It restarts processes and reloads load balancers, but skips build/release-command/post-release scripts and keeps current variables. It does not reverse migrations, external calls or other release-command side effects. Back up data and design reversible changes yourself.

## Auto-deploy and live-release join

Enable auto-deploy in Deployment settings. With a configured GitHub App or supported source-control webhook, a matching repository/branch push queues an environment deployment. A source-control boundary check still applies to automatic runs. Keep webhook configuration and repository permissions current.

New ready components automatically join the live release when possible, using its commit and variable snapshot without rerunning the release command or replacing the live-run pointer. A full pending deployment takes precedence. Failed joins require inspection and a later full deployment.
