#!/bin/bash

set -x

# Usage: ./run-playwright.sh [url] [grep]
BASE_URL=$1
GREP=${2:-@(contact-us|media|taxonomy|user|menu|register-login)}

# cd to project root (one level up from web/ or scripts/)
cd ..

# rewrite Playwright configs, they are slightly different
# from the ones created by recipe
cp config_playwright/* .

npx playwright install chromium --with-deps
npx playwright test -g "$GREP"

