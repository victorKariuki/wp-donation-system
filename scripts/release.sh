#!/bin/bash

# Check if version number is provided
if [ -z "$1" ]; then
    echo "Please provide a version number (e.g. 1.0.2)"
    exit 1
fi

VERSION=$1

# Update version in main plugin file
sed -i "s/Version: .*/Version: $VERSION/" wp-donation-system.php
sed -i "s/define('WP_DONATION_SYSTEM_VERSION', '.*'/define('WP_DONATION_SYSTEM_VERSION', '$VERSION'/" wp-donation-system.php

# Add and commit version changes
git add wp-donation-system.php
git commit -m "Bump version to $VERSION"

# Create and push tag
git tag -a "v$VERSION" -m "Release version $VERSION"
git push origin main "v$VERSION"

echo "Release $VERSION initiated. Check GitHub Actions for progress." 