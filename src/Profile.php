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
	 * real/database names of xprofile fields used across the plugin
	 */
	const XPROFILE_FIELD_NAME_NAME = 'Name';
	const XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION = 'Institutional or Other Affiliation';
	const XPROFILE_FIELD_NAME_TITLE = 'Title';
	const XPROFILE_FIELD_NAME_SITE = 'Website URL';
	const XPROFILE_FIELD_NAME_TWITTER_USER_NAME = '<em>Twitter</em> handle';
	const XPROFILE_FIELD_NAME_FACEBOOK = 'Facebook URL';
	const XPROFILE_FIELD_NAME_LINKEDIN = 'LinkedIn URL';
	const XPROFILE_FIELD_NAME_ORCID = '<em>ORCID</em> iD';
	const XPROFILE_FIELD_NAME_ABOUT = 'About';
	const XPROFILE_FIELD_NAME_EDUCATION = 'Education';
	const XPROFILE_FIELD_NAME_PUBLICATIONS = 'Other Publications';
	const XPROFILE_FIELD_NAME_PROJECTS = 'Projects';
	const XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES = 'Upcoming Talks and Conferences';
	const XPROFILE_FIELD_NAME_MEMBERSHIPS = 'Memberships';
	const XPROFILE_FIELD_NAME_CORE_DEPOSITS = 'CORE Deposits';

	/**
	 * display names of the above fields
	 */
	public static $display_names;

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
		self::$plugin_dir = plugin_dir_path( realpath( __DIR__ ) );
		self::$plugin_templates_dir = trailingslashit( self::$plugin_dir . 'templates' );
		self::$display_names = [
			self::XPROFILE_FIELD_NAME_NAME => 'Name',
			self::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION => 'Institutional or Other Affiliation',
			self::XPROFILE_FIELD_NAME_TITLE => 'Title',
			self::XPROFILE_FIELD_NAME_SITE => 'Website URL',
			self::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => '<em>Twitter</em> handle',
			self::XPROFILE_FIELD_NAME_FACEBOOK => 'Facebook URL',
			self::XPROFILE_FIELD_NAME_LINKEDIN => 'LinkedIn URL',
			self::XPROFILE_FIELD_NAME_ORCID => '<em>ORCID</em> iD',
			self::XPROFILE_FIELD_NAME_ABOUT => 'About',
			self::XPROFILE_FIELD_NAME_EDUCATION => 'Education',
			self::XPROFILE_FIELD_NAME_PUBLICATIONS => 'Publications',
			self::XPROFILE_FIELD_NAME_PROJECTS => 'Projects',
			self::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES => 'Upcoming Talks and Conferences',
			self::XPROFILE_FIELD_NAME_MEMBERSHIPS => 'Memberships',
			self::XPROFILE_FIELD_NAME_CORE_DEPOSITS => 'Work Shared in CORE',
		];

		if( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'profile', __NAMESPACE__ . '\Profile\CLI' );
		}

		add_action( 'bp_init', [ $this, 'init' ] );
	}

	public static function get_instance() {
		return self::$instance = ( null === self::$instance ) ? new self : self::$instance;
	}

	public function init() {
		foreach ( BP_XProfile_Group::get( [ 'fetch_fields' => true ] ) as $group ) {
			if ( $group->name === self::XPROFILE_GROUP_NAME && $group->description === self::XPROFILE_GROUP_DESCRIPTION ) {
				$this->xprofile_group = $group;
				break;
			}
		}

		// change publications field name depending on whether the user has CORE deposits
		if ( ! empty( bp_get_displayed_user_fullname() ) ) {
			$querystring = sprintf( 'facets[author_facet][]=%s', urlencode( bp_get_displayed_user_fullname() ) );
			if ( function_exists( 'humcore_has_deposits' ) && humcore_has_deposits( $querystring ) ) {
				self::$display_names[ self::XPROFILE_FIELD_NAME_PUBLICATIONS ] = 'Other Publications';
			}
		}

		add_filter( 'bp_xprofile_get_field_types', [ $this, 'filter_xprofile_get_field_types' ] );

		add_filter( 'xprofile_allowed_tags', [ $this, 'filter_xprofile_allowed_tags' ] );

		add_action( 'wp_before_admin_bar_render', [ $this, 'filter_admin_bar' ] );

		// replace the default updated_profile activity handler with our own
		//remove_action( 'xprofile_updated_profile', 'bp_xprofile_updated_profile_activity', 10, 5 );
		//add_action( 'xprofile_updated_profile', [ '\MLA\Commons\Profile\Activity', 'updated_profile_activity' ], 10, 5 );

		if (
			! bp_is_user_change_avatar() &&
			! bp_is_user_change_cover_image() &&
			(
				bp_is_user_profile() ||
				bp_is_user_profile_edit() ||
				bp_is_members_directory() ||
				bp_is_groups_directory()
			)
		) {
			bp_register_template_stack( [ $this, 'register_template_stack' ], 0 );

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_local_scripts' ] );
			add_filter( 'teeny_mce_before_init', [ $this, 'filter_teeny_mce_before_init' ] );

			add_action( 'xprofile_updated_profile', [ '\MLA\Commons\Profile\Academic_Interests', 'save_academic_interests' ] );
			add_action( 'bp_before_profile_edit_content', [ $this, 'init_profile_edit' ] );
			add_action( 'bp_get_template_part', [ '\MLA\Commons\Profile\Academic_Interests', 'add_academic_interests_to_directory' ] );

			// this needs to be able to send a set-cookie header
			add_action( 'send_headers', [ '\MLA\Commons\Profile\Academic_Interests', 'set_academic_interests_cookie_query' ] );

			// we want the full value including existing html in edit field inputs
			remove_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );
		}

		// disable buddypress friends component in favor of follow/block
		$this->disable_bp_component( 'friends' );

	}

	/**
	 * register custom xprofile field types
	 */
	public function filter_xprofile_get_field_types( $field_types ) {
		require_once 'Profile/CORE_Deposits_Field_Type.php';

		$custom_field_types = [
			'core_deposits' => 'MLA\Commons\Profile\CORE_Deposits_Field_Type',
		];

		return array_merge( $field_types, $custom_field_types );
	}

	public function filter_teeny_mce_before_init( $args ) {
		/* TODO
		$js = file_get_contents( self::$plugin_dir . 'js/teeny_mce_before_init.js' );

		if ( $js ) {
			$args['setup'] = $js;
		}
		 */

		// mimick bbpress
		$args['plugins'] = 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview,wpembed,image';
		$args['toolbar1'] = "bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,tabindent,link,unlink,spellchecker,print,image,paste,undo,redo";
		$args['toolbar3'] = "tablecontrols";

		//$args['paste_as_text'] = 'true'; // turn on by default

		return $args;
	}

	public function filter_xprofile_allowed_tags( $allowed_tags ) {
		$allowed_tags['br'] = [];
		return $allowed_tags;
	}

	public function disable_bp_component( $component_name ) {
		$active_components = bp_get_option( 'bp-active-components' );

		if ( isset( $active_components[$component_name] ) ) {
			unset( $active_components[$component_name] );
			bp_update_option( 'bp-active-components', $active_components );
		}
	}

	/**
	 * scripts/styles that apply on profile & related pages only
	 */
	public function enqueue_local_scripts() {
		wp_enqueue_style( 'mla-commons-profile-local', plugins_url() . '/profile/css/profile.css' );
		wp_enqueue_script( 'mla-commons-profile-jqdmh', plugins_url() . '/profile/js/lib/jquery.dynamicmaxheight.min.js' );
		wp_enqueue_script( 'mla-commons-profile-local', plugins_url() . '/profile/js/main.js' );

		// TODO only enqueue theme-specific styles if that theme is active
		wp_enqueue_style( 'mla-commons-profile-boss', plugins_url() . '/profile/css/boss.css' );
	}

	/**
	 * initializes the profile field group loop
	 * templates do not actually use this loop, but do use variables initialized by bp_the_profile_group()
	 */
	public function init_profile_edit() {
		bp_has_profile( 'profile_group_id=' . $this->xprofile_group->id );
		bp_the_profile_group();
	}

	public function register_template_stack() {
		return self::$plugin_templates_dir;
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
