#!/bin/bash
set -e

PLUGIN_SLUG="flexa-block"
PROJECT_PATH=$(pwd)
STAGE_PATH="/tmp/${PLUGIN_SLUG}-release"
DEST_PATH="${STAGE_PATH}/${PLUGIN_SLUG}"

echo "Building assets..."
npm run build -- --stats=errors-only

echo "Preparing release directory..."
rm -rf "$STAGE_PATH"
mkdir -p "$DEST_PATH"

echo "Syncing files..."
rsync -rc --exclude-from="${PROJECT_PATH}/.distignore" "${PROJECT_PATH}/" "${DEST_PATH}/" --delete --delete-excluded

echo "Generating zip file..."
cd "$STAGE_PATH" || exit
zip -q -r "${PLUGIN_SLUG}.zip" "${PLUGIN_SLUG}/"
mv "${PLUGIN_SLUG}.zip" "${PROJECT_PATH}/"
rm -rf "$STAGE_PATH"

echo "${PLUGIN_SLUG}.zip generated!"
