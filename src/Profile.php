<?php

namespace MLA\Commons;

use \BP_Component;

class Profile extends BP_Component {
	protected static $instance;

	public static function init() {
		add_action('plugins_loaded', array(__CLASS__, 'loaded'));
	}

	public static function loaded() {
	}
}
