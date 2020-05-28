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

	// Organizations.
	if ( class_exists( 'Hc_Organizations' ) ) {
		require_once dirname( __FILE__ ) . '/class-bp-xprofile-field-type-hc-organizations.php';
		$fields['organizations'] = 'BP_XProfile_Field_Type_Organizations';
		// Backpat functionality - TODO roll this into the field type.
		require_once dirname( __FILE__ ) . '/class-organizations.php';
		add_action( 'bp_get_template_part', [ 'Organizations', 'add_organizations_to_directory' ] );
		add_action( 'xprofile_updated_profile', [ 'Organizations', 'save_organizations' ] );
		add_action( 'send_headers', [ 'Organizations', 'set_organizations_cookie_query' ] );
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
 * Scripts & styles.
 */
function hcmp_enqueue_scripts() {
	wp_enqueue_script( 'hcmp-jqdmh', plugins_url( 'js/lib/jquery.dynamicmaxheight.min.js', __DIR__ ) );

	$path  = 'js/main.js';
	$url   = plugins_url( $path, __DIR__ );
	$mtime = filemtime( plugin_dir_path( __DIR__ ) . $path );
	wp_enqueue_script( 'hcmp-main', $url, [], $mtime );

	// Theme-independent styles.
	$path  = 'css/profile.css';
	$url   = plugins_url( $path, __DIR__ );
	$mtime = filemtime( plugin_dir_path( __DIR__ ) . $path );
	wp_enqueue_style( 'hcmp-profile', $url, [], $mtime );

	// Boss-specific styles.
	$theme = wp_get_theme();
	if ( false !== strpos( strtolower( $theme->get( 'Name' ) ), 'boss' ) ) {
		$path  = 'css/boss.css';
		$url   = plugins_url( $path, __DIR__ );
		$mtime = filemtime( plugin_dir_path( __DIR__ ) . $path );
		wp_enqueue_style( 'hcmp-boss', $url, [], $mtime );
	}
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
 * Get follow counts.
 * Depends on the buddypress-followers plugin.
 *
 * @return int
 */
function hcmp_get_follow_counts() {
	$follow_counts = 0;

	if ( function_exists( 'bp_follow_total_follow_counts' ) ) {
		$follow_counts = bp_follow_total_follow_counts( [ 'user_id' => bp_displayed_user_id() ] );
	}

	return $follow_counts;
}

/**
 * Get list of available user actions as markup to display in profile member header.
 *
 * @return string
 */
function hcmp_get_header_actions() {
	ob_start();
	do_action( 'bp_member_header_actions' ); // Buttons dependent on context.
	bp_get_options_nav(); // Nav links, but we're grouping everything together.
	$html = ob_get_clean();

	$html_doc = new DOMDocument();
	$html_doc->loadHTML(
		mb_convert_encoding( '<ul>' . $html . '</ul>', 'HTML-ENTITIES', 'UTF-8' ),
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);

	// @codingStandardsIgnoreStart
	// Move "edit" element to the end.
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
		HC_Member_Profiles_Component::TWITTER  => 'twitter.com',
		HC_Member_Profiles_Component::FACEBOOK => 'facebook.com',
		HC_Member_Profiles_Component::LINKEDIN => 'linkedin.com/in',
		HC_Member_Profiles_Component::FIGSHARE => 'figshare.com',
		HC_Member_Profiles_Component::ORCID    => 'orcid.org',
	];

	$patterns = [
		'#@#',
		'#(https?://)?(www\.)?' . preg_quote( $domains[ $field_name ], '#' ) . '/?#',
	];

	$value = strip_tags(
		preg_replace(
			$patterns,
			'',
			_hcmp_get_field_data( $field_name )
		)
	);

	if ( ! empty( $value ) ) {
		$value = "<a href=\"https://{$domains[ $field_name ]}/$value\">$value</a>";
	}

	return $value;
}

/**
 * Get direct message link for displayed user.
 *
 * @return string
 */
function hcmp_get_username_link() {
	$html  = '<a href="' . bp_get_send_private_message_link() . '" title="Send private message">';
	$html .= '@' . bp_get_displayed_user_username();
	$html .= '</a>';
	return $html;
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
		HC_Member_Profiles_Component::ABOUT,
		HC_Member_Profiles_Component::EDUCATION,
		HC_Member_Profiles_Component::PUBLICATIONS,
		HC_Member_Profiles_Component::PROJECTS,
		HC_Member_Profiles_Component::TALKS,
		HC_Member_Profiles_Component::MEMBERSHIPS,
		HC_Member_Profiles_Component::CV,
	];

	if ( in_array( $field_name, $user_hideable_fields ) ) {
		$classes[] = 'hideable';
	}

	$show_more_fields = [
		HC_Member_Profiles_Component::INTERESTS,
		HC_Member_Profiles_Component::PUBLICATIONS,
		HC_Member_Profiles_Component::DEPOSITS,
		HC_Member_Profiles_Component::GROUPS,
		HC_Member_Profiles_Component::BLOGS,
	];

	$wordblock_fields = [
		HC_Member_Profiles_Component::INTERESTS,
		HC_Member_Profiles_Component::GROUPS,
		HC_Member_Profiles_Component::BLOGS,
	];

	if ( in_array( $field_name, $wordblock_fields ) ) {
		$classes[] = 'wordblock';
	}

	if ( bp_is_user_profile_edit() ) {
		$classes[] = 'editable';
		$content   = _hcmp_get_edit_field( $field_name );
	} elseif ( 'public' === _hcmp_get_field_visibility( $field_name ) ) {
		if ( in_array( $field_name, $show_more_fields ) ) {
			$classes[] = 'show-more';
		}

		$content = _hcmp_get_field_data( $field_name );
	}

	$retval = '';

	if ( ! empty( $content ) ) {

		if ( bp_is_user_profile_edit() ) {
		     $retval = sprintf(
                        '<div class="%s"><h4>%s</h4>%s <br><br> %s</div>',
                        implode( ' ', $classes ),
                        HC_Member_Profiles_Component::$display_names[ $field_name ],
                        $content,
			bp_get_the_profile_field_description()
		
                    );

		} else {
		    $retval = sprintf(
			'<div class="%s"><h4>%s</h4>%s</div>',
			implode( ' ', $classes ),
			HC_Member_Profiles_Component::$display_names[ $field_name ],
			$content
		    );
		}
	}

	return $retval;
}

