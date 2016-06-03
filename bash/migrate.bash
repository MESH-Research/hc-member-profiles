#!/bin/bash
set -ex

pre_php=/tmp/__pre.php
[[ -e "$pre_php" ]] || echo "<?php error_reporting( 0 ); define( 'WP_DEBUG', false );" > "$pre_php"

wp="wp --path=/srv/www/commons/current/web/wp --require=$pre_php"

$wp plugin deactivate --network cac-advanced-profiles
$wp plugin activate --network buddypress-followers
$wp plugin activate --network bp-block-member
$wp plugin activate --network profile

#if [[ "$PWD" != *plugins/profile ]]
#then
#  echo "you must run this from within the plugin root directory!"
#  exit 1
#fi

$wp profile friends_to_followers
$wp profile create_xprofile_fields
$wp profile migrate_xprofile_field_data
