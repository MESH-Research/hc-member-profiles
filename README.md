# Academic Member Profiles for BuddyPress

[![Build Status](https://travis-ci.org/mlaa/profile.svg)](https://travis-ci.org/mlaa/profile)

Inspired by (and incompatible with) [CAC Advanced Profiles](https://github.com/cuny-academic-commons/cac-advanced-profiles).

Built for [Humanities Commons](https://hcommons.org).


## Required Dependencies

[BuddyPress](https://buddypress.org) `xprofile`, `activity`, and `groups` components must be enabled.


## Optional Dependencies

The BuddyPress `blogs` component enables the "Sites" xprofile field.

[BuddyPress Follow](https://wordpress.org/plugins/buddypress-followers) enables displaying follower count on profiles. (Humanities Commons uses [BuddyBlock](http://www.philopress.com/products/buddyblock) to complement BuddyPress Follow but it changes nothing about how this plugin works.)

[MLA Academic Interests](https://github.com/mlaa/mla-academic-interests) enables the "Academic Interests" xprofile field.

[HumCORE](https://github.com/mlaa/humcore) enables the "CORE Deposits" xprofile field.


## Installation

Modify the included migration script to fit your existing data and then run it before using this plugin:

    wp profile migrate
