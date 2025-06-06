#!/bin/bash

set -x

# Usage: ./run-playwright.sh [url] [grep]
BASE_URL=$1
GREP=${2:-@(user|ATK-PW-1000|ATK-PY-1012|ATK-PW-1030)}

# cd to project root (one level up from web/ or scripts/)
cd ..

# rewrite Playwright configs, they are slightly different
# from the ones created by recipe
cp config_playwright/* .

npx playwright install chromium --with-deps
npx playwright test -g "$GREP"

