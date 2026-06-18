# Contribution Guide

## Environment Setup

Setup your local environment by following the [installation documentation](../getting-started/installation.md#install-locally)

## Development Discussion

You may propose new features or improvements of existing behavior in the repository's [GitHub discussion board](https://github.com/vitodeploy/vito/discussions). If you propose a new feature, please be willing to implement at least some of the code that would be needed to complete the feature.

Informal discussion regarding bugs, new features, and implementation of existing features takes place in the #general channel of our [Discord](https://discord.gg/uZeeHZZnm5) server.

## Which Branch?

All bug fixes should be sent to the latest version that supports bug fixes (currently `1.x`).

Minor features that are fully backward compatible with the current release may be sent to the latest stable branch (currently `1.x`).

Major new features or features with breaking changes should always be sent to the `main` branch, which contains the upcoming release.

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an email to Saeed Vaziry at sa.vaziry@gmail.com. All security vulnerabilities will be promptly addressed.

## Coding Style

The coding style is already hardcoded in configurations inside the project.

For PHP codes you may run `./vendor/bin/pint` to fix your coding styles.

And for the frontend codes you may run `npm run lint:fix` to fix your frontend coding styles.

## Code of Conduct

Violations of the code of conduct should be reported to Saeed Vaziry at sa.vaziry@gmail.com.

- Participants should embrace tolerance toward differing opinions.
- It is imperative for participants to use language and exhibit behaviors that avoid personal attacks and demeaning comments.
- When evaluating the statements and actions of others, it is crucial to presume positive intentions.
- Any behavior deemed as reasonably constituting harassment is strictly prohibited.
