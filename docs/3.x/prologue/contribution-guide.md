# Contribution Guide

## Environment Setup

Set up your local environment by following
the [installation documentation](../getting-started/installation.mdx#install-locally)

## Where to start?

If you want to start right away, we recommend you to start from the open issues in the `Todo` state.

You can find them [here](https://github.com/orgs/vitodeploy/projects/5/views/12)

They're sorted by priority. So feel free to pick an unassigned one. Drop a comment so we can assign it to you.

:::warning
If you want to work on something which is not listed in the `ToDo` issues. Please read the below section!
:::

## Development Discussion

You may propose new features or improvements of existing behavior in the
repository's [Discussion Board](https://github.com/vitodeploy/vito/discussions). If you propose a new feature,
please be willing to implement at least some of the code that would be needed to complete the feature.

Informal discussion regarding bugs, new features, and implementation of existing features takes place in the #ideas
channel of our [Discord](https://discord.gg/uZeeHZZnm5) server.

:::warning
Please do not refactor any existing code without discussing it with the maintainers first.
:::

## Which Branch?

All bug fixes should be sent to the latest version that supports bug fixes (currently `3.x`).

Minor features that are fully backward compatible with the current release may be sent to the latest stable branch (
currently `3.x`).

Major new features or features with breaking changes should always be sent to the `main` branch, which contains the
upcoming major release.

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please email to Saeed Vaziry at sa.vaziry@gmail.com.
All security vulnerabilities will be promptly addressed.

## Coding Style

The coding style is already hardcoded in configurations inside the project.

For PHP codes you may run `./vendor/bin/pint` to fix your coding styles.

And for the frontend codes and bash scripts you may run `npm run format` to fix your frontend coding styles.

Code style is enforced by the CI pipeline, so please ensure your code passes the checks before submitting a pull
request.

## Code Quality

We use [PHPStan](https://phpstan.org/) for static analysis of the PHP code and [ESLint](https://eslint.org/) for the
frontend code.

You can run the static analysis tools locally by running the following commands:

```bash
# For PHP code
./vendor/bin/phpstan analyse
# For JavaScript/TypeScript code
npm run lint
```

Code quality is enforced by the CI pipeline, so please ensure your code passes the checks before submitting a pull

## Pre Commit

To ensure all the actions will pass, you can locally run `composer pre-commit` command which will format your code and run tests and check phpstan before you commit your changes.

## Code of Conduct

Violations of the code of conduct should be reported to Saeed Vaziry at sa.vaziry@gmail.com.

- Participants should embrace tolerance toward differing opinions.
- It is imperative for participants to use language and exhibit behaviors that avoid personal attacks and demeaning
  comments.
- When evaluating the statements and actions of others, it is crucial to presume positive intentions.
- Any behavior deemed as reasonably constituting harassment is strictly prohibited.
