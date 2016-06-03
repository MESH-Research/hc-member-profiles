<?php

namespace MLA\Commons;

use \WP_CLI;
use \BP_Follow;

/**
 * These commands will change your data in serious ways!
 * Do not run any of them unless you're absolutely sure what you're doing.
 */
class ProfileCLI {

	/**
	 * TODO NOT idempotent. check if each BP_Follow exists before constructing/saving.
	 */
	function friends_to_followers() {
		global $wpdb;

		$sql = "SELECT * FROM wp_bp_friends";
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		$progress = WP_CLI\Utils\make_progress_bar( 'Migrating friends to followers:', count( $rows ) );

		try {
			foreach( $rows as $row ){
				//print_r($row);
				extract( $row );

				$follow = new BP_Follow( $initiator_user_id, $friend_user_id );
				$follow->save();

				if( $is_confirmed == 1 ){
					$follow = new BP_Follow( $friend_user_id, $initiator_user_id );
					$follow->save();
				}

				$progress->tick();
			}
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		} finally {
			$progress->finish();
			WP_CLI::success( "Finished migrating friends to followers." );
		}
	}

	/**
	 * Creates new fields used by this plugin.
	 */
	function create_xprofile_fields() {
		$new_field_names = [
			'Academic Interests',
			'About',
			'Education',
			'Publications',
			'Projects',
			'Upcoming Talks and Conferences',
			'Memberships',
		];

		try {
			foreach ( $new_field_names as $name ) {
				\xprofile_insert_field( [
					'name' => $name,
					'type' => 'textarea',
					'field_group_id' => 1,
				] );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		} finally {
			WP_CLI::success( "Finished creating new xprofile fields." );
		}
	}

	/**
	 * Expects a single field group with id=1 to exist already and contain fields listed in the map.
	 * Migrates data from the relevant old fields to the new ones.
	 */
	function migrate_xprofile_field_data() {
		// old => new
		$old_to_new_map = [
			'Interests' => 'About',
		];

		// only one group exists
		$old_groups = \BP_XProfile_Group::get( [
			'fetch_fields' => true,
		] );

		$old_fields = $old_groups[0]->fields;
		//var_dump( $old_fields );

		$user_id = 1;
		$max_user_id = 6120;
		//$user_id = 5488;
		//$max_user_id = 5488;

		$progress = WP_CLI\Utils\make_progress_bar( 'Migrating xprofile field data:', $max_user_id );

		try {
			while ( $user_id <= $max_user_id ) {
				$userdata = \get_userdata( $user_id );

				if ( $userdata ) {
					foreach ( $old_fields as $field ) {
						if ( in_array( $field->name, array_keys( $old_to_new_map ) ) ) {
							$result = \xprofile_set_field_data(
								$old_to_new_map[$field->name],
								$user_id,
								\xprofile_get_field_data( $field->id, $user_id )
							);

							if ( ! $result ) {
								throw Exception( "Field data migration failed for user $user_id" );
							}
						}
					}
				}

				$user_id++;
				$progress->tick();
			}
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		} finally {
			$progress->finish();
			WP_CLI::success( "Finished migrating xprofile field data." );
		}
	}
}

if( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'profile', __NAMESPACE__ . '\ProfileCLI' );
}
