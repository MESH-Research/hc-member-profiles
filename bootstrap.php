<?php
/**
 * Plugin Name: MLA Commons Profile
 */

namespace MLA\Commons;

require_once 'autoload.php';

// initialize actions & filters etc. by instantiating
Profile::get_instance();

// only for dev & migration for now: load manually because this class is never used
new ProfileCLI;
