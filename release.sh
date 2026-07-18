#!/bin/bash
set -e

PLUGIN_SLUG="flexa-block"
PROJECT_PATH=$(pwd)
STAGE_PATH="/tmp/${PLUGIN_SLUG}-release"
DEST_PATH="${STAGE_PATH}/${PLUGIN_SLUG}"

PLUGIN_VERSION=$(grep -iE "^\s*\*\s*Version:" "${PROJECT_PATH}/${PLUGIN_SLUG}.php" | head -1 | sed -E 's/.*Version:\s*//' | tr -d '[:space:]')
ZIP_NAME="${PLUGIN_SLUG}-${PLUGIN_VERSION}.zip"

if [ ! -d "${PROJECT_PATH}/node_modules" ]; then
  echo "Installing dependencies..."
  npm ci
fi

echo "Building assets..."
npm run build -- --stats=errors-only

echo "Preparing release directory..."
rm -rf "$STAGE_PATH"
mkdir -p "$DEST_PATH"

echo "Syncing files..."
rsync -rc --exclude-from="${PROJECT_PATH}/.distignore" "${PROJECT_PATH}/" "${DEST_PATH}/" --delete --delete-excluded

echo "Generating zip file..."
cd "$STAGE_PATH" || exit
zip -q -r "${ZIP_NAME}" "${PLUGIN_SLUG}/"
mv "${ZIP_NAME}" "${PROJECT_PATH}/"
rm -rf "$STAGE_PATH"

echo "${ZIP_NAME} generated!"