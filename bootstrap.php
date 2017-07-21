<?php
/**
 * Plugin Name: MLA Commons Profile
 */

namespace MLA\Commons;

require_once 'autoload.php';

// initialize actions & filters etc. by instantiating
Profile::get_instance();

// since Profile hooks into bp_init, add this separately since this action runs before that
//add_action( 'xprofile_register_activity_actions', [ '\MLA\Commons\Activity', 'register_activity_actions' ] );
