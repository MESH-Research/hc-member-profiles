<?php
/**
 * Plugin Name:     HC Member Profiles
 * Plugin URI:      https://github.com/mlaa/hc-member-profiles
 * Description:     Enhanced BuddyPress xprofile functionality for scholars
 * Author:          Ryan Williams
 * Author URI:      https://github.com/modelm
 * Text Domain:     hc-member-profiles
 * Domain Path:     /languages
 *
 * @package         HC_Member_Profiles
 */

require_once( 'includes/class-profile.php' );

/**
 * Register custom field types.
 *
 * @param array $fields Array of field type/class name pairings.
 * @return array
 */
function hcmp_register_xprofile_field_types( array $fields ) {
	// TODO better. too late by the time the field type class is instantiated (so not in that constructor)
	// In order to get BP to display this field without a value, include empty fields.
	add_filter( 'bp_before_has_profile_parse_args', function( $r ) {
		return array_merge( $r, [ 'hide_empty_fields' => false ] );
	} );
	add_filter( 'bp_field_has_data', '__return_true' );

	// BP Groups
	if ( bp_is_active( 'groups' ) ) {
		require_once( 'includes/class-bp-xprofile-field-type-groups.php' );

		$fields = array_merge( $fields, [
			'bp_groups' => 'BP_XProfile_Field_Type_Groups',
		] );
	}

	// BP Activity
	if ( bp_is_active( 'activity' ) ) {
		require_once( 'includes/class-bp-xprofile-field-type-activity.php' );

		$fields = array_merge( $fields, [
			'bp_activity' => 'BP_XProfile_Field_Type_Activity',
		] );
	}

	// BP Blogs
	if ( bp_is_active( 'blogs' ) ) {
		require_once( 'includes/class-bp-xprofile-field-type-blogs.php' );

		$fields = array_merge( $fields, [
			'bp_blogs' => 'BP_XProfile_Field_Type_Blogs',
		] );
	}

	// CORE Deposits
	if ( bp_is_active( 'humcore_deposits' ) ) {
		require_once( 'includes/class-bp-xprofile-field-type-core-deposits.php' );

		$fields = array_merge( $fields, [
			'core_deposits' => 'BP_XProfile_Field_Type_CORE_Deposits',
		] );
	}

	// Academic Interests
	if ( class_exists( 'MLA_Academic_Interests' ) ) {
		require_once( 'includes/class-bp-xprofile-field-type-academic-interests.php' );

		$fields = array_merge( $fields, [
			'academic_interests' => 'BP_XProfile_Field_Type_Academic_Interests',
		] );
	}

	return $fields;
}
add_filter( 'bp_xprofile_get_field_types', 'hcmp_register_xprofile_field_types' );

//function hcmp_register_template_stack() {
//	var_dump(trailingslashit( __DIR__ ) . 'templates');
//	return trailingslashit( __DIR__ ) . 'templates';
//}

//function hcmp_init() {
//	var_dump( __METHOD__ );
//	bp_register_template_stack( 'hcmp_register_template_stack', -999999 );
//}
//add_action( 'bp_init', 'hcmp_init' );
