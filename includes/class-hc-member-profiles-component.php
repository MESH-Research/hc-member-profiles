<?php
/**
 * BuddyPress component class.
 *
 * @package Hc_Member_Profiles
 */

/**
 * Component class.
 */
class HC_Member_Profiles_Component extends BP_Component {

	/**
	 * TODO deprecate.
	 *
	 * @var array
	 */
	public static $display_names;

	/**
	 * TODO deprecate.
	 *
	 * @var array
	 */
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
	const XPROFILE_FIELD_NAME_ACADEMIC_INTERESTS                 = 'Academic Interests';
	const XPROFILE_FIELD_NAME_GROUPS                             = 'Commons Groups';
	const XPROFILE_FIELD_NAME_ACTIVITY                           = 'Commons Activity';
	const XPROFILE_FIELD_NAME_BLOGS                              = 'Commons Sites';

	/**
	 * Start the component creation process.
	 */
	public function __construct() {
		require_once dirname( __DIR__ ) . '/includes/functions.php';

		self::$display_names = [
			self::XPROFILE_FIELD_NAME_NAME               => 'Name',
			self::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION => 'Institutional or Other Affiliation',
			self::XPROFILE_FIELD_NAME_TITLE              => 'Title',
			self::XPROFILE_FIELD_NAME_SITE               => 'Website URL',
			self::XPROFILE_FIELD_NAME_TWITTER_USER_NAME  => '<em>Twitter</em> handle',
			self::XPROFILE_FIELD_NAME_FACEBOOK           => 'Facebook URL',
			self::XPROFILE_FIELD_NAME_LINKEDIN           => 'LinkedIn URL',
			self::XPROFILE_FIELD_NAME_ORCID              => '<em>ORCID</em> iD',
			self::XPROFILE_FIELD_NAME_ABOUT              => 'About',
			self::XPROFILE_FIELD_NAME_EDUCATION          => 'Education',
			self::XPROFILE_FIELD_NAME_PUBLICATIONS       => 'Publications',
			self::XPROFILE_FIELD_NAME_PROJECTS           => 'Projects',
			self::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES => 'Upcoming Talks and Conferences',
			self::XPROFILE_FIELD_NAME_MEMBERSHIPS        => 'Memberships',
			self::XPROFILE_FIELD_NAME_CORE_DEPOSITS      => 'Work Shared in CORE',
			self::XPROFILE_FIELD_NAME_CV                 => 'CV',
			self::XPROFILE_FIELD_NAME_ACADEMIC_INTERESTS => 'Academic Interests',
			self::XPROFILE_FIELD_NAME_GROUPS             => 'Commons Groups',
			self::XPROFILE_FIELD_NAME_ACTIVITY           => 'Commons Activity',
			self::XPROFILE_FIELD_NAME_BLOGS              => 'Commons Sites',
		];

		parent::start(
			'hc_member_profiles',
			'HC Member Profiles',
			dirname( __DIR__ ) . '/includes'
		);

		buddypress()->active_components[ $this->id ] = '1';

		bp_register_template_stack(
			function() {
					return plugin_dir_path( __DIR__ ) . 'templates/';
			}
		);

		$result = BP_XProfile_Group::get(
			[
				'profile_group_id' => 2,
				'fetch_fields'     => true,
			]
		);

		// If the expected group does NOT exist, we should throw an error here.
		// That is the case with phpunit so be careful to handle accordingly.
		if ( ! $result ) {
			$result = BP_XProfile_Group::get(
				[
					'fetch_fields' => true,
				]
			);
		}

		$this->xprofile_group = $result[0];
	}

	/**
	 * Add custom hooks.
	 */
	public function setup_actions() {
		// Register custom field types.
		add_filter( 'bp_xprofile_get_field_types', 'hcmp_register_xprofile_field_types' );

		// Update allowed/auto tag filtering.
		add_filter( 'xprofile_allowed_tags', 'hcmp_filter_xprofile_allowed_tags' );
		remove_filter( 'bp_get_the_profile_field_value', 'wpautop' );

		// Filter profile links in admin bar.
		add_action( 'wp_before_admin_bar_render', 'hcmp_filter_admin_bar' );

		// Don't log "changed their profile picture" activities.
		remove_action( 'xprofile_avatar_uploaded', 'bp_xprofile_new_avatar_activity' );

		// Apply profile-section-specific filters etc.
		if (
			! bp_is_user_change_avatar() &&
			! bp_is_user_change_cover_image() &&
			(
				bp_is_user_profile() ||
				bp_is_user_profile_edit() ||
				bp_is_members_directory() || // TODO replace member directory functionality.
				bp_is_groups_directory()
			)
		) {
			add_action( 'wp_enqueue_scripts', 'hcmp_enqueue_local_scripts' );

			if ( ! empty( bp_get_displayed_user_fullname() ) ) {

			}

			add_action( 'bp_before_profile_edit_content', 'hcmp_init_profile_edit' );

			// TODO still necessary?
			// We want the full value including existing html in edit field inputs.
			remove_filter( 'bp_get_the_profile_field_edit_value', 'wp_filter_kses', 1 );

			// TODO belongs in hc-custom
			// This breaks content containing [] chars (unless they're using the feature it provides, which our data is not).
			remove_filter( 'bp_get_the_profile_field_value', 'cpfb_add_brackets', 999, 1 );
		}

	}
}
