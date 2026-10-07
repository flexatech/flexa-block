#!/usr/bin/env bash
# Regenerate languages/flexa-block.pot.
#
# Plain `wp i18n make-pot` over the whole plugin: the editor code is compiled to
# build/ before release, and make-pot's JS extractor reads the bundle, so no
# stub-extraction step is needed here (unlike the sibling plugins whose admin
# apps are TypeScript). node_modules/ and vendor/ are skipped by make-pot itself.
#
# Run this from a tree where build/ is current (`pnpm build`), otherwise editor
# strings added since the last bundle will be missing from the catalogue.
set -euo pipefail

PLUGIN_SLUG="flexa-block"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
POT_FILE="languages/${PLUGIN_SLUG}.pot"

cd "${ROOT_DIR}"

command -v wp >/dev/null 2>&1 || { echo "wp-cli is required" >&2; exit 1; }

mkdir -p "$(dirname "${POT_FILE}")"

# --skip-audit only silences the placeholder report; it does not change output.
wp i18n make-pot . "${POT_FILE}" \
    --domain="${PLUGIN_SLUG}" --slug="${PLUGIN_SLUG}" \
    --skip-audit

COUNT="$(grep -c '^msgid ' "${POT_FILE}")"
echo "Wrote ${POT_FILE} ($((COUNT - 1)) strings)"
