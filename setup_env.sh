#!/bin/bash

# Setup script for WooSuite AI development environment
# Usage: bash setup_env.sh

echo "🚀 Setting up WooSuite AI environment..."

# 1. Check for package.json
if [ ! -f "package.json" ]; then
    echo "❌ Error: package.json not found. Are you in the root of the repo?"
    exit 1
fi

# 2. Install Node.js dependencies
echo "📦 Installing npm dependencies..."
npm install

# 3. Build the frontend assets
echo "🏗️  Building frontend assets..."
npm run build

# 4. Verify assets exist
if [ -f "assets/woosuite-app.js" ]; then
    echo "✅ Build successful. Assets generated in assets/."
else
    echo "❌ Error: Build failed. Assets not found."
    exit 1
fi

echo "🎉 Environment setup complete! You are ready to work."
