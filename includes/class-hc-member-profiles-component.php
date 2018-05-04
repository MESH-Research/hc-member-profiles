<?php
/**
 * BuddyPress component class.
 *
 * @package HC_Member_Profiles
 */

class HC_Member_Profiles_Component extends BP_Component {

	// TODO deprecate
	static $display_names;
	public $xprofile_group;
	const XPROFILE_GROUP_NAME                                    = 'MLA Commons Profile';
	const XPROFILE_GROUP_DESCRIPTION                             = 'Created and used by the MLA Commons Profile plugin.';
	const XPROFILE_FIELD_NAME_NAME                               = 'Name';
	const XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION = 'Institutional or Other Affiliation';
	const XPROFILE_FIELD_NAME_TITLE                              = 'Title';
	const XPROFILE_FIELD_NAME_SITE                               = 'Website URL';
	const XPROFILE_FIELD_NAME_TWITTER_USER_NAME                  = '<em>Twitter</em> handle';
	const XPROFILE_FIELD_NAME_FACEBOOK                           = 'Facebook URL';
	const XPROFILE_FIELD_NAME_LINKEDIN                           = 'LinkedIn URL';
	const XPROFILE_FIELD_NAME_ORCID                              = '<em>ORCID</em> iD';
	const XPROFILE_FIELD_NAME_ABOUT                              = 'About';
	const XPROFILE_FIELD_NAME_EDUCATION                          = 'Education';
	const XPROFILE_FIELD_NAME_PUBLICATIONS                       = 'Other Publications';
	const XPROFILE_FIELD_NAME_PROJECTS                           = 'Projects';
	const XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES     = 'Upcoming Talks and Conferences';
	const XPROFILE_FIELD_NAME_MEMBERSHIPS                        = 'Memberships';
	const XPROFILE_FIELD_NAME_CORE_DEPOSITS                      = 'CORE Deposits';
	const XPROFILE_FIELD_NAME_CV                                 = 'CV';

	// TODO deprecate
	public static function get_instance() {
		return buddypress()->hc_member_profiles;
	}

	/**
	 * Start the component creation process.
	 */
	public function __construct() {
		// TODO deprecate
		$this->xprofile_group = BP_XProfile_Group::get(
			[
				'profile_group_id' => 2,
				'fetch_fields'     => true,
			]
		)[0];

		self::$display_names = [
			self::XPROFILE_FIELD_NAME_NAME              => 'Name',
			self::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION => 'Institutional or Other Affiliation',
			self::XPROFILE_FIELD_NAME_TITLE             => 'Title',
			self::XPROFILE_FIELD_NAME_SITE              => 'Website URL',
			self::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => '<em>Twitter</em> handle',
			self::XPROFILE_FIELD_NAME_FACEBOOK          => 'Facebook URL',
			self::XPROFILE_FIELD_NAME_LINKEDIN          => 'LinkedIn URL',
			self::XPROFILE_FIELD_NAME_ORCID             => '<em>ORCID</em> iD',
			self::XPROFILE_FIELD_NAME_ABOUT             => 'About',
			self::XPROFILE_FIELD_NAME_EDUCATION         => 'Education',
			self::XPROFILE_FIELD_NAME_PUBLICATIONS      => 'Publications',
			self::XPROFILE_FIELD_NAME_PROJECTS          => 'Projects',
			self::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES => 'Upcoming Talks and Conferences',
			self::XPROFILE_FIELD_NAME_MEMBERSHIPS       => 'Memberships',
			self::XPROFILE_FIELD_NAME_CORE_DEPOSITS     => 'Work Shared in CORE',
			self::XPROFILE_FIELD_NAME_CV                => 'CV',
		];

		require_once dirname( __DIR__ ) . '/includes/functions.php';

		parent::start(
			'hc_member_profiles',
			_x( 'HC Member Profiles', 'Component page <title>', 'buddypress' ),
			dirname( __DIR__ ) . '/includes'
		);

		bp_register_template_stack( 'hcmp_get_template_path' );

		buddypress()->active_components[ $this->id ] = '1';
	}

	/**
	 * Add custom hooks.
	 */
	public function setup_actions() {
		add_filter( 'bp_xprofile_get_field_types', 'hcmp_register_xprofile_field_types' );
		add_filter( 'xprofile_allowed_tags', 'hcmp_filter_xprofile_allowed_tags' );
		add_action( 'wp_before_admin_bar_render', 'hcmp_filter_admin_bar' );
		// Don't log "changed their profile picture" activities.
		remove_action( 'xprofile_avatar_uploaded', 'bp_xprofile_new_avatar_activity' );

		// TODO still necessary?
		// Just need the actual value, no extra tags.
		remove_filter( 'bp_get_the_profile_field_value', 'wpautop' );

		// TODO better. too late by the time the field type class is instantiated (so not in that constructor)
		// In order to get BP to display this field without a value, include empty fields.
		add_filter(
			'bp_before_has_profile_parse_args', function( $r ) {
				return array_merge( $r, [ 'hide_empty_fields' => false ] );
			}
		);
		// TODO think unnecessary
		// add_filter( 'bp_field_has_data', '__return_true' );
		if (
			! bp_is_user_change_avatar() &&
			! bp_is_user_change_cover_image() &&
			(
				bp_is_user_profile() ||
				bp_is_user_profile_edit() ||
				bp_is_members_directory() || // TODO replace member directory functionality
				bp_is_groups_directory()
			)
		) {
			add_action( 'wp_enqueue_scripts', 'hcmp_enqueue_local_scripts' );

			// change publications field name depending on whether the user has CORE deposits
			// TODO move this somewhere more relevant - maybe core field type class
			if ( ! empty( bp_get_displayed_user_fullname() ) ) {
				$displayed_user = bp_get_displayed_user();
				$querystring    = sprintf( 'username=%s', urlencode( $displayed_user->userdata->user_login ) );
				if ( function_exists( 'humcore_has_deposits' ) && humcore_has_deposits( $querystring ) ) {
					self::$display_names[ self::XPROFILE_FIELD_NAME_PUBLICATIONS ] = 'Other Publications';
				}
			}

			add_action( 'bp_before_profile_edit_content', 'hcmp_init_profile_edit' );

			// TODO still necessary?
			// We want the full value including existing html in edit field inputs
			remove_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );

			// TODO belongs in hc-custom
			// This breaks content containing [] characters (unless they're using the feature it provides, which our data is not)
			remove_filter( 'bp_get_the_profile_field_value', 'cpfb_add_brackets', 999, 1 );
		}

	}
}
