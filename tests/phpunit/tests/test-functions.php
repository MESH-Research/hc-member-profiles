<?php

class Test_Functions extends BP_UnitTestCase {

	/**
	 * @dataProvider hcmp_get_normalized_url_field_value_provider
	 */
	function test_hcmp_get_normalized_url_field_value( $field_name, $field_value, $expected_return_value ) {
		// since the tested method gets data from an empty db, overwrite value with this filter
		$return_provider_value = function() use ( $field_value ) {
			return $field_value;
		};

		add_filter( 'hcmp_xprofile_field_value_' . sanitize_title( $field_name ), $return_provider_value );

		$this->assertEquals(
			hcmp_get_normalized_url_field_value( $field_name ),
			$expected_return_value
		);

		remove_filter( 'hcmp_xprofile_field_value_' . sanitize_title( $field_name ), $return_provider_value );
	}

	/**
	 * Only the first two elements are used by the actual test directly,
	 * the third provides a value to the function being tested so we can
	 * compare to the second.
	 */
	function hcmp_get_normalized_url_field_value_provider() {
		// TODO these are probably better as consts to DRY with the method being tested
		$domains = [
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => 'twitter.com',
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_FACEBOOK          => 'facebook.com',
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_LINKEDIN          => 'linkedin.com/in',
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ORCID             => 'orcid.org',
		];

		$field_names = [
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_TWITTER_USER_NAME,
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_FACEBOOK,
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_LINKEDIN,
			HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ORCID,
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
