<?php
/**
 * Misc. functions.
 *
 * @package Hc_Member_Profiles
 */

/**
 * Register custom field types.
 *
 * @param array $fields Array of field type/class name pairings.
 * @return array
 */
function hcmp_register_xprofile_field_types( array $fields ) {
	// BP Groups.
	if ( bp_is_active( 'groups' ) ) {
		require_once dirname( __FILE__ ) . '/class-bp-xprofile-field-type-groups.php';
		$fields['bp_groups'] = 'BP_XProfile_Field_Type_Groups';
	}

	// BP Activity.
	if ( bp_is_active( 'activity' ) ) {
		require_once dirname( __FILE__ ) . '/class-bp-xprofile-field-type-activity.php';
		$fields['bp_activity'] = 'BP_XProfile_Field_Type_Activity';
	}

	// BP Blogs.
	if ( bp_is_active( 'blogs' ) ) {
		require_once dirname( __FILE__ ) . '/class-bp-xprofile-field-type-blogs.php';
		$fields['bp_blogs'] = 'BP_XProfile_Field_Type_Blogs';
	}

	// CORE Deposits.
	if ( bp_is_active( 'humcore_deposits' ) ) {
		require_once dirname( __FILE__ ) . '/class-bp-xprofile-field-type-core-deposits.php';
		$fields['core_deposits'] = 'BP_XProfile_Field_Type_CORE_Deposits';
	}

	// Academic Interests.
	if ( class_exists( 'MLA_Academic_Interests' ) ) {
		// Field type.
		require_once dirname( __FILE__ ) . '/class-bp-xprofile-field-type-academic-interests.php';
		$fields['academic_interests'] = 'BP_XProfile_Field_Type_Academic_Interests';

		// Backpat functionality - TODO roll this into the field type.
		require_once dirname( __FILE__ ) . '/class-academic-interests.php';
		add_action( 'bp_get_template_part', [ 'Academic_Interests', 'add_academic_interests_to_directory' ] );
		add_action( 'xprofile_updated_profile', [ 'Academic_Interests', 'save_academic_interests' ] );
		add_action( 'send_headers', [ 'Academic_Interests', 'set_academic_interests_cookie_query' ] );
	}

	return $fields;
}

/**
 * Whitelist some allowed HTML tags.
 *
 * @param array $allowed_tags Associative array of allowed tags.
 * @return array
 */
function hcmp_filter_xprofile_allowed_tags( $allowed_tags ) {
	$allowed_tags['br'] = [];
	$allowed_tags['ul'] = [];
	$allowed_tags['li'] = [];
	$allowed_tags['a']  = array_merge(
		$allowed_tags['a'], [
			'target' => true,
			'rel'    => true,
		]
	);
	return $allowed_tags;
}

/**
 * Scripts/styles that apply on profile & related pages only.
 */
function hcmp_enqueue_local_scripts() {
	wp_enqueue_style( 'hc-members-profile-local', plugins_url() . '/hc-member-profiles/css/profile.css' );
	wp_enqueue_script( 'hc-members-profile-jqdmh', plugins_url() . '/hc-member-profiles/js/lib/jquery.dynamicmaxheight.min.js' );
	wp_enqueue_script( 'hc-members-profile-local', plugins_url() . '/hc-member-profiles/js/main.js', [], 1 );

	// Load theme-specific styles.
	$theme = wp_get_theme();
	if ( false !== strpos( strtolower( $theme->get( 'Name' ) ), 'boss' ) ) {
		wp_enqueue_style( 'hc-members-profile-boss', plugins_url() . '/hc-member-profiles/css/boss.css' );
	}
}

/**
 * Initialize the profile field group loop.
 */
function hcmp_init_profile_edit() {
	bp_has_profile( 'profile_group_id=' . buddypress()->hc_member_profiles->xprofile_group->id );
	bp_the_profile_group();
}

/**
 * Change 'Portfolio' to 'Profile' in the admin bar.
 */
