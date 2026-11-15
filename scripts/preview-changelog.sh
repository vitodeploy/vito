#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

BOLD=$'\033[1m'
DIM=$'\033[2m'
RED=$'\033[31m'
GREEN=$'\033[32m'
YELLOW=$'\033[33m'
BLUE=$'\033[34m'
MAGENTA=$'\033[35m'
CYAN=$'\033[36m'
RESET=$'\033[0m'

usage() {
  cat <<EOF
Preview the release notes that would be generated for the next beta or stable
release of Vito. Calls the GitHub generate-notes API the same way the release
workflow (.github/workflows/releases.yml) does, so the output matches the
actual release.

Vito ships a major version per branch (1.x, 2.x, 3.x, 4.x, ...). The major is
taken from the current branch; tags have no "v" prefix and betas look like
X.Y.Z-beta-N. The current version lives in config/app.php.

Usage:
  scripts/preview-changelog.sh beta   [--version X.Y.Z] [--since <tag>|auto] [--fetch] [--raw]
  scripts/preview-changelog.sh stable [--version X.Y.Z] [--since <tag>|auto] [--fetch] [--raw]

Beta mode:
  Version defaults to the next beta for the current major: the latest
  X.Y.Z-beta-N tag with N incremented (or config/app.php's base + -beta-1 when
  no beta exists yet). The range starts at the most recent beta tag.

Stable mode:
  Version defaults to config/app.php's base version (the -beta-N suffix
  stripped). The range starts at the most recent stable tag for this major.

Options:
  --version X.Y.Z   Override the version being previewed (without -beta-N).
  --since <tag>     Force the previous tag the changelog starts from.
  --since auto      Let GitHub auto-pick the previous tag (matches the workflow).
  --fetch           Run 'git fetch --tags' first so tag discovery is current.
  --repo owner/name Override the repository (defaults to the 'origin' remote).
  --raw             Print the exact markdown body from GitHub, no styling.
EOF
}

MODE="${1:-}"
if [[ -z "$MODE" || "$MODE" == "-h" || "$MODE" == "--help" ]]; then
  usage
  [[ -z "$MODE" ]] && exit 1 || exit 0
fi
shift

if [[ "$MODE" != "beta" && "$MODE" != "stable" ]]; then
  printf "%sError:%s mode must be 'beta' or 'stable' (got '%s')\n" "$RED" "$RESET" "$MODE" >&2
  exit 1
fi

VERSION_OVERRIDE=""
SINCE_OVERRIDE=""
REPO_OVERRIDE=""
FETCH=0
RAW=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    --version) VERSION_OVERRIDE="${2:-}"; shift 2 ;;
    --since)   SINCE_OVERRIDE="${2:-}"; shift 2 ;;
    --repo)    REPO_OVERRIDE="${2:-}"; shift 2 ;;
    --fetch)   FETCH=1; shift ;;
    --raw)     RAW=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) printf "%sError:%s unknown argument '%s'\n" "$RED" "$RESET" "$1" >&2; exit 1 ;;
  esac
done

if ! command -v gh >/dev/null 2>&1; then
  printf "%sError:%s 'gh' is required. Install with: brew install gh\n" "$RED" "$RESET" >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  printf "%sError:%s 'gh' is not authenticated. Run: gh auth login\n" "$RED" "$RESET" >&2
  exit 1
fi

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  printf "%sError:%s not inside a git repository\n" "$RED" "$RESET" >&2
  exit 1
fi

if [[ -n "$REPO_OVERRIDE" ]]; then
  REPO="$REPO_OVERRIDE"
elif git remote get-url origin >/dev/null 2>&1; then
  REPO="$(git remote get-url origin | sed -E 's#(git@github.com:|https://github.com/)##; s#\.git$##')"
else
  REPO="$(gh repo view --json nameWithOwner --jq .nameWithOwner)"
fi

if [[ "$FETCH" -eq 1 ]]; then
  printf "%sFetching tags from origin...%s\n" "$DIM" "$RESET" >&2
  git fetch --tags --quiet origin || true
