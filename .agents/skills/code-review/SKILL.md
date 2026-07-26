---
name: code-review
description: Review code changes 
---

# Code review

Review the code changes in 2 directions. Specs and Standards.

## Specs

Understand the reason of the changes. This can be from the commit messages, PR summary and title, user given information or self collected from the changes.

## Standards

Understand the project standards, check existing patterns, read AGENTS.md or CLAUDE.md.

## Review Process

Spawn 2 independent and parallel sub-agents without sharing or accessing context to eachother.

**Specs Agent:** Should review the code changes to verify if they are achieving the specs or not.

**Standards Agent:** Should review the code changes to verify if they are following the project standards or not.

