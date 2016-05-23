#!/bin/bash
set -ex

wp plugin deactivate --network cac-advanced-profiles
wp plugin activate --network profile
wp plugin activate --network buddypress-followers