fi

BRANCH="$(git rev-parse --abbrev-ref HEAD)"

BRANCH_MAJOR=""
if [[ "$BRANCH" =~ ^([0-9]+)\.x$ ]]; then
  BRANCH_MAJOR="${BASH_REMATCH[1]}"
fi

CONFIG_RAW="$(grep -oE "'version' => '[^']*'" config/app.php | grep -oE "'[^']*'$" | tr -d "'" || true)"
if [[ -z "$CONFIG_RAW" ]]; then
  printf "%sError:%s could not read 'version' from config/app.php\n" "$RED" "$RESET" >&2
  exit 1
fi
CONFIG_BASE="${CONFIG_RAW%%-beta-*}"

MAJOR="${BRANCH_MAJOR:-${CONFIG_BASE%%.*}}"

latest_beta_tag() {
  git tag --list "${MAJOR}.*-beta-*" 2>/dev/null \
    | grep -E "^${MAJOR}\.[0-9]+\.[0-9]+-beta-[0-9]+$" \
    | sort -V | tail -1 || true
}

latest_stable_tag() {
  git tag --list "${MAJOR}.*" 2>/dev/null \
    | grep -E "^${MAJOR}\.[0-9]+\.[0-9]+$" \
    | sort -V | tail -1 || true
}

next_beta_number() {
  local base="$1" last
  last="$(git tag --list "${base}-beta-*" 2>/dev/null \
    | grep -E "^${base//./\\.}-beta-[0-9]+$" | sort -V | tail -1 || true)"
  if [[ -n "$last" ]]; then
    echo $(( ${last##*-beta-} + 1 ))
  else
    echo 1
  fi
}

validate_xyz() {
  if ! [[ "$1" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    printf "%sError:%s --version must be X.Y.Z (got '%s')\n" "$RED" "$RESET" "$1" >&2
    exit 1
  fi
}

hr() {
  local color="$1"
  printf "%s%s" "$BOLD" "$color"
  printf '━%.0s' {1..68}
  printf "%s\n" "$RESET"
}

print_header() {
  local channel="$1" version="$2" tag="$3" target="$4" target_sha="$5" prev="$6"
  local color="$CYAN"
  [[ "$channel" == "Stable" ]] && color="$GREEN"

  printf "\n"
  hr "$color"
  printf "  %s%sVito Release Preview%s\n" "$BOLD" "$color" "$RESET"
  hr "$color"
  printf "  %sChannel:%s  %s\n" "$BOLD" "$RESET" "$channel"
  printf "  %sVersion:%s  %s\n" "$BOLD" "$RESET" "$version"
  printf "  %sTag:%s      %s\n" "$BOLD" "$RESET" "$tag"
  printf "  %sTarget:%s   %s%s\n" "$BOLD" "$RESET" "$target" "$target_sha"
  if [[ -n "$prev" ]]; then
    printf "  %sSince:%s    %s\n" "$BOLD" "$RESET" "$prev"
  else
    printf "  %sSince:%s    %s(auto — GitHub picks the previous release)%s\n" "$BOLD" "$RESET" "$DIM" "$RESET"
  fi
  hr "$color"
  printf "\n"
}

strip_noise() {
  awk '
    /^## New Contributors[[:space:]]*$/ { in_contrib = 1; next }
    in_contrib {
      if (/^## / || /^\*\*Full Changelog\*\*/) { in_contrib = 0 }
      else { next }
    }
    /^[*-] / {
      sub(/[[:space:]]+by[[:space:]]+@[A-Za-z0-9_-]+[[:space:]]+in[[:space:]]+https?:\/\/[^[:space:]]+[[:space:]]*$/, "")
      sub(/[[:space:]]+\(#[0-9]+\)[[:space:]]*$/, "")
    }
    { print }
  '
}

style_notes() {
  if [[ "$RAW" -eq 1 ]]; then
    printf "%s\n" "$1"
    return
  fi
  printf "%s\n" "$1" | strip_noise | awk \
    -v B="$BOLD" -v D="$DIM" -v C="$CYAN" -v M="$MAGENTA" -v G="$GREEN" -v R="$RESET" '
    /^## / {
      sub(/^## /, "")
      printf "%s%s%s%s\n\n", B, C, $0, R
      next
    }
    /^\*\*Full Changelog\*\*/ {
      gsub(/\*\*/, "")
      printf "\n%s%s%s\n", D, $0, R
      next
    }
    /^[*-] / {
      sub(/^[*-] /, "")
      printf "  %s•%s %s\n", G, R, $0
      next
    }
    /^### / {
      sub(/^### /, "")
      printf "%s%s%s%s\n", B, M, $0, R
      next
    }
    NF == 0 { print ""; next }
    { print }
  '
}

if [[ "$MODE" == "beta" ]]; then
  CHANNEL="Beta"
  if [[ -n "$VERSION_OVERRIDE" ]]; then
    validate_xyz "$VERSION_OVERRIDE"
    BASE="$VERSION_OVERRIDE"
  else
    LATEST_BETA="$(latest_beta_tag)"
    BASE="${LATEST_BETA%-beta-*}"
    BASE="${BASE:-$CONFIG_BASE}"
  fi
  N="$(next_beta_number "$BASE")"
  VERSION="${BASE}-beta-${N}"
  TAG="$VERSION"

  if [[ "$SINCE_OVERRIDE" == "auto" ]]; then
    PREV=""
  elif [[ -n "$SINCE_OVERRIDE" ]]; then
    PREV="$SINCE_OVERRIDE"
  else
    PREV="$(latest_beta_tag)"
  fi
else
  CHANNEL="Stable"
  VERSION="${VERSION_OVERRIDE:-$CONFIG_BASE}"
  validate_xyz "$VERSION"
  TAG="$VERSION"

  if [[ "$SINCE_OVERRIDE" == "auto" ]]; then
    PREV=""
  elif [[ -n "$SINCE_OVERRIDE" ]]; then
    PREV="$SINCE_OVERRIDE"
  else
    PREV="$(latest_stable_tag)"
  fi
fi

if [[ -n "$BRANCH_MAJOR" && "${VERSION%%.*}" != "$BRANCH_MAJOR" ]]; then
  printf "%sWarning:%s version %s does not match branch '%s' (major %s). The real release would abort.\n" \
    "$YELLOW" "$RESET" "$VERSION" "$BRANCH" "$BRANCH_MAJOR" >&2
fi

TARGET="$BRANCH"
TARGET_SHA=""
if git show-ref --verify --quiet "refs/remotes/origin/${BRANCH}"; then
  TARGET_SHA=" (origin tip $(git rev-parse --short "origin/${BRANCH}"))"
  AHEAD="$(git rev-list --count "origin/${BRANCH}..HEAD" 2>/dev/null || echo 0)"
  if [[ "$AHEAD" -gt 0 ]]; then
    printf "%sNote:%s %s local commit(s) are not pushed to origin/%s and will NOT appear in this preview.\n" \
      "$YELLOW" "$RESET" "$AHEAD" "$BRANCH" >&2
  fi
else
  printf "%sNote:%s branch '%s' is not on origin; GitHub previews the pushed branch state only.\n" \
    "$YELLOW" "$RESET" "$BRANCH" >&2
fi

ARGS=(--method POST "/repos/$REPO/releases/generate-notes"
      -f "tag_name=$TAG"
      -f "target_commitish=$TARGET")
if [[ -n "$PREV" && "$PREV" != "$TAG" ]]; then
  ARGS+=(-f "previous_tag_name=$PREV")
fi

NOTES="$(gh api "${ARGS[@]}" --jq .body)"

print_header "$CHANNEL" "$VERSION" "$TAG" "$TARGET" "$TARGET_SHA" "$PREV"
style_notes "$NOTES"
printf "\n"
