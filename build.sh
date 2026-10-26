#!/usr/bin/env bash
# Builds dist/dashwp.zip, ready for Plugins → Add New → Upload Plugin.
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p dist
rm -f dist/dashwp.zip
zip -rq -X dist/dashwp.zip dashwp -x '*.DS_Store'
echo "Built dist/dashwp.zip"
