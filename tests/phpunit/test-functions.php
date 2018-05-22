<?php
/**
 * Class Test_Functions
 *
 * @package Hc_Member_Profiles
 */

/**
 * Tests for standalone functions.
 */
class Test_Functions extends BP_UnitTestCase {

	/**
	 * Ensure various possible URL user-input values are normalized correctly.
	 *
	 * @dataProvider hcmp_get_normalized_url_field_value_provider
	 *
	 * @param string $field_name Name.
	 * @param string $field_value Value.
	 * @param string $expected_return_value Value.
	 */
	function test_hcmp_get_normalized_url_field_value( $field_name, $field_value, $expected_return_value ) {
		// Since the tested method gets data from an empty db, overwrite value with this filter.
		$return_provider_value = function() use ( $field_value ) {
			return $field_value;
		};

		add_filter( 'bp_get_the_profile_field_value', $return_provider_value );

		$this->assertEquals(
			hcmp_get_normalized_url_field_value( $field_name ),
			$expected_return_value
		);

		remove_filter( 'bp_get_the_profile_field_value', $return_provider_value );
	}

	/**
	 * Only the first two elements are used by the actual test directly,
	 * the third provides a value to the function being tested so we can
	 * compare to the second.
	 */
	function hcmp_get_normalized_url_field_value_provider() {
		// TODO these are probably better as consts to DRY with the method being tested.
		$domains = [
			HC_Member_Profiles_Component::TWITTER  => 'twitter.com',
			HC_Member_Profiles_Component::FACEBOOK => 'facebook.com',
			HC_Member_Profiles_Component::LINKEDIN => 'linkedin.com/in',
			HC_Member_Profiles_Component::ORCID    => 'orcid.org',
		];

		$field_names = [
			HC_Member_Profiles_Component::TWITTER,
			HC_Member_Profiles_Component::FACEBOOK,
			HC_Member_Profiles_Component::LINKEDIN,
			HC_Member_Profiles_Component::ORCID,
		];

		$data_sets = [];

		foreach ( $field_names as $name ) {
			$patterns = [
				'#@#',
				'#(https?://)?(www\.)?' . preg_quote( $domains[ $name ], '#' ) . '/?#',
			];

			// Use same user input values for all fields.
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
