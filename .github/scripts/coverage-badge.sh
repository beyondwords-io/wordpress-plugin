#!/usr/bin/env bash

# Check we are running in GitHub Actions
if [[ -z "$GITHUB_ACTIONS" ]]; then
    echo "This script can only be run in GitHub Actions. Aborting." 1>&2
    exit 1
fi

# Check we are on main branch
if [[ -z "$GITHUB_REF_NAME" ]] || [[ "$GITHUB_REF_NAME" != "main" ]]; then
    echo "Branch is required and must be 'main' branch. Stopping badge generation." 1>&2
    exit 0
fi

BADGES_DIR="/tmp/badges"

COVERAGE_PERCENT="$1"

# BADGE_URL="https://img.shields.io/badge/Coverage-$COVERAGE_PERCENT%25-blue"
BADGE_URL="https://img.shields.io/badge/Coverage-60.95%25-blue"

# Check if the latest SVN tag exists already
TAG=$(svn ls "https://plugins.svn.wordpress.org/$WP_ORG_PLUGIN_NAME/tags/$SVN_TAG")
error=$?
if [ $error == 0 ]; then
    # Tag exists, don't deploy
    echo "Tag ($SVN_TAG) already exists on the WordPress directory. No deployment needed!"
    exit 0
fi

# Checkout the SVN repo
svn co -q "http://svn.wp-plugins.org/$WP_ORG_PLUGIN_NAME" $PLUGIN_SVN_PATH

# Delete the trunk directory
rm -rf $PLUGIN_SVN_PATH/trunk

# Copy our new version of the plugin as the new trunk directory
cp -r /tmp/$WP_ORG_PLUGIN_NAME $PLUGIN_SVN_PATH/trunk

# Copy our new version of the plugin into new version tag directory
cp -r /tmp/$WP_ORG_PLUGIN_NAME $PLUGIN_SVN_PATH/tags/$SVN_TAG

# Move into SVN directory
cd $PLUGIN_SVN_PATH

# Add new files to SVN
svn stat | grep '^?' | awk '{print $2}' | xargs -I x svn add x@

# Remove deleted files from SVN
svn stat | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@

# Debugging
pwd
ls -la $PLUGIN_SVN_PATH/trunk

# Commit to SVN
svn ci --no-auth-cache --username $WP_ORG_USERNAME --password $WP_ORG_PASSWORD -m "Deploy version $SVN_TAG"