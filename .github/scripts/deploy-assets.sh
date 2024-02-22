#!/usr/bin/env bash

# Check we are running in GitHub Actions
if [[ -z "$GITHUB_ACTIONS" ]]; then
    echo "This script can only be run in GitHub Actions. Aborting." 1>&2
    exit 1
fi

# Check we are on main branch
if [[ -z "$GITHUB_REF_NAME" ]] || [[ "$GITHUB_REF_NAME" != "main" ]]; then
    echo "Build branch is required and must be 'main' branch. Stopping deployment." 1>&2
    exit 0
fi

if [[ -z "$WP_ORG_PASSWORD" ]]; then
    echo "WordPress.org password not set. Aborting." 1>&2
    exit 1
fi

if [[ -z "$WP_ORG_PLUGIN_NAME" ]]; then
    echo "WordPress.org plugin name not set. Aborting." 1>&2
    exit 1
fi

if [[ -z "$WP_ORG_USERNAME" ]]; then
    echo "WordPress.org username not set. Aborting." 1>&2
    exit 1
fi

PLUGIN_SVN_PATH="/tmp/svn"

# Checkout the SVN repo
svn co -q "http://svn.wp-plugins.org/$WP_ORG_PLUGIN_NAME" $PLUGIN_SVN_PATH

# Delete the wordpress.org assets directory
rm -rf $PLUGIN_SVN_PATH/.wordpress-org

# Copy our plugin assets as the new assets directory
cp -r ./.wordpress-org $PLUGIN_SVN_PATH/.wordpress-org

# Move into SVN directory
cd $PLUGIN_SVN_PATH

# Add new files to SVN
svn stat | grep '^?' | awk '{print $2}' | xargs -I x svn add x@

# Remove deleted files from SVN
svn stat | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@

# Debugging
ls -la $PLUGIN_SVN_PATH/.wordpress-org

# Commit to SVN
svn ci --no-auth-cache --username $WP_ORG_USERNAME --password $WP_ORG_PASSWORD -m "Deploy wordpress.org assets"
