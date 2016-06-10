<?php

namespace MLA\Commons;

use \BP_Component;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

class Profile extends BP_Component {
	protected static $instance;

	public $plugin_dir;
	public $plugin_templates_dir;
	public $template_files;

	public function __construct() {
		$this->plugin_dir = plugin_dir_path( __DIR__ . '/../..' );
		$this->plugin_templates_dir = trailingslashit( $this->plugin_dir . 'templates' );
		$this->template_files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $this->plugin_templates_dir ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		add_action( 'bp_init', [ $this, 'init' ] );
	}

	public static function get_instance() {
		return self::$instance = ( null === self::$instance ) ? new self : self::$instance;
	}

	/**
	 * TODO check if required plugins are active & throw warning or bail if not: follow, block
	 */
	public function init() {
		if ( ! bp_is_user_change_avatar() && ( bp_is_user_profile() || bp_is_user_profile_edit() ) ) {
			add_filter( 'load_template', [ $this, 'filter_load_template' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'xprofile_updated_profile', [ $this, 'save_academic_interests' ] );
		}

		// disable buddypress friends component in favor of follow/block
		$this->disable_bp_component( 'friends' );
	}

	public function disable_bp_component( $component_name ) {
		$active_components = \bp_get_option( 'bp-active-components' );

		if ( in_array( $component_name, array_keys( $active_components ) ) ) {
			unset( $active_components[$component_name] );
			bp_update_option( 'bp-active-components', $active_components );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'mla_commons_profile_main_css', plugins_url() . '/profile/css/main.css' );
		wp_enqueue_script( 'mla_commons_profile_main_js', plugins_url() . '/profile/js/main.js' );
	}

	public function filter_load_template( $path ) {
		$their_slug = str_replace( trailingslashit( STYLESHEETPATH ), '', $path );

		foreach( $this->template_files as $name => $object ){
			$our_slug = str_replace( $this->plugin_templates_dir, '', $name );

			if ( $our_slug === $their_slug ) {
				return $name;
			}
		}

		return $path;
	}

}