/**
 * Internal helper function to run a callback on a field by field name.
 *
 * @param string   $field_name Field name.
 * @param callable $callback Callback.
 *
 * @return mixed Return value of callback.
 */
function _hcmp_do_with_field_in_loop( $field_name = '', $callback ) {
	$args = [
		'hide_empty_fields' => false, // Some custom field types are "empty" by design e.g. 'CORE Deposits'.
	];

	if ( bp_has_profile( $args ) ) {
		while ( bp_profile_groups() ) {
			bp_the_profile_group();

			if ( bp_profile_group_has_fields() ) {
				while ( bp_profile_fields() ) {
					bp_the_profile_field();

					if ( bp_get_the_profile_field_name() === $field_name ) {
						return call_user_func( $callback );
					}
				}
			}
		}
	}
}

/**
 * Get field visibility.
 *
 * @param string $field_name Field name.
 * @return string
 */
function _hcmp_get_field_visibility( $field_name = '' ) {
	return _hcmp_do_with_field_in_loop(
		$field_name, function() {
			return xprofile_get_field_visibility_level( bp_get_the_profile_field_id(), bp_displayed_user_id() );
		}
	);
}

/**
 * Get field data by name.
 *
 * @param string $field_name Field name.
 *
 * @return string
 */
function _hcmp_get_field_data( $field_name = '' ) {
	return _hcmp_do_with_field_in_loop(
		$field_name, function() {
			$retval = bp_get_the_profile_field_value();
			if ( 'textarea' === bp_get_the_profile_field_type() ) {
				$retval = nl2br( $retval );
			}
			return $retval;
		}
	);
}