function hcmp_filter_admin_bar() {
	global $wp_admin_bar;

	// Portfolio -> Profile.
	foreach ( [ 'my-account-xprofile', 'my-account-settings-profile' ] as $field_id ) {
		$clone = $wp_admin_bar->get_node( $field_id );
		if ( $clone ) {
			$clone->title = 'Profile';
			$wp_admin_bar->add_menu( $clone );
		}
	}
}

/**
 * Get follow counts.
 * Depends on the buddypress-followers plugin.
 */
function hcmp_get_follow_counts() {
	$follow_counts = 0;

	if ( function_exists( 'bp_follow_total_follow_counts' ) ) {
		$follow_counts = bp_follow_total_follow_counts( [ 'user_id' => bp_displayed_user_id() ] );
	}

	return $follow_counts;
}

/**
 * For edit view - use like bp_the_profile_field().
 *
 * @param string $field_name Field name.
 * @return string Field edit form markup.
 */
function hcmp_get_edit_field( $field_name = '' ) {
	// TODO This basically tries to brute-force the profile group bootstrap. Is this necessary? Can it be improved?
	bp_has_profile( [ 'profile_group_id' => buddypress()->hc_member_profiles->xprofile_group->id ] );
	bp_the_profile_group(); // Start (abuse) the loop.

	$html = '';

	while ( bp_profile_fields() ) {
		bp_the_profile_field();

		if ( bp_get_the_profile_field_name() !== $field_name ) {
			continue;
		}

		ob_start();

		$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );

		$field_type->edit_field_html();

		do_action( 'bp_custom_profile_edit_fields_pre_visibility' );
		bp_profile_visibility_radio_buttons();

		do_action( 'bp_custom_profile_edit_fields' );

		$html = ob_get_clean();

		break; // Once we output the field we want, no need to continue looping.
	}

	return $html;
}

/**
 * Get list of available user actions as markup to display in profile member header.
 */
function hcmp_get_header_actions() {
	$html = '';

	ob_start();

	do_action( 'bp_member_header_actions' ); // Buttons dependent on context.
	bp_get_options_nav(); // Nav links, but we're grouping everything together.

	$html = ob_get_clean();

	$html_doc = new DOMDocument();

	$html_doc->loadHTML(
		mb_convert_encoding( '<ul>' . $html . '</ul>', 'HTML-ENTITIES', 'UTF-8' ),
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);

	// Move "edit" element to the end.
	// @codingStandardsIgnoreStart
	$edit_node = $html_doc->getElementById( 'edit-personal-li' );
	if ( $edit_node ) {
		$edit_node->firstChild->setAttribute(
			'class',
			$edit_node->firstChild->getAttribute( 'class' ) . ' button'
		);
		$html_doc->appendChild( $edit_node ); // this ends up after the <ul>, but we remove that anyway
	}

	$html = $html_doc->saveHTML();
	// @codingStandardsIgnoreEnd

	// Remove wrapping <ul> now that DOMDocument is finished.
	$html = preg_replace( '/<\/?ul>/', '', $html );

	// Remove button class from action buttons.
	$html = str_replace( 'generic-button', '', $html );

	// Turn nav <li>s into <div>s.
	$html = str_replace( '<li', '<div', $html );
	$html = str_replace( 'li>', 'div>', $html );

	return $html;
}

/**
 * Get field data by name.
 *
 * @param string $field_name Field name.
 */
function hcmp_get_xprofile_field_data( $field_name = '' ) {
	/**
	 * Filter the XProfile group in which to fetch this field's data by field name.
	 *
	 * @param int    XProfile group ID.
	 * @param string Field name.
	 *
	 * @return int
	 */
	$group_id = apply_filters(
		'hcmp_xprofile_group_id',
		buddypress()->hc_member_profiles->xprofile_group->id,
		$field_name
	);

	// Use hardcoded primary name field.
	if ( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_NAME === $field_name ) {
		$group_id = 1;
	}

	// Some custom fields are "empty" by design e.g. 'CORE Deposits'.
	$args = [
		'profile_group_id'  => $group_id,
		'hide_empty_fields' => false,
	];

	$retval = '';

	if ( bp_has_profile( $args ) ) {
		while ( bp_profile_groups() ) {
			bp_the_profile_group();

			if ( bp_profile_group_has_fields() ) {
				while ( bp_profile_fields() ) {
					bp_the_profile_field();

					if ( bp_get_the_profile_field_name() === $field_name ) {
						$retval = bp_get_the_profile_field_value();
						break;
					}
				}
			}
		}
	}

	/**
	 * Filter the field value.
	 *
	 * @param string $retval Field value.
	 * @param int    $group_id Field group ID.
	 *
	 * @return string Field value.
	 */
	$retval = apply_filters( 'hcmp_xprofile_field_value_' . sanitize_title( $field_name ), $retval, $group_id );

	return $retval;
}

