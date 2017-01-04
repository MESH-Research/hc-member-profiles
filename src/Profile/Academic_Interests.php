<?php

namespace MLA\Commons\Profile;

class Academic_Interests {

	static function save_academic_interests( $user_id ) {
		$tax = get_taxonomy( 'mla_academic_interests' );

		// If array add any new keywords.
		if ( is_array( $_POST['academic-interests'] ) ) {
			foreach ( $_POST['academic-interests'] as $term_id ) {
				$term_key = wpmn_term_exists( $term_id, 'mla_academic_interests' );
				if ( empty( $term_key ) ) {
					$term_key = wpmn_insert_term( sanitize_text_field( $term_id ), 'mla_academic_interests' );
				}
				if ( ! is_wp_error( $term_key ) ) {
					$term_ids[] = intval( $term_key['term_id'] );
				} else {
					error_log( '*****MLA Academic Interests Error - bad tag*****' . var_export( $term_key, true ) );
				}
			}
		}

		// Set object terms for tags.
		$term_taxonomy_ids = wpmn_set_object_terms( $user_id, $term_ids, 'mla_academic_interests' );
		wpmn_clean_object_term_cache( $user_id, 'mla_academic_interests' );

		// Set user meta for theme query.
		delete_user_meta( $user_id, 'academic_interests' );
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			add_user_meta( $user_id, 'academic_interests', $term_taxonomy_id, $unique = false );
		}
	}

	static function set_academic_interests_cookie_query() {
		$cookie_name = 'academic_interest_term_taxonomy_id'; // TODO DRY

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$term_taxonomy_id = $_COOKIE[ $cookie_name ];
		} else {
			$interest = isset( $_REQUEST['academic_interest'] ) ? $_REQUEST['academic_interest'] : null;

			if ( ! empty( $interest ) ) {
				$term = wpmn_get_term_by( 'name', $interest, 'mla_academic_interests' );

				setcookie( $cookie_name, $term->term_taxonomy_id, null, '/' );
				$_COOKIE[ $cookie_name ] = $term->term_taxonomy_id;
			}

			if ( empty( $interest ) ) {
				setcookie( $cookie_name, null, null, '/' );
			}
		}
	}

	/**
	 * injects markup/js to support filtering a search/list by academic interest in member directory
	 * TODO academic-interest-related functions & variables should move to their own class. see Activity
	 */
	static function add_academic_interests_to_directory( $template ) {
		if ( in_array( 'members/members-loop.php', (array) $template ) ) {
			$cookie_name = 'academic_interest_term_taxonomy_id'; // TODO DRY
			$term_taxonomy_id = $_COOKIE[ $cookie_name ];

			if ( ! empty( $term_taxonomy_id ) ) {
				$term = wpmn_get_term_by( 'term_taxonomy_id', $term_taxonomy_id, 'mla_academic_interests' );
			}

			if ( $term ) {
				/*
						<div id="message" class="info notice">
							<p>
								<strong>"Academic Interest: %1$s" filter removed</strong>
								You can run another filtered search by clicking on an Academic Interest in any member profile.
							</p>
						</div>
				 */
				$format =
					'<div id="academic_interest">
						<h4>Academic Interest: %s <sup><a href="#" id="remove_academic_interest_filter">x</a></sup></h4>
					</div>';

				printf( $format, $term->name );
			}
		}

		return $template;
	}

}
