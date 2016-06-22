<?php

namespace MLA\Commons\Profile;

use \BP_Follow;
use \BP_XProfile_Group;
use \Exception;
use \Mla_Academic_Interests;
use \MLA\Commons\Profile;

class Migration {

	protected $xprofile_fields;
	protected $profile;

	public function __construct() {
		$this->profile = Profile::get_instance();

		$this->xprofile_fields = [
			Profile::XPROFILE_FIELD_NAME_NAME => 'textbox',
			Profile::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION => 'textbox',
			Profile::XPROFILE_FIELD_NAME_TITLE => 'textbox',
			Profile::XPROFILE_FIELD_NAME_SITE => 'textbox',
			Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => 'textbox',
			Profile::XPROFILE_FIELD_NAME_ABOUT => 'textarea',
			Profile::XPROFILE_FIELD_NAME_EDUCATION => 'textarea',
			Profile::XPROFILE_FIELD_NAME_PUBLICATIONS => 'textarea',
			Profile::XPROFILE_FIELD_NAME_PROJECTS => 'textarea',
			Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES => 'textarea',
			Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS => 'textarea',
		];
	}

	public function convert_friends_to_followers() {
		global $wpdb;

		$sql = "SELECT * FROM wp_bp_friends";
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		foreach( $rows as $row ){
			extract( $row );

			$follow = new BP_Follow( $initiator_user_id, $friend_user_id );
			$follow->save();

			if( $is_confirmed == 1 ){
				$follow = new BP_Follow( $friend_user_id, $initiator_user_id );
				$follow->save();
			}
		}
	}

	public function create_xprofile_group() {
		if ( ! $this->profile->xprofile_group ) {
			$this->profile->xprofile_group = new BP_XProfile_Group();
			$this->profile->xprofile_group->name = Profile::XPROFILE_GROUP_NAME;
			$this->profile->xprofile_group->description = Profile::XPROFILE_GROUP_DESCRIPTION;
			$this->profile->xprofile_group->save();
		}
	}

	/**
	 * Creates new fields used by this plugin.
	 */
	public function create_xprofile_fields() {
		$field_exists = function() use ( &$field_name, &$field_type ) {
			if ( is_array( $this->profile->xprofile_group->fields ) ) {
				foreach ( $this->profile->xprofile_group->fields as $field ) {
					if ( $field->name === $field_name && $field->type === $field_type ) {
						return true;
					}
				}
			}
		};

		foreach ( $this->xprofile_fields as $field_name => $field_type ) {
			if ( ! $field_exists() ) {
				\xprofile_insert_field( [
					'name' => $field_name,
					'type' => $field_type,
					'field_group_id' => $this->profile->xprofile_group->id,
				] );
			};
		}

		// populate instantiated group with newly created fields
		$this->profile->xprofile_group = BP_XProfile_Group::get( [
			'group_id' => $this->profile->xprofile_group->id,
			'fetch_fields' => true,

		] )[1]; // [1] since we expect a single existing group. in the future this should become configurable
	}

	/**
	 * Migrates data from preexisting xprofile fields to the new ones used by this plugin.
	 * If you are using this plugin and you're not MLA Commons, you'll want to override this to accommodate your schema.
	 */
	public function migrate_xprofile_field_data() {
		global $wpdb;

		// unless we remove this filter, html is stripped from some field values. we want the real value, not filtered
		remove_filter( 'xprofile_get_field_data', 'xprofile_filter_kses', 1 );

		// prevent stripping <br />
		remove_filter( 'xprofile_data_value_before_save', 'xprofile_sanitize_data_value_before_save', 1, 4 );

		// group that contains old fields
		// TODO for easier reuse on other sites, this should be in a configuration file somewhere
		$old_group_id = 0;

		// 'old' => 'new'
		// TODO for easier reuse on other sites, this should be in a configuration file somewhere
		$old_field_to_new_field_map = [
			'Name' => Profile::XPROFILE_FIELD_NAME_NAME,
			'Institutional or Other Affiliation' => Profile::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION,
			'Title' => Profile::XPROFILE_FIELD_NAME_TITLE,
			'Site' => Profile::XPROFILE_FIELD_NAME_SITE,
			'<em>Twitter</em> user name' => Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME,
			'Academic Interests' => Profile::XPROFILE_FIELD_NAME_ABOUT,
			'Education' => Profile::XPROFILE_FIELD_NAME_EDUCATION,
			'Publications' => Profile::XPROFILE_FIELD_NAME_PUBLICATIONS,
		];

		$old_groups = BP_XProfile_Group::get( [ 'fetch_fields' => true, ] );
		$old_fields = $old_groups[$old_group_id]->fields;

		$user_id = 1;
		$max_user_id = $wpdb->get_var( 'SELECT MAX(ID) FROM wp_users' );

		// field names are not guaranteed unique across groups, so we need to find the correct field in our group
		$get_new_field_id_by_name = function( $name ) {
			foreach ( $this->profile->xprofile_group->fields as $field ) {
				if ( $field->name === $name ) {
					return $field->id;
				}
			}
		};

		while ( $user_id <= $max_user_id ) {
			$userdata = \get_userdata( $user_id );

			if ( $userdata ) {
				foreach ( $old_fields as $field ) {
					if ( in_array( $field->name, array_keys( $old_field_to_new_field_map ) ) ) {
						$result = \xprofile_set_field_data(
							$get_new_field_id_by_name( $old_field_to_new_field_map[$field->name] ),
							$user_id,
							\xprofile_get_field_data( $field->id, $user_id )
						);

						if ( ! $result ) {
							throw new Exception( "Field data migration failed for user $user_id field " . $field->name );
						}
					}
				}
			}

			$user_id++;
		}
	}

