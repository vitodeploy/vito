#!/usr/bin/env bash

set -euo pipefail

features=""
fixes=""
others=""
trailer=""

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

  if [[ "$in_changelog" == "true" && -n "$line" ]]; then
    in_changelog="false"
  fi

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
  while [[ "$trailer" == $'\n'* ]]; do
    trailer="${trailer#$'\n'}"
  done
  output+="$trailer"
fi

printf '%s' "${output%$'\n'}"
printf '\n'
