<?php

namespace MLA\Commons;

use \BP_XProfile_Group;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \RecursiveRegexIterator;
use \RegexIterator;
use \WP_CLI;

class Profile {

	/**
	 * Used by MLA\Commons\Profile\Migration when creating the xprofile group this plugin uses.
	 * THERE CAN ONLY BE ONE GROUP WITH THIS NAME AND DESCRIPTION, OTHERWISE THIS PLUGIN WILL BE CONFUSED.
	 */
	const XPROFILE_GROUP_NAME = 'MLA Commons Profile';
	const XPROFILE_GROUP_DESCRIPTION = 'Created and used by the MLA Commons Profile plugin.';

	/**
	 * names of xprofile fields used across the plugin
	 */
	const XPROFILE_FIELD_NAME_NAME = 'Name';
	const XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION = 'Institutional or Other Affiliation';
	const XPROFILE_FIELD_NAME_TITLE = 'Title';
	const XPROFILE_FIELD_NAME_SITE = 'Site';
	const XPROFILE_FIELD_NAME_TWITTER_USER_NAME = '<em>Twitter</em> handle';
	const XPROFILE_FIELD_NAME_FACEBOOK = 'Facebook handle';
	const XPROFILE_FIELD_NAME_LINKEDIN = 'LinkedIn handle';
	const XPROFILE_FIELD_NAME_ORCID = '<em>ORCID</em> iD';
	const XPROFILE_FIELD_NAME_ABOUT = 'About';
	const XPROFILE_FIELD_NAME_EDUCATION = 'Education';
	const XPROFILE_FIELD_NAME_PUBLICATIONS = 'Publications';
	const XPROFILE_FIELD_NAME_PROJECTS = 'Projects';
	const XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES = 'Upcoming Talks and Conferences';
	const XPROFILE_FIELD_NAME_MEMBERSHIPS = 'Memberships';

	/**
	 * paths to commonly used directories
	 */
	public static $plugin_dir;
	public static $plugin_templates_dir;

	/**
	 * singleton, see get_instance()
	 */
	protected static $instance;

	/**
	 * BP_XProfile_Group object identified by XPROFILE_GROUP_NAME & XPROFILE_GROUP_DESCRIPTION
	 */
	public $xprofile_group;

	public function __construct() {
		self::$plugin_dir = \plugin_dir_path( realpath( __DIR__ ) );
		self::$plugin_templates_dir = \trailingslashit( self::$plugin_dir . 'templates' );

		if( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'profile', __NAMESPACE__ . '\Profile\CLI' );
		}

