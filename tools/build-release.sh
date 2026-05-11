#!/usr/bin/env sh
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
VERSION=$(sed -n "s/^const CMS_VERSION = '\([^']*\)';$/\1/p" "$ROOT_DIR/core/bootstrap.php")

if [ -z "$VERSION" ]; then
    echo "Could not read CMS_VERSION from core/bootstrap.php" >&2
    exit 1
fi

RELEASE_DIR="$ROOT_DIR/releases"
ARCHIVE="$RELEASE_DIR/holycross-cms-$VERSION.zip"
CHECKSUM="$ARCHIVE.sha256"

mkdir -p "$RELEASE_DIR"
rm -f "$ARCHIVE" "$CHECKSUM"

cd "$ROOT_DIR"
zip -qr "$ARCHIVE" . \
    -x ".git/*" \
    -x "config/config.php" \
    -x "releases/*" \
    -x "*.log" \
    -x ".DS_Store"

(cd "$RELEASE_DIR" && shasum -a 256 "$(basename "$ARCHIVE")" > "$(basename "$CHECKSUM")")

echo "Created $ARCHIVE"
echo "Created $CHECKSUM"
