#!/usr/bin/env bash
#
# Build the installable plugin zip from an explicit allowlist, then verify the
# artifact it just built.
#
#   bash bin/build-zip.sh [output-dir]      # default output-dir: parent of the repo
#
# ALLOWLIST, NOT DENYLIST
# A new development directory is excluded because nobody added it here, rather
# than shipped because nobody remembered to exclude it.
#
# WHAT ACTUALLY ENFORCES THIS
# The foundation's shared CI checks what would ship on every pull request, and
# again against the built artifact at release time. That is the enforcing copy.
# This script is for building a zip by hand — a site's first install, before the
# release workflow has ever run. If the two disagree, the foundation is right.
#
# WHY NOT Compress-Archive / GNU tar
# PowerShell's Compress-Archive writes backslash entry paths on Windows, and
# WordPress (Linux) then reports "Plugin file does not exist." on activate. GNU
# tar cannot write zip format at all. This script insists on a tool that writes
# correct forward-slash entries, and proves it afterwards.

set -euo pipefail

SLUG="blueworx-labs-deck-builder"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="${1:-$(cd "$ROOT/.." && pwd)}"

# --- the allowlist: what ships ------------------------------------------------
INCLUDE=(
	"$SLUG.php"
	"uninstall.php"
	"CHANGELOG.md"
	"README.md"
	"includes"
	"assets"
	# Vendored, and required unguarded by the main plugin file — a zip without
	# it fatals the moment WordPress activates the plugin. The "everything the
	# plugin requires is in the zip" check below is what catches the next one.
	"plugin-update-checker"
)

# Belt and braces. The allowlist already excludes these, so a hit here means one
# is nested inside a shipped directory — exactly the case a human misses.
FORBIDDEN_SEGMENTS=( "tests" "docs" "preview" "node_modules" ".claude" ".github" ".git" ".superpowers" ".wp-test" )
FORBIDDEN_FILES=( "*.spec.js" "phpunit.xml*" "phpcs.xml*" "composer.json" "composer.lock" "package.json" "package-lock.json" "approved-deps.json" "playwright.config.js" "CLAUDE.md" ".gitignore" ".env" "*.zip" )

say() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

# --- pick an archiver that writes real zip entries with forward slashes --------
ZIP_TOOL=""
for candidate in "/c/Windows/System32/tar.exe" "$(command -v bsdtar || true)" "$(command -v tar || true)"; do
	[ -n "$candidate" ] && [ -x "$candidate" ] || continue
	if "$candidate" --version 2>&1 | grep -qi 'bsdtar\|libarchive'; then
		ZIP_TOOL="bsdtar:$candidate"
		break
	fi
done
if [ -z "$ZIP_TOOL" ] && command -v zip >/dev/null 2>&1; then
	# Info-ZIP on Linux/CI: GNU tar cannot write zip, but zip(1) can, and writes
	# forward slashes natively.
	ZIP_TOOL="zip:$(command -v zip)"
fi
[ -n "$ZIP_TOOL" ] || die "no zip-capable archiver found (need bsdtar or zip; GNU tar cannot write zip)"

TOOL_KIND="${ZIP_TOOL%%:*}"
TOOL_BIN="${ZIP_TOOL#*:}"
say "Archiver : $TOOL_KIND ($TOOL_BIN)"

# The header is what a site compares against, so the header is what names the
# file. Reading the constant instead would let the two drift unnoticed.
VERSION="$(sed -n 's/^ \* Version:[[:space:]]*\([0-9][0-9.]*\).*$/\1/p' "$ROOT/$SLUG.php" | head -1)"
[ -n "$VERSION" ] || die "could not read the Version: header from $SLUG.php"
CONST_VERSION="$(sed -n "s/.*BLUEWORX_DECK_BUILDER_VERSION', '\([0-9][0-9.]*\)'.*/\1/p" "$ROOT/$SLUG.php" | head -1)"
[ "$VERSION" = "$CONST_VERSION" ] || die "the Version: header ($VERSION) and BLUEWORX_DECK_BUILDER_VERSION ($CONST_VERSION) disagree"
say "Version  : $VERSION"

# The version lives in the FILENAME only. The folder inside the archive stays
# "$SLUG/" — WordPress identifies a plugin by that folder name, so versioning it
# would install every release as a separate plugin.
ZIP="$OUT_DIR/$SLUG-$VERSION.zip"

# --- stage --------------------------------------------------------------------
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$STAGE/$SLUG"
for item in "${INCLUDE[@]}"; do
	[ -e "$ROOT/$item" ] || die "allowlisted path is missing from the repo: $item"
	cp -R "$ROOT/$item" "$STAGE/$SLUG/"
