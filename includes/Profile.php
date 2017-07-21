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
	 * Used by MLA\Commons\Migration when creating the xprofile group this plugin uses.
	 * THERE CAN ONLY BE ONE GROUP WITH THIS NAME AND DESCRIPTION, OTHERWISE THIS PLUGIN WILL BE CONFUSED.
	 */
	const XPROFILE_GROUP_NAME = 'MLA Commons Profile';
	const XPROFILE_GROUP_DESCRIPTION = 'Created and used by the MLA Commons Profile plugin.';

	/**
	 * real/database names of xprofile fields used across the plugin
	 * TODO translatable
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
	const XPROFILE_FIELD_NAME_ACADEMIC_INTERESTS = 'Academic Interests';
	const XPROFILE_FIELD_NAME_GROUPS = 'Commons Groups';
	const XPROFILE_FIELD_NAME_ACTIVITY = 'Recent Commons Activity';
	const XPROFILE_FIELD_NAME_BLOGS = 'Commons Sites';

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
			$displayed_user = bp_get_displayed_user();
			$querystring = sprintf( 'username=%s', urlencode( $displayed_user->userdata->user_login ) );
			if ( function_exists( 'humcore_has_deposits' ) && humcore_has_deposits( $querystring ) ) {
				self::$display_names[ self::XPROFILE_FIELD_NAME_PUBLICATIONS ] = 'Other Publications';
			}
		}

		add_action( bp_core_admin_hook(), [ new Admin, 'add_admin_menu' ] );

		add_filter( 'bp_xprofile_get_field_types', [ $this, 'filter_xprofile_get_field_types' ] );

		add_filter( 'xprofile_allowed_tags', [ $this, 'filter_xprofile_allowed_tags' ] );

		add_action( 'wp_before_admin_bar_render', [ $this, 'filter_admin_bar' ] );

		remove_filter( 'bp_get_the_profile_field_value', 'wpautop' ); // just need the actual value, no extra tags

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

			add_action( 'bp_before_profile_edit_content', [ $this, 'init_profile_edit' ] );

			// TODO make part of field
			if ( class_exists( 'Mla_Academic_Interests' ) ) {
				add_action( 'bp_get_template_part', [ '\MLA\Commons\Academic_Interests', 'add_academic_interests_to_directory' ] );
				add_action( 'xprofile_updated_profile', [ '\MLA\Commons\Academic_Interests', 'save_academic_interests' ] );
				// this needs to be able to send a set-cookie header
				add_action( 'send_headers', [ '\MLA\Commons\Academic_Interests', 'set_academic_interests_cookie_query' ] );
			}

			// we want the full value including existing html in edit field inputs
			remove_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );

			// this breaks content containing [] characters (unless they're using the feature it provides, which our data is not)
			remove_filter( 'bp_get_the_profile_field_value', 'cpfb_add_brackets', 999, 1 );
		}
	}

	/**
	 * Register custom xprofile field types.
	 *
	 * @return array
	 */
	public function filter_xprofile_get_field_types( array $field_types ) {
		if ( bp_is_active( 'activity' ) ) {
			$field_types['activity'] = __NAMESPACE__ . '\Profile\Activity_Field_Type';
		}

		if ( bp_is_active( 'blogs' ) ) {
			$field_types['blogs'] = __NAMESPACE__ . '\Profile\Blogs_Field_Type';
		}

		if ( bp_is_active( 'groups' ) ) {
			$field_types['groups'] = __NAMESPACE__ . '\Profile\Groups_Field_Type';
		}

		if ( bp_is_active( 'humcore_deposits' ) ) {
			// TODO identifier in hc db, must change there before updating here - but ideally should match component name
			$field_types['core_deposits'] = __NAMESPACE__ . '\Profile\CORE_Deposits_Field_Type';
		}

		if ( class_exists( 'Mla_Academic_Interests' ) ) {
			$field_types['academic_interests'] = __NAMESPACE__ . '\Profile\Academic_Interests_Field_Type';
		}

		return $field_types;
	}

	// TODO move
	public function filter_teeny_mce_before_init( $args ) {
		/* TODO
		$js = file_get_contents( self::$plugin_dir . 'js/teeny_mce_before_init.js' );

		if ( $js ) {
			$args['setup'] = $js;
		}
		 */

		// mimick bbpress
		$args['plugins'] = 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview,wpembed,image';
		$args['toolbar1'] = "bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,tabindent,link,unlink,spellchecker,print,paste,undo,redo";
		$args['toolbar3'] = "tablecontrols";

		//$args['paste_as_text'] = 'true'; // turn on by default

		return $args;
	}

	// TODO move
	public function filter_xprofile_allowed_tags( $allowed_tags ) {
		$allowed_tags['br'] = [];
		$allowed_tags['ul'] = [];
		$allowed_tags['li'] = [];
		return $allowed_tags;
	}

	// TODO get rid of this. ensure component is off in hc db
	public function disable_bp_component( $component_name ) {
		$active_components = bp_get_option( 'bp-active-components' );

		if ( isset( $active_components[$component_name] ) ) {
			unset( $active_components[$component_name] );
			bp_update_option( 'bp-active-components', $active_components );
		}
	}

	/**
	 * scripts/styles that apply on profile & related pages only
	 * TODO move
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