/**
 * Helper function for url fields - handle user input including:
 *     username/path
 *     (for twitter) '@username'
 *     domain + username/path
 *     scheme + domain + username/path
 *
 * @param string $field_name Field name.
 * @return string normalized value
 */
function hcmp_get_normalized_url_field_value( $field_name ) {
	$domains = [
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => 'twitter.com',
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_FACEBOOK          => 'facebook.com',
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_LINKEDIN          => 'linkedin.com/in',
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ORCID             => 'orcid.org',
	];

	$patterns = [
		'#@#',
		'#(https?://)?(www\.)?' . preg_quote( $domains[ $field_name ], '#' ) . '/?#',
	];

	$value = strip_tags(
		preg_replace(
			$patterns,
			'',
			hcmp_get_xprofile_field_data( $field_name )
		)
	);

	if ( ! empty( $value ) ) {
		$value = "<a href=\"https://{$domains[ $field_name ]}/$value\">$value</a>";
	}

	return $value;
}

/**
 * Get direct message link for displayed user.
 */
function hcmp_get_username_link() {
	$html  = '<a href="' . bp_get_send_private_message_link() . '" title="Send private message">';
	$html .= '@' . bp_get_displayed_user_username();
	$html .= '</a>';
	return $html;
}

/**
 * Get field visibility.
 *
 * @param string $field_name Field name.
 * @return string
 */
function hcmp_get_xprofile_field_visibility( $field_name = '' ) {
	// TODO this doesn't handle multiple groups.
	foreach ( buddypress()->hc_member_profiles->xprofile_group->fields as $field ) {
		if ( $field->name === $field_name ) {
			return xprofile_get_field_visibility_level( $field->id, bp_displayed_user_id() );
		}
	}
}

/**
 * Get field data or edit form with header label wrapped in a div.
 *
 * @param string $field_name Field name.
 * @return string
 */
function hcmp_get_field( $field_name = '' ) {
	$classes = [
		sanitize_title( $field_name ),
	];

	$user_hideable_fields = [
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ABOUT,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_EDUCATION,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_PUBLICATIONS,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_PROJECTS,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_MEMBERSHIPS,
	];

	if ( in_array( $field_name, $user_hideable_fields ) ) {
		$classes[] = 'hideable';
	}

	$show_more_fields = [
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_PUBLICATIONS,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_CORE_DEPOSITS,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_GROUPS,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_BLOGS,
	];

	if ( in_array( $field_name, $show_more_fields ) ) {
		$classes[] = 'show-more';
	}

	$wordblock_fields = [
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ACADEMIC_INTERESTS,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_GROUPS,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ACTIVITY,
		HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_BLOGS,
	];

	if ( in_array( $field_name, $wordblock_fields ) ) {
		$classes[] = 'wordblock';
	}

	if ( bp_is_user_profile_edit() ) {
		$classes[] = 'editable';
		$content   = hcmp_get_edit_field( $field_name );
	} elseif ( 'public' === hcmp_get_xprofile_field_visibility( $field_name ) ) {
		$content = hcmp_get_xprofile_field_data( $field_name );
	}

	$retval = '';

	if ( ! empty( $content ) ) {
		// Must be on one line with no extra whitespace due to 'white-space: pre-wrap;'.
		$retval = sprintf(
			'<div class="%s"><h4>%s</h4>%s</div>',
			implode( ' ', $classes ),
			HC_Member_Profiles_Component::$display_names[ $field_name ],
			$content
		);
	}

	return $retval;
}