done

# --- build --------------------------------------------------------------------
mkdir -p "$OUT_DIR"
# Exactly one zip per plugin is ever present: an older build left beside the new
# one is how the wrong version reaches a live site.
rm -f "$OUT_DIR/$SLUG.zip" "$OUT_DIR"/"$SLUG"-*.zip
case "$TOOL_KIND" in
	bsdtar) ( cd "$STAGE" && "$TOOL_BIN" -a -c -f "$ZIP" "$SLUG" ) ;;
	zip)    ( cd "$STAGE" && "$TOOL_BIN" -q -r -X "$ZIP" "$SLUG" ) ;;
esac
[ -f "$ZIP" ] || die "no zip was produced at $ZIP"

# --- verify the artifact, not the intent --------------------------------------
if command -v unzip >/dev/null 2>&1; then
	ENTRIES="$(unzip -Z1 "$ZIP")"
elif [ "$TOOL_KIND" = "bsdtar" ]; then
	ENTRIES="$("$TOOL_BIN" -tf "$ZIP")"
else
	die "need unzip (or bsdtar) to list the zip — refusing to ship an unverified artifact"
fi

fail=0
check() { # check <description> <offending-entries>
	if [ -n "$2" ]; then
		printf 'FAIL: %s\n%s\n' "$1" "$(printf '%s\n' "$2" | sed 's/^/    /')" >&2
		fail=1
	else
		say "  ok: $1"
	fi
}

say "Verifying $ZIP"

# A backslash entry mis-extracts on a Linux host, and WordPress then reports
# "Plugin file does not exist." on activate. This is the Compress-Archive bug.
check "every entry uses forward slashes" "$(printf '%s\n' "$ENTRIES" | grep -F '\' || true)"
check "every entry is nested under $SLUG/" "$(printf '%s\n' "$ENTRIES" | grep -vE "^$SLUG/" || true)"

offenders=""
for seg in "${FORBIDDEN_SEGMENTS[@]}"; do
	hit="$(printf '%s\n' "$ENTRIES" | grep -E "(^|/)$(printf '%s' "$seg" | sed 's/\./\./g')(/|$)" || true)"
	[ -n "$hit" ] && offenders="$offenders$hit"$'\n'
done
check "no development directories ship" "$(printf '%s' "$offenders" | sed '/^$/d')"

# Composer's own vendor tree, which is dev-only here. Checked at the top level
# only: plugin-update-checker ships a vendor/ of its own that it loads at
# runtime (Parsedown), so a blanket "vendor" segment ban would reject a
# perfectly good zip.
check "Composer's vendor tree does not ship" "$(printf '%s\n' "$ENTRIES" | grep -E "^$SLUG/vendor(/|$)" || true)"

offenders=""
for pat in "${FORBIDDEN_FILES[@]}"; do
	hit="$(printf '%s\n' "$ENTRIES" | grep -E "(^|/)${pat//\*/[^/]*}$" || true)"
	[ -n "$hit" ] && offenders="$offenders$hit"$'\n'
done
check "no development files ship" "$(printf '%s' "$offenders" | sed '/^$/d')"

check "the main plugin file sits directly inside $SLUG/" \
	"$(printf '%s\n' "$ENTRIES" | grep -qxF "$SLUG/$SLUG.php" && true || echo "missing $SLUG/$SLUG.php")"

# The allowlist above is a list of names, and a name is easy to forget. This
# reads what the plugin actually requires on boot and insists the zip carries
# it, so the next vendored directory is caught here rather than by a live site
# fatalling on activate.
offenders=""
while IFS= read -r required; do
	[ -n "$required" ] || continue
	printf '%s\n' "$ENTRIES" | grep -qxF "$SLUG/$required" || offenders="$offenders$required"$'\n'
done <<EOF
$(grep -oE "require(_once)? +(__DIR__|BLUEWORX_DECK_BUILDER_DIR) *\.? *'/?[^']+'" "$ROOT/$SLUG.php" \
	| grep -oE "'/?[^']+'" | tr -d "'" | sed 's|^/||')
EOF
check "everything the plugin requires on boot is in the zip" "$(printf '%s' "$offenders" | sed '/^$/d')"

[ "$fail" -eq 0 ] || die "the zip is not shippable — see the failures above"

say ""
say "Built $ZIP ($SLUG $VERSION, $(printf '%s\n' "$ENTRIES" | grep -c . ) entries)"
