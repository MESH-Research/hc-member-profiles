<?php

use MLA\Commons\Profile;
use MLA\Commons\Profile\Migration;
use MLA\Commons\Profile\Template;

class Test_Template extends BP_UnitTestCase {

	public static function setUpBeforeClass() {
		return; // TODO
		$profile = Profile::get_instance();

		// since tests use an empty db, group & fields need to be created
		$migration = new Migration;
		$migration->create_xprofile_group();
		$migration->create_xprofile_fields();
	}

	/**
	 * @dataProvider get_twitter_link_provider
	 */
	function test_get_twitter_link( $value ) {
		// TODO need to populate a mock user with values in relevant fields for this type of thing
		$this->markTestSkipped();

		$template = new Template;
		$result   = $template->get_twitter_link();

		$this->assertTrue( preg_match( '/^<a href="https:\/\/twitter.com\/[a-z0-9_]+">@[a-z0-9_]+<\/a>$/', $result ) );
	}

	function get_twitter_link_provider() {
		return [
			[ 'example' ],
			[ '@example' ],
			[ '@@example' ],
		];
	}

	/**
	 * @dataProvider get_normalized_url_field_value_provider
	 */
	function test_get_normalized_url_field_value( $field_name, $field_value, $expected_return_value ) {
		$template = new Template;

		// since the tested method gets data from an empty db, overwrite value with this filter
		$return_provider_value = function() use ( $field_value ) {
			return $field_value;
		};

		add_filter( 'commons_profile_field_value_' . sanitize_title( $field_name ), $return_provider_value );

		$this->assertEquals(
			$template->get_normalized_url_field_value( $field_name ),
			$expected_return_value
		);

		remove_filter( 'commons_profile_field_value_' . sanitize_title( $field_name ), $return_provider_value );
	}

	/**
	 * only the first two elements are used by the actual test directly,
	 * the third provides a value to the function being tested so we can
	 * compare to the second
	 */
	function get_normalized_url_field_value_provider() {
		// TODO this should probably be its own field type
		$this->markTestSkipped();

		// TODO these are probably better as consts to DRY with the method being tested
		$domains = [
			Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => 'twitter.com',
			Profile::XPROFILE_FIELD_NAME_FACEBOOK          => 'facebook.com',
			Profile::XPROFILE_FIELD_NAME_LINKEDIN          => 'linkedin.com/in',
			Profile::XPROFILE_FIELD_NAME_ORCID             => 'orcid.org',
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
				'0123456789',
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
				$cleaned_value = strip_tags(
					preg_replace(
						$patterns,
						'',
						$value
					)
				);

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
