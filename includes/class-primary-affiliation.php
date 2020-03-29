<?php
/**
 * Legacy class to support primary affiliation.
 *
 * Deprecated - planned to roll into field type class.
 *
 * @package Hc_Member_Profiles
 */

/**
 * Filters to control tax saving/loading.
 */
class Primary_Affiliation {

	/**
	 * Cookie name.
	 *
	 * @var string
	 */
	static $cookie_name = 'primary_affiliation_term_taxonomy_id';

	/**
	 * Querystring param name.
	 *
	 * @var string
	 */
	static $query_param = 'primary_affiliation';

	/**
	 * Save terms.
	 *
	 * @param int $user_id User.
	 */
	static function save_primary_affiliation( $user_id ) {
		$tax = get_taxonomy( 'hc_primary_affiliation' );

		// Add any new terms.
		foreach ( $_POST['primary-affiliation'] as $term_text ) {
			$term_key = wpmn_term_exists( $term_text, 'hc_primary_affiliation' );
			if ( empty( $term_key ) ) {
				$term_key = wpmn_insert_term( sanitize_text_field( $term_text ), 'hc_primary_affiliation' );
			}
			if ( ! is_wp_error( $term_key ) ) {
				$term_ids[] = intval( $term_key['term_id'] );
			} else {
				error_log( '*****HC Primary Affiliation Error - bad tag*****' . var_export( $term_key, true ) );
			}
		}

		// Set object terms for tags.
		$term_taxonomy_ids = wpmn_set_object_terms( $user_id, $term_ids, 'hc_primary_affiliation' );
		wpmn_clean_object_term_cache( $user_id, 'hc_primary_affiliation' );

		$term_names = array();

		// Set user meta for theme query.
		delete_user_meta( $user_id, 'primary_affiliation' );
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			add_user_meta( $user_id, 'primary_affiliation', $term_taxonomy_id, false );
			$term_data = wpmn_get_term_by( 'term_taxonomy_id', $term_taxonomy_id, 'hc_primary_affiliation' );
			$term_names[] = $term_data->name;
		}
		if ( ! empty( $term_names ) ) {
			$result = xprofile_set_field_data( 14, $user_id, implode( '; ', $term_names ) );
		}
	}

	/**
	 * Set cookie.
	 */
	static function set_primary_affiliation_cookie_query() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$term_taxonomy_id = $_COOKIE[ self::$cookie_name ];
		} else {
			$affiliation = isset( $_REQUEST[ self::$query_param ] ) ? $_REQUEST[ self::$query_param ] : null;

			if ( ! empty( $affiliation ) ) {
				$term = wpmn_get_term_by( 'name', $affiliation, 'hc_primary_affiliation' );

				setcookie( self::$cookie_name, $term->term_taxonomy_id, null, '/' );
				$_COOKIE[ self::$cookie_name ] = $term->term_taxonomy_id;
			} else {
				setcookie( self::$cookie_name, null, null, '/' );
			}
		}
	}

	/**
	 * Injects markup to support filtering a search/list by primary affiliation in member directory
	 *
	 * @param string $template Template path.
	 */
	static function add_primary_affiliation_to_directory( $template ) {
		if ( in_array( 'members/members-loop.php', (array) $template ) && isset( $_COOKIE[ self::$cookie_name ] ) ) {
			$term_taxonomy_id = $_COOKIE[ self::$cookie_name ];

			if ( ! empty( $term_taxonomy_id ) ) {
				$term = wpmn_get_term_by( 'term_taxonomy_id', $term_taxonomy_id, 'hc_primary_affiliation' );
			}

			if ( $term ) {
				echo sprintf(
					'<div id="primary_affiliation">
						<h4>Primary Affiliation: %1$s <sup><a href="#" id="remove_primary_affiliation_filter">x</a></sup></h4>
					</div>
					<div id="message" class="primary_affiliation_removed" class="info notice" style="display:none">
						<p>"Primary Affiliation: %1$s" filter removed</p>
					</div>',
					$term->name
				);
			}
		}

		return $template;
	}

}
