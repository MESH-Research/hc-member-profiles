<?php

use MLA\Commons\Profile;
use MLA\Commons\Migration;
use MLA\Commons\Template;

class Test_Template extends BP_UnitTestCase {
	public static function setUpBeforeClass() {
		Profile::get_instance();

		// since tests use an empty db, group & fields need to be created
		$migration = new Migration;
		$migration->create_xprofile_group();
		$migration->create_xprofile_fields();
	}

	/**
	 * This covers field types native to BP excluding normalized URL values which are covered by test_get_normalized_url_field_value()
	 * Adapted from BP_Tests_XProfile_Functions->test_bp_get_member_profile_data_outside_of_loop
	 *
	 * @dataProvider get_xprofile_field_data_provider
	 */
	function test_get_xprofile_field_data( $field_name, $expected_field_value ) {
		$user_id = $this->factory->user->create();
		xprofile_set_field_data( $field_name, $user_id, $expected_field_value );

		$actual_field_value = bp_get_member_profile_data( [
			'user_id' => $user_id,
			'field' => $field_name,
		] );

		$this->assertSame( $expected_field_value, $actual_field_value );
	}

	function get_xprofile_field_data_provider() {
		return [
			[ Profile::XPROFILE_FIELD_NAME_NAME, 'Alice Smith' ],
			[ Profile::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION, 'Example Title' ],
			[ Profile::XPROFILE_FIELD_NAME_TITLE, 'Example Title' ],
			[ Profile::XPROFILE_FIELD_NAME_ABOUT, 'Example about.' ],
			[ Profile::XPROFILE_FIELD_NAME_EDUCATION, 'Example education.' ],
			[ Profile::XPROFILE_FIELD_NAME_PUBLICATIONS, 'Example publications.' ],
			[ Profile::XPROFILE_FIELD_NAME_PROJECTS, 'Example projects.' ],
			[ Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES, 'Example talks.' ],
			[ Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS, 'Example memberships.' ],
		];
	}

	/**
	 * @dataProvider get_normalized_url_field_value_provider
	 */
	function test_get_normalized_url_field_value( $field_name, $field_value, $expected_return_value ) {
		// since the tested method gets data from an empty db, overwrite value with this filter
		$return_provider_value = function() use ( $field_value ) {
			return $field_value;
		};

		add_filter( 'commons_profile_field_value_' . sanitize_title( $field_name ), $return_provider_value );

		$this->assertEquals(
			Template::get_normalized_url_field_value( $field_name ),
			$expected_return_value
		);

		remove_filter( 'commons_profile_field_value_' . sanitize_title( $field_name ), $return_provider_value );
	}

	function get_normalized_url_field_value_provider() {
		// TODO these are probably better as consts to DRY with the method being tested
		$domains = [
			Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => 'twitter.com',
			Profile::XPROFILE_FIELD_NAME_FACEBOOK => 'facebook.com',
			Profile::XPROFILE_FIELD_NAME_LINKEDIN => 'linkedin.com/in',
			Profile::XPROFILE_FIELD_NAME_ORCID => 'orcid.org',
		];

		$field_names = [
			Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME,
			Profile::XPROFILE_FIELD_NAME_FACEBOOK,
			Profile::XPROFILE_FIELD_NAME_LINKEDIN,
			Profile::XPROFILE_FIELD_NAME_ORCID,
		];

		$data_sets = [];

		foreach ( $field_names as $name ) {
			// TODO can this be DRY with the tested method?
			$patterns = [
				'#@#',
				'#(https?://)?(www\.)?' . preg_quote( $domains[ $name ], '#' ) . '/?#',
			];

			// use same user input values for all fields
			$field_values = [
				"0123456789",
				'example',
				'@example',
				'@@example',
				"{$domains[ $name ]}/example",
				"www.{$domains[ $name ]}/example",
				"http://{$domains[ $name ]}/example",
				"http://www.{$domains[ $name ]}/example",
				"https://{$domains[ $name ]}/example",
				"https://www.{$domains[ $name ]}/example",
			];

			foreach ( $field_values as $value ) {
				$cleaned_value = strip_tags( preg_replace(
					$patterns,
					'',
					$value
				) );

				$data_sets[] = [
					$name,
					$value,
					"<a href=\"https://{$domains[ $name ]}/$cleaned_value\">$cleaned_value</a>",
				];
			}
		}

		return $data_sets;
	}
}
