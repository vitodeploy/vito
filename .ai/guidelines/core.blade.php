# Core Guidelines

**This is the core guidelines. These are very important and high prio and have precedence over other guidelines in this file.**

## General Rules

- If you find yourself in a loop of failing, ask the user before continuing.
- Don't generate redundant code. If the code is already there, use it.
- Write LESS code! This is very important.

## Coding

- Always follow the same coding style that the project uses and other parts are built with
- Use simplified solutions rather than complex ones
- Always analyse the current codebase before adding new code
- This codebase is written by human. Always check with the user if something is not clear or doesn't make sense.
- Write code with less dependencies on third-party packages
- Write reusable code all the time.
- Never run npm build or dev or artisan serve commands because user is already running them in the background.
- Avoid unnecessary code comments.

## Testing

- Don't write too many tests.
- Don't test the framework or third-party packages. Only test the logic.
- Always use refresh database in the tests.
- Never test obvious things like command signature or transactions or etc.
- Use existing tests to add more tests rather than creating focused test files. If doesn't exist, ask the user's confirmation when creating.

## Working with Git and Issues and PRs

- The system has `gh` installed. use it to view the issues and PRs.

## Code Formatting and Style and Linting

- Don't run formatters like pint or prettier. User will run them later.
- When writing code, keep an eye on the PHPStan level and don't make mistakes.
