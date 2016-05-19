<?php

namespace MLA\Commons;

use \BP_Component;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

class Profile extends BP_Component {
	protected static $instance;

	private static $plugin_dir;
	private static $plugin_templates_dir;
	private static $template_files;

	public function __construct() {
		self::$plugin_dir = trailingslashit( dirname( __DIR__, 1 ) );
		self::$plugin_templates_dir = trailingslashit( self::$plugin_dir . 'templates' );
		self::$template_files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( self::$plugin_templates_dir ), RecursiveIteratorIterator::SELF_FIRST );

		add_filter( 'load_template', [ $this, 'filter_load_template' ] );
	}

	public static function get_instance() {
		return self::$instance = ( null === self::$instance ) ? new self : self::$instance;
	}

	public function filter_load_template( $path ) {
		$their_slug = str_replace( trailingslashit( STYLESHEETPATH ), '', $path );

		foreach( self::$template_files as $name => $object ){
			$our_slug = str_replace( self::$plugin_templates_dir, '', $name );

			if ( $our_slug === $their_slug ) {
				return $name;
			}
		}

		return $path;
	}
}
