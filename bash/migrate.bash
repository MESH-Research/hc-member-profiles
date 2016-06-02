#!/bin/bash
set -ex

wp plugin deactivate --network cac-advanced-profiles
wp plugin activate --network buddypress-followers
wp plugin activate --network bp-block-member
wp plugin activate --network profile

#if [[ "$PWD" != *plugins/profile ]]
#then
#  echo "you must run this from within the plugin root directory!"
#  exit 1
#fi

wp profile friends_to_followers