		\add_action( 'bp_init', [ $this, 'init' ] );
	}

	public static function get_instance() {
		return self::$instance = ( null === self::$instance ) ? new self : self::$instance;
	}

	public function init() {
		$current_url = ( \is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		foreach ( BP_XProfile_Group::get( [ 'fetch_fields' => true ] ) as $group ) {
			if ( $group->name === self::XPROFILE_GROUP_NAME && $group->description === self::XPROFILE_GROUP_DESCRIPTION ) {
				$this->xprofile_group = $group;
				break;
			}
		}

		// activity home view is replaced entirely by profile view
		//if ( \bp_displayed_user_domain() === $current_url ) {
		//	\bp_core_redirect( \get_option('siteurl') . '/members/' . \bp_get_displayed_user_username() . '/profile/' );
		//}


		\add_filter( 'xprofile_allowed_tags', [ $this, 'filter_xprofile_allowed_tags' ] );

		add_action( 'wp_before_admin_bar_render', [ $this, 'filter_admin_bar' ] );

		//\add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_global_scripts' ] );

		if ( ! \bp_is_user_change_avatar() && ( \bp_is_user_profile() || \bp_is_user_profile_edit() || \bp_is_members_directory() || \bp_is_groups_directory() ) ) {
			\add_filter( 'load_template', [ $this, 'filter_load_template' ] );
			\add_filter( 'query_vars', [ $this, 'filter_query_vars' ] );
		}

		if ( ! \bp_is_user_change_avatar() && ( \bp_is_user_profile() || \bp_is_user_profile_edit() ) ) {
			\add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_local_scripts' ] );
			\add_action( 'xprofile_updated_profile', [ $this, 'save_academic_interests' ] );
			\add_action( 'bp_before_profile_edit_content', [ $this, 'init_profile_edit' ] );

			// we want the full value including existing html in edit field inputs
			\remove_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );
		}

		// disable buddypress friends component in favor of follow/block
		$this->disable_bp_component( 'friends' );

	}

	function filter_query_vars( $vars ){
		$vars[] = 'academic_interests';
		return $vars;
	}

	public function filter_xprofile_allowed_tags( $allowed_tags ) {
		$allowed_tags['br'] = [];
		return $allowed_tags;
	}

	public function disable_bp_component( $component_name ) {
		$active_components = \bp_get_option( 'bp-active-components' );

		if ( isset( $active_components[$component_name] ) ) {
			unset( $active_components[$component_name] );
			\bp_update_option( 'bp-active-components', $active_components );
		}
	}

	/**
	 * scripts/styles that apply site/network-wide
	 */
	public function enqueue_global_scripts() {
		\wp_enqueue_style( 'mla-commons-profile-global', \plugins_url() . '/profile/css/site.css' );
	}

	/**
	 * scripts/styles that apply on profile & related pages only
	 */
	public function enqueue_local_scripts() {
		\wp_enqueue_style( 'mla-commons-profile-local', \plugins_url() . '/profile/css/profile.css' );
		\wp_enqueue_script( 'mla-commons-profile-local', \plugins_url() . '/profile/js/main.js' );

		// TODO only enqueue theme-specific styles if that theme is active
		\wp_enqueue_style( 'mla-commons-profile-boss', \plugins_url() . '/profile/css/boss.css' );
	}

	/**
	 * initializes the profile field group loop
	 * templates do not actually use this loop, but do use variables initialized by bp_the_profile_group()
	 */
	public function init_profile_edit() {
		bp_has_profile( 'profile_group_id=' . $this->xprofile_group->id );
		bp_the_profile_group();
	}

	public function filter_load_template( $path ) {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( self::$plugin_templates_dir ) );
		$template_files = new RegexIterator( $iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH );

		// TODO currently members/single/profile.php would match for buddypress/members/single/profile.php
		// this works okay because boss templates need to be loaded first anyway and buddypress/ will match first.
		// it would be better if matching disambiguation were not dependent on alphabetical iteration.
		foreach( $template_files as $name => $object ){
			$our_slug = str_replace( self::$plugin_templates_dir, '', $name );

			if ( strpos( $path, $our_slug ) !== false ) {
				return $name;
			}
		}

		return $path;
	}

	public function save_academic_interests( $user_id ) {
		$tax = \get_taxonomy( 'mla_academic_interests' );

		// If array add any new keywords.
		if ( is_array( $_POST['academic-interests'] ) ) {
			foreach ( $_POST['academic-interests'] as $term_id ) {
				$term_key = \wpmn_term_exists( $term_id, 'mla_academic_interests' );
				if ( empty( $term_key ) ) {
					$term_key = \wpmn_insert_term( \sanitize_text_field( $term_id ), 'mla_academic_interests' );
				}
				if ( ! \is_wp_error( $term_key ) ) {
					$term_ids[] = intval( $term_key['term_id'] );
				} else {
					error_log( '*****CAC Academic Interests Error - bad tag*****' . var_export( $term_key, true ) );
				}
			}
		}

		// Set object terms for tags.
		$term_taxonomy_ids = \wpmn_set_object_terms( $user_id, $term_ids, 'mla_academic_interests' );
		\wpmn_clean_object_term_cache( $user_id, 'mla_academic_interests' );

		// Set user meta for theme query.
		\delete_user_meta( $user_id, 'academic_interests' );
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			\add_user_meta( $user_id, 'academic_interests', $term_taxonomy_id, $unique = false );
		}
	}

	function filter_admin_bar() {
		global $wp_admin_bar;

		// Portfolio -> Profile
		foreach ( [ 'my-account-xprofile', 'my-account-settings-profile' ] as $field_id ) {
			$clone = $wp_admin_bar->get_node( $field_id );
			if ( $clone ) {
				$clone->title = 'Profile';
				$wp_admin_bar->add_menu( $clone );
			}
		}
	}
}
