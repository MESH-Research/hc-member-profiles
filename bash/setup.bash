#!/bin/bash
set -ex

wp plugin deactivate --network cac-advanced-profiles
wp plugin activate --network buddypress-followers
wp plugin activate --network bp-block-member
wp plugin activate --network profile
