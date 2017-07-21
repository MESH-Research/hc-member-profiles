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

	/**
	 * @return null
	 */
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

		add_action( 'bp_init', [ $this, 'init' ] );
	}

	/**
	 * Singleton factory
	 *
	 * @return Profile
	 */
	public static function get_instance() {
		return self::$instance = ( null === self::$instance ) ? new self : self::$instance;
	}

	/**
	 * Set up actions and filters.
	 *
	 * @return null
	 */
	public function init() {
		foreach ( BP_XProfile_Group::get( [ 'fetch_fields' => true ] ) as $group ) {
			if ( $group->name === self::XPROFILE_GROUP_NAME && $group->description === self::XPROFILE_GROUP_DESCRIPTION ) {
				$this->xprofile_group = $group;
				break;
			}
		}

		if( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'profile', __NAMESPACE__ . '\CLI' );
		}

/*
		// TODO make this more configurable & optional, maybe use admin tool menu api
		if ( ! $this->xprofile_group ) {
			$migration = new Migration;
			$migration->create_xprofile_group();
			$migration->create_xprofile_fields();
		}
*/

		// change publications field name depending on whether the user has CORE deposits
		if ( ! empty( bp_get_displayed_user_fullname() ) ) {
			$displayed_user = bp_get_displayed_user();
			$querystring = sprintf( 'username=%s', urlencode( $displayed_user->userdata->user_login ) );
			if ( function_exists( 'humcore_has_deposits' ) && humcore_has_deposits( $querystring ) ) {
				self::$display_names[ self::XPROFILE_FIELD_NAME_PUBLICATIONS ] = 'Other Publications';
			}
		}

		add_filter( 'bp_xprofile_get_field_types', [ $this, 'filter_xprofile_get_field_types' ] );
		add_filter( 'xprofile_allowed_tags', [ new Template, 'filter_xprofile_allowed_tags' ] );
		add_action( 'wp_before_admin_bar_render', [ new Template, 'filter_admin_bar' ] );
		//add_action( bp_core_admin_hook(), [ new Admin, 'add_admin_menu' ] );

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
			bp_register_template_stack( [ new Template, 'register_template_stack' ], 0 );

			add_action( 'wp_enqueue_scripts', [ new Template, 'enqueue_local_scripts' ] );
			add_filter( 'teeny_mce_before_init', [ new Template, 'filter_teeny_mce_before_init' ] );

			// we want the full value including existing html in edit field inputs
			remove_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );

			// this breaks content containing [] characters (unless they're using the feature it provides, which we'll assume is not the case)
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
			$field_types['activity'] = __NAMESPACE__ . '\Activity_Field_Type';
		}

		if ( bp_is_active( 'blogs' ) ) {
			$field_types['blogs'] = __NAMESPACE__ . '\Blogs_Field_Type';
		}

		if ( bp_is_active( 'groups' ) ) {
			$field_types['groups'] = __NAMESPACE__ . '\Groups_Field_Type';
		}

		if ( bp_is_active( 'humcore_deposits' ) ) {
			// TODO identifier in hc db, must change there before updating here - but ideally should match component name
			// which is to say, core_deposits -> humcore_deposits
			$field_types['core_deposits'] = __NAMESPACE__ . '\CORE_Deposits_Field_Type';
		}

		if ( class_exists( 'Mla_Academic_Interests' ) ) {
			$field_types['academic_interests'] = __NAMESPACE__ . '\Academic_Interests_Field_Type';
		}

		return $field_types;
	}

}
