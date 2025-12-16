#!/bin/bash

# Exit on any error
set -e

echo "🚀 Starting WooSuite AI Build Process..."

# 1. Install Dependencies
echo "📦 Installing Node dependencies..."
npm install

# 2. Build React App
echo "🏗️  Building React application..."
npm run build

# 3. Create Zip File
echo "🤐 Creating Release Zip..."
ZIP_NAME="woosuite-ai.zip"

# Remove existing zip if it exists
if [ -f "$ZIP_NAME" ]; then
    rm "$ZIP_NAME"
fi

# Zip command excluding dev files
# We include:
# - woosuite-ai.php (Main file)
# - includes/ (PHP Classes)
# - assets/ (Compiled JS/CSS)
# - vendor/ (If PHP deps exist - currently none, but good practice)
# - LICENSE, README.md, workflow.md
zip -r "$ZIP_NAME" \
    woosuite-ai.php \
    includes \
    assets \
    LICENSE \
    README.md \
    workflow.md \
    -x "*.DS_Store" \
    -x "__MACOSX" \
    -x "node_modules/*" \
    -x "src/*" \
    -x ".git/*" \
    -x ".gitignore" \
    -x "tsconfig.json" \
    -x "vite.config.ts" \
    -x "tailwind.config.js" \
    -x "postcss.config.js" \
    -x "package.json" \
    -x "package-lock.json"

echo "✅ Build Complete!"
echo "📂 Zip file created: $ZIP_NAME"
echo "👉 Download this file and upload it to your WordPress Plugins page."
