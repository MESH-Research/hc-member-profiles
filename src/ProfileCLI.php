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
	 * Renames (prefixes) old fields to differentiate them from new ones.
	 */
	function rename_old_xprofile_fields() {
		$old_groups = \BP_XProfile_Group::get( [
			'fetch_fields' => true,
		] );

		$old_fields = $old_groups[0]->fields;

		try {
			foreach ( $old_fields as &$field ) {
				$field->name = '(old) ' . $field->name;
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
		global $wpdb;

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
		$max_user_id = $wpdb->get_var( 'SELECT MAX(ID) FROM wp_users' );
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
								throw \Exception( "Field data migration failed for user $user_id" );
							}
						}
					}
				}

				$user_id++;
				$progress->tick();
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		} finally {
			$progress->finish();
			WP_CLI::success( "Finished migrating xprofile field data." );
		}
	}

	/**
	 * depends on the mla-academic-interests plugin, which must be activated
	 */
	function insert_default_academic_interests() {
		$csv_file = "data/academic_interests.csv";
		$mla_academic_interests = new \Mla_Academic_Interests;

		if ( ( $handle = fopen( $csv_file, "r" ) ) !== false ) {
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$data = array_filter( $data );
				if ( ! empty( $data ) ) {
					$term = $data[0];
					$set_term = wp_insert_term( $term, 'mla_academic_interests' );
				}
			}
			fclose( $handle );
		}
	}


	/**
	 * depends on the mla-academic-interests plugin, which must be activated
	 * idempotent. overwrites academic_interests each iteration, but result should be the same as long as source data is
	 */
	function migrate_academic_interests() {
		global $wpdb;

		$csv_file = "data/academic_interests.csv";
		$mla_academic_interests = new \Mla_Academic_Interests;

		$term_map = [];

		// first, create a map of terms to facilitate migration
		// map contains keys which are primary terms and values which are arrays of variations of those terms to map to the key
		if ( ( $handle = fopen( $csv_file, "r" ) ) !== false ) {
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$data = array_filter( $data );
				if ( ! empty( $data ) ) {
					$primary_term = $data[0];
					unset( $data[0] );
					$term_map[$primary_term] = $data;
				}
			}
			fclose( $handle );
		}

		// now loop through users & assign interests according to the map
		$user_id = 1;
		$max_user_id = $wpdb->get_var( 'SELECT MAX(ID) FROM wp_users' );
		//$user_id = 2038;
		//$max_user_id = 2038;

		$progress = WP_CLI\Utils\make_progress_bar( 'Migrating academic interests:', $max_user_id );

		try {
			while ( $user_id <= $max_user_id ) {
				$userdata = \get_userdata( $user_id );
				$_POST = [];

				if ( $userdata ) {
					$old_interest_data = \xprofile_get_field_data( 'Interests', $user_id );
					foreach ( $term_map as $primary_term => $mapped_terms ) {
						// if the old interest data contains any of the mapped terms, add the primary term for this user
						foreach ( $mapped_terms as $term ) {
							if ( strpos( $old_interest_data, $term ) !== false ) {
								// populate $_POST because save_user_mla_academic_interests_terms expects it
								//$_POST['userid'] = $user_id; // not used to save, but useful for debugging/logging
								$_POST['academic-interests'][] = $primary_term;
							}
						}
					}
				}

				if ( ! empty( $_POST ) ) {
					//var_dump($_POST);
					$mla_academic_interests->save_user_mla_academic_interests_terms( $user_id );
				}

				$user_id++;
				$progress->tick();
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		} finally {
			$progress->finish();
			WP_CLI::success( "Finished migrating academic interests." );
		}
	}
}

if( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'profile', __NAMESPACE__ . '\ProfileCLI' );
}
