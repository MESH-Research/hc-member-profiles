# Academic Member Profiles for BuddyPress

[![Build Status](https://travis-ci.org/mlaa/profile.svg)](https://travis-ci.org/mlaa/profile)

Inspired by (and not compatible with) [CAC Advanced Profiles](https://github.com/cuny-academic-commons/cac-advanced-profiles).

Built for [Humanities Commons](https://hcommons.org).


## Required Dependencies

[BuddyPress](https://buddypress.org) `xprofile`, `activity`, `blogs`, `groups`, and `members` components must be enabled.

You must also enable [Multisite](https://codex.wordpress.org/Create_A_Network) in WordPress.


## Optional Dependencies

[BuddyPress Follow](https://wordpress.org/plugins/buddypress-followers) enables displaying follower count on profiles. (Humanities Commons uses [BuddyBlock](http://www.philopress.com/products/buddyblock) to complement BuddyPress Follow but it changes nothing about how this plugin works.)

[MLA Academic Interests](https://github.com/mlaa/mla-academic-interests) enables the "Academic Interests" xprofile field.

[HumCORE](https://github.com/mlaa/humcore) enables the "CORE Deposits" xprofile field.


## Installation

Modify the included migration script to fit your existing data and then run it before using this plugin:

    wp profile migrate
