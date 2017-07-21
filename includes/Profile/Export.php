<?php

namespace MLA\Commons\Profile;

use \BP_XProfile_Group;

class Export {

	/**
	 * Export all xprofile field data + groups as CSV
	 */
	function write_xprofile_data_csv( $filename = 'xprofile_export.csv' ) {
		global $wpdb;

		$groups = BP_XProfile_Group::get( [ 'fetch_fields' => true ] );

		// this is NOT the group used by this plugin (probably), should change to be configurable or export all groups
		$fields = $groups[0]->fields;

		$user_id = 1;
		$max_user_id = $wpdb->get_var( 'SELECT MAX(ID) FROM wp_users' );

		$fp = fopen( $filename, 'w' );

		// column headers
		$row = [];
		foreach ( $fields as $field ) {
			$row[] = $field->name;
		}
		$row[] = 'Group Slugs';
		fputcsv( $fp, $row );

		// data rows
		while ( $user_id <= $max_user_id ) {
			$userdata = get_userdata( $user_id );

			if ( $userdata ) {
				$row = [];

				foreach ( $fields as $field ) {
					$row[] = xprofile_get_field_data( $field->id, $user_id );
				}

				$group_ids = groups_get_user_groups( $user_id );
				$group_slugs = [];

				foreach ( $group_ids as $group_id ) {
					$group_slugs[] = groups_get_slug( $group_id );
				}

				$row[] = implode( ';', $group_slugs );

				fputcsv( $fp, $row );
			}

			$user_id++;
		}

		fclose( $fp );
	}

}
