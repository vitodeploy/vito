#!/usr/bin/env bash
#
# Group auto-generated GitHub release notes by PR title prefix.
#
# Reads raw release notes (as produced by GitHub's "generate_release_notes")
# on stdin and writes grouped notes to stdout. Changelog entries under the
# "What's Changed" heading are sorted into three sections based on their title
# prefix:
#
#   [Feat] ... -> ### Features
#   [Fix]  ... -> ### Fixes
#   anything else -> ### Others
#
# The [Feat]/[Fix] prefix is stripped from each line once it has been sorted
# into its section. Anything outside the "What's Changed" list (the
# "New Contributors" block, the "Full Changelog" link, etc.) is preserved
# verbatim and printed after the grouped sections.
#
# Usage:
#   group-release-notes.sh < raw-notes.md > grouped-notes.md

set -euo pipefail

features=""
fixes=""
others=""
trailer=""

# GitHub puts changelog entries under a "## What's Changed" heading and any
# additional info (e.g. "## New Contributors") under later headings. Only list
# items in the changelog section should be categorized.
in_changelog="false"

while IFS= read -r line || [[ -n "$line" ]]; do
  if [[ "$line" =~ ^##[[:space:]]+ ]]; then
    if [[ "$line" =~ [Ww]hat\'?s[[:space:]]+[Cc]hanged ]]; then
      in_changelog="true"
    else
      in_changelog="false"
      trailer+="${line}"$'\n'
    fi
    continue
  fi

  # Changelog entries are markdown list items: "* ..." or "- ...".
  if [[ "$in_changelog" == "true" && "$line" =~ ^[*-][[:space:]]+(.*)$ ]]; then
    entry="${BASH_REMATCH[1]}"

    if [[ "$entry" =~ ^\[Feat\]\ (.*)$ ]]; then
      features+="* ${BASH_REMATCH[1]}"$'\n'
    elif [[ "$entry" =~ ^\[Fix\]\ (.*)$ ]]; then
      fixes+="* ${BASH_REMATCH[1]}"$'\n'
    else
      others+="* ${entry}"$'\n'
    fi

    continue
  fi

  # A non-blank, non-list line inside the changelog section (e.g. the
  # "Full Changelog" link that follows the list) marks the end of the list.
  if [[ "$in_changelog" == "true" && -n "$line" ]]; then
    in_changelog="false"
  fi

  # Outside the changelog list: keep lines (e.g. the New Contributors entries
  # and the Full Changelog link, with their spacing) for the trailer.
  if [[ "$in_changelog" != "true" ]]; then
    trailer+="${line}"$'\n'
  fi
done

output=""

if [[ -n "$features" ]]; then
  output+="### Features"$'\n\n'"$features"$'\n'
fi

if [[ -n "$fixes" ]]; then
  output+="### Fixes"$'\n\n'"$fixes"$'\n'
fi

if [[ -n "$others" ]]; then
  output+="### Others"$'\n\n'"$others"$'\n'
fi

if [[ -n "$trailer" ]]; then
  # Drop leading blank lines so the trailer hugs the last grouped section.
  while [[ "$trailer" == $'\n'* ]]; do
    trailer="${trailer#$'\n'}"
  done
  output+="$trailer"
fi

# Trim a trailing blank line for tidy output.
printf '%s' "${output%$'\n'}"
printf '\n'
