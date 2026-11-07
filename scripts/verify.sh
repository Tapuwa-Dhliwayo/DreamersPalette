#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
verification_dir=$(mktemp -d "${TMPDIR:-/tmp}/dreamers-verify.XXXXXX")
trap 'rm -rf "$verification_dir"' EXIT

composer validate --no-check-publish
php scripts/check-php-references.php
while IFS= read -r -d '' source_file; do
    php -l "$source_file" > /dev/null
done < <(find app bootstrap config database routes scripts -name '*.php' -not -path 'bootstrap/cache/*' -print0)
vendor/bin/pint --test
npm run lint
php artisan route:list --json > "$verification_dir/routes.json"
VIEW_COMPILED_PATH="$verification_dir/views" php artisan view:cache
npm run build -- --outDir "$verification_dir/build"
git diff --check
printf '\nVerification passed: class references, PHP syntax, formatting, lint, routes, Blade compilation and frontend build.\n'
printf 'Before handoff, run the changed user actions and inspect fresh logs; this command does not exercise database or provider workflows.\n'
