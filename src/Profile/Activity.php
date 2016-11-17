<?php

namespace MLA\Commons\Profile;

use \Humanities_Commons;

class Activity {

	public static function register_activity_actions() {
		bp_activity_set_action(
			buddypress()->profile->id,
			'updated_profile',
			__( 'Updated Profile', 'buddypress' ),
			__CLASS__ . '::format_activity_action_updated_profile',
			__( 'Profile Updates', 'buddypress' ),
			array( 'activity' )
		);
	}

	/**
	 * formats the output of existing activity records
	 * since we store the activity action in the format we want already with the filter below, just output directly
	 */
	public static function format_activity_action_updated_profile( $action, $activity ) {
		return apply_filters( 'bp_xprofile_format_activity_action_updated_profile', $action, $activity );
	}

	/**
	 * inserts the activity record
	 * mostly ripped from buddypress/bp-xprofile/bp-xprofile-activity.php
	 * the original does not save a comment at all, so the rendered activity just says "name's profile was updated"
	 * this saves a comment telling which field(s) changed e.g. "name updated field"
	 */
	public static function updated_profile_activity( $user_id, $field_ids = [], $errors = false, $old_values = [], $new_values = [] ) {
		// If there were errors, don't post.
		if ( ! empty( $errors ) ) {
			return false;
		}

		// Bail if activity component is not active.
		if ( ! bp_is_active( 'activity' ) ) {
			return false;
		}

		// Don't post if there have been no changes, or if the changes are
		// related solely to non-public fields.
		$public_changes = false;
		foreach ( $new_values as $field_id => $new_value ) {
			$old_value = isset( $old_values[ $field_id ] ) ? $old_values[ $field_id ] : '';

			// Don't register changes to private fields.
			if ( empty( $new_value['visibility'] ) || ( 'public' !== $new_value['visibility'] ) ) {
				continue;
			}

			// Don't register if there have been no changes.
			if ( $new_value === $old_value ) {
				continue;
			}

			// if we got here, this field has changed. take note

			$changed_fields[] = xprofile_get_field( $field_id );

			// Looks like we have public changes - no need to keep checking.
			$public_changes = true;
			// the original function breaks here, but we want to continue and grab all changed fields for output
			//break;
		}

		// Bail if no public changes.
		if ( empty( $public_changes ) ) {
			return false;
		}

		// Throttle to one activity of this type per 2 hours.
		$existing = bp_activity_get( array(
			'max'    => 1,
			'filter' => array(
				'user_id' => $user_id,
				'object'  => buddypress()->profile->id,
				'action'  => 'updated_profile',
			),
		) );

		// Default throttle time is 2 hours. Filter to change (in seconds).
		if ( ! empty( $existing['activities'] ) ) {

			/**
			 * Filters the throttle time, in seconds, used to prevent excessive activity posting.
			 *
			 * @since 2.0.0
			 *
			 * @param int $value Throttle time, in seconds.
			 */
			$throttle_period = apply_filters( 'bp_xprofile_updated_profile_activity_throttle_time', HOUR_IN_SECONDS * 2 );
			$then            = strtotime( $existing['activities'][0]->date_recorded );
			$now             = strtotime( bp_core_current_time() );

			// Bail if throttled.
			if ( ( $now - $then ) < $throttle_period ) {
				//TODO do we care?
				//return false;
			}
		}

		// If we've reached this point, assemble and post the activity item.
		if ( ! empty( Humanities_Commons::$main_network->domain ) ) {
			$profile_link = trailingslashit(
				trailingslashit( 'https://' . Humanities_Commons::$main_network->domain ) .
				bp_get_profile_slug()
			);
		} else {
			$profile_link = trailingslashit( bp_core_get_user_domain( $user_id ) . bp_get_profile_slug() );
		}

		$changed_field_names = [];
		foreach ( $changed_fields as $field ) {
			$changed_field_names[] = "\"$field->name\"";
		}

		// format as comma separated list including "and" before final item (when length > 1)
		$changed_field_names_str = join(
			( count( $changed_field_names ) > 2 ? ',' : '' ) . ' and ',
			array_filter(
				array_merge( [ join( ', ', array_slice( $changed_field_names, 0, -1 ) ) ], array_slice( $changed_field_names, -1 ) ),
				'strlen'
			)
		);

		$action = sprintf(
			__( "%s updated %s in their %s", 'buddypress' ),
			'<a href="' . $profile_link . '">' . bp_core_get_user_displayname( $user_id ) . '</a>',
			$changed_field_names_str,
			'<a href="' . $profile_link . '">profile</a>'
		);

		return (bool) xprofile_record_activity( array(
			'user_id'      => $user_id,
			'primary_link' => $profile_link,
			'component'    => buddypress()->profile->id,
			'type'         => 'updated_profile',
			'action'       => $action,
		) );
}

}