/**
 * Get field edit form markup.
 *
 * For edit view - use like bp_the_profile_field().
 *
 * @param string $field_name Field name.
 * @return string
 */
function _hcmp_get_edit_field( $field_name = '' ) {
	return _hcmp_do_with_field_in_loop(
		$field_name, function() {
			ob_start();

			$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
			$field_type->edit_field_html();
			do_action( 'bp_custom_profile_edit_fields_pre_visibility' );
			bp_profile_visibility_radio_buttons();
			do_action( 'bp_custom_profile_edit_fields' );

			return ob_get_clean();
		}
	);
}

/**
 * Create xprofile fields used by this plugin (that don't exist already).
 *
 * TODO error handling if field creation fails.
 * TODO filterable field_group_id
 * TODO handle name collisions with different fields/types
 */
function _hcmp_create_xprofile_fields() {

	// Create field types with no dependencies other than BP XProfile.
	$default_fields = [
		HC_Member_Profiles_Component::NAME         => 'textbox',
		HC_Member_Profiles_Component::TITLE        => 'textbox',
		HC_Member_Profiles_Component::SITE         => 'url',
		HC_Member_Profiles_Component::TWITTER      => 'textbox',
		HC_Member_Profiles_Component::ORCID        => 'textbox',
		HC_Member_Profiles_Component::FACEBOOK     => 'url',
		HC_Member_Profiles_Component::LINKEDIN     => 'url',
		HC_Member_Profiles_Component::FIGSHARE     => 'url',
		HC_Member_Profiles_Component::ABOUT        => 'textarea',
		HC_Member_Profiles_Component::EDUCATION    => 'textarea',
		HC_Member_Profiles_Component::PUBLICATIONS => 'textarea',
		HC_Member_Profiles_Component::PROJECTS     => 'textarea',
		HC_Member_Profiles_Component::TALKS        => 'textarea',
		HC_Member_Profiles_Component::MEMBERSHIPS  => 'textarea',
	];

	foreach ( $default_fields as $name => $type ) {
		$field_id = xprofile_get_field_id_from_name( $name );

		if ( ! $field_id ) {
			$field_id = xprofile_insert_field(
				[
					'name'           => $name,
					'type'           => $type,
					'field_group_id' => 1,
				]
			);
		};

		$field = xprofile_get_field( $field_id );

		// If an existing field is in a different group, move it to the primary group.
		if ( 1 !== $field->group_id ) {
			$field->group_id = 1;
			$field->save();
		}
	}

	// Create field types that have satisfied dependencies - see hcmp_register_xprofile_field_types().
	$extra_fields = [
		HC_Member_Profiles_Component::AFFILIATION  => 'organizations',
		HC_Member_Profiles_Component::DEPOSITS  => 'core_deposits',
		HC_Member_Profiles_Component::CV        => 'bp_attachment',
		HC_Member_Profiles_Component::INTERESTS => 'academic_interests',
		HC_Member_Profiles_Component::GROUPS    => 'bp_groups',
		HC_Member_Profiles_Component::ACTIVITY  => 'bp_activity',
		HC_Member_Profiles_Component::BLOGS     => 'bp_blogs',
	];

	$existing_types = bp_xprofile_get_field_types();

	foreach ( $extra_fields as $name => $type ) {
		if ( in_array( $type, array_keys( $existing_types ) ) ) {
			$field_id = xprofile_get_field_id_from_name( $name );
			$field    = xprofile_get_field( $field_id );

			// If a field with the same name but a different type exists, this updates that field.
			if ( ! $field_id || $field->type !== $type ) {
				$field_id = xprofile_insert_field(
					[
						'name'           => $name,
						'type'           => $type,
						'field_group_id' => 1,
					]
				);
			}
		}
	}

}