	/**
	 * depends on the mla-academic-interests plugin, which must be activated
	 * idempotent. overwrites academic_interests each iteration, but result should be the same as long as source data is
	 * only works if you've already run create_xprofile_group(), create_xprofile_fields(), and migrate_xprofile_field_data()
	 */
	function migrate_academic_interests() {
		global $wpdb;

		// name of text field from which interests will be parsed & migrated
		// this field is the one which this plugin already created in create_xprofile_fields() and should contain migrated text
		$migrated_interests_field_name = Profile::XPROFILE_FIELD_NAME_ABOUT;

		$csv_file = "data/academic_interests.csv";

		$mla_academic_interests = new Mla_Academic_Interests;
		$term_map = [];

		foreach ( $this->profile->xprofile_group->fields as $field ) {
			if ( $field->name === $migrated_interests_field_name ) {
				$migrated_interests_field_id = $field->id;
				break;
			}
		}

		// first, create a map of terms to facilitate migration
		// map contains keys which are primary terms and values which are arrays of variations of those terms to map to the key
		if ( ( $handle = fopen( $csv_file, "r" ) ) !== false ) {
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$data = array_filter( $data );
				if ( ! empty( $data ) ) {
					$primary_term = $data[0];
					$term_map[$primary_term] = $data;
				}
			}
			fclose( $handle );
		}

		// now loop through users & assign interests according to the map
		$user_id = 1;
		$max_user_id = $wpdb->get_var( 'SELECT MAX(ID) FROM wp_users' );

		while ( $user_id <= $max_user_id ) {
			$userdata = \get_userdata( $user_id );
			$_POST = [];

			if ( $userdata ) {
				$old_interest_data = \xprofile_get_field_data( $migrated_interests_field_id, $user_id );
				$new_migrated_interests_field_data = $old_interest_data;

				foreach ( $term_map as $primary_term => $mapped_terms ) {
					// mapped_terms contains primary_term
					// this flag helps avoid redundant iterations after the first match
					$row_match = false;

					// if the old interest data contains any of the mapped terms, add the primary term for this user
					foreach ( $mapped_terms as $term ) {
						if ( ! $row_match && strpos( strtolower( $old_interest_data ), strtolower( $term ) ) !== false ) {
							$row_match = true;
							//var_dump($term);

							// populate $_POST because save_user_mla_academic_interests_terms expects it
							//$_POST['userid'] = $user_id; // not used to save, only for debugging/logging
							$_POST['academic-interests'][] = $primary_term;

							// remove matched terms from old data
							$new_migrated_interests_field_data = preg_replace(
								"/(,|^)[\s]*$term(,[\s]*|$)/i",
								'\2',
								$new_migrated_interests_field_data
							);

							// remove extra comma if required
							$new_migrated_interests_field_data = preg_replace(
								"/([,\s]*$|^[,\s]*)/",
								'',
								$new_migrated_interests_field_data
							);
						}
					}
				}

				// if we removed terms, save new data
				//var_dump($old_interest_data);
				//var_dump($new_migrated_interests_field_data);
				if ( $new_migrated_interests_field_data !== $old_interest_data ) {
					$remove_old_term_result = \xprofile_set_field_data(
						$migrated_interests_field_id,
						$user_id,
						$new_migrated_interests_field_data
					);
				}
			}

			if ( ! empty( $_POST ) ) {
				//var_dump($_POST);
				$mla_academic_interests->save_user_mla_academic_interests_terms( $user_id );
			}

			$user_id++;
		}
	}

}
