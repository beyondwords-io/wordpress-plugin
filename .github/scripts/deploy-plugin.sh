#!/usr/bin/env bash

# Check we are running in GitHub Actions
if [[ -z "$GITHUB_ACTIONS" ]]; then
    echo "This script can only be run in GitHub Actions. Aborting." 1>&2
    exit 1
fi

# Check we are on a tag
if [[ -z "$GITHUB_REF_TYPE" ]] || [[ "$GITHUB_REF_TYPE" != "tag" ]]; then
    echo "Only tags are deployed to WordPress SVN. Stopping deployment." 1>&2
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

# Remove the "v" at the beginning of the git tag
SVN_TAG=${GITHUB_REF_NAME:1}

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