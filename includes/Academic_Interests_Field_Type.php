<?php

namespace MLA\Commons;



class Blogs_Field_Type extends \BP_XProfile_Field_Type {

	public $name = Profile::XPROFILE_FIELD_NAME_BLOGS;

	public $accepts_null_value = true;

	static $cookie_name = 'academic_interest_term_taxonomy_id';

	static $query_param = 'academic_interests';

	public function __construct() {
		add_action( 'bp_get_template_part', [ $this, 'add_academic_interests_to_directory' ] );
		add_action( 'xprofile_updated_profile', [ $this, 'save_academic_interests' ] );
		// this needs to be able to send a set-cookie header
		add_action( 'send_headers', [ $this, 'set_academic_interests_cookie_query' ] );

		parent::__construct();
	}

	public static function display_filter( $field_value, $field_id = '' ) {
		global $humanities_commons;

		$html = '';
		$societies_html = [];

		$querystring =  bp_ajax_querystring( 'blogs' ) . '&' . http_build_query( [
			'type' => 'alphabetical',
		] );

		if ( bp_has_blogs( $querystring ) ) {
			while ( bp_blogs() ) {
				bp_the_blog();
				switch_to_blog( bp_get_blog_id() );
				$user = get_userdata( bp_core_get_displayed_userid( bp_get_displayed_user_username() ) );
				if ( ! empty( array_intersect( ['administrator', 'editor'], $user->roles ) ) ) {
					$society_id = $humanities_commons->hcommons_get_blog_society_id( bp_get_blog_id() );
					$societies_html[ $society_id ][] = '<li><a href="' . bp_get_blog_permalink() . '">' . bp_get_blog_name() . '</a></li>';
				}
				restore_current_blog();
			}

			ksort( $societies_html );

			foreach ( $societies_html as $society_id => $society_html ) {
				$html .= '<h5>' . strtoupper( $society_id ) . '</h5>';
				$html .= '<ul>' . implode( '', $society_html ) . '</ul>';
			}
		}

		return $html;
	}

	public function edit_field_html( array $raw_properties = [] ) {
		global $mla_academic_interests;

		$tax = get_taxonomy( 'mla_academic_interests' );

		$interest_list = $mla_academic_interests->mla_academic_interests_list();
		$input_interest_list = wpmn_get_object_terms( bp_displayed_user_id(), 'mla_academic_interests', [ 'fields' => 'names' ] );

		$html = '<p class="description">Enter interests from the existing list, or add new interests if needed.</p>';
		$html .= '<select name="academic-interests[]" class="js-basic-multiple-tags interests" multiple="multiple" data-placeholder="Enter interests.">';

		foreach ( $interest_list as $interest_key => $interest_value ) {
			$html .= sprintf('
				<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
				( in_array( $interest_key, $input_interest_list ) ) ? 'selected="selected"' : '',
				$interest_key,
				$interest_value
			);
		}

		$html .= '</select>';

		return $html;
	}

	public function admin_field_html( array $raw_properties = [] ) {
		echo 'This field is not editable.';
	}

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
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$term_taxonomy_id = $_COOKIE[ self::$cookie_name ];
		} else {
			$interest = isset( $_REQUEST[ self::$query_param ] ) ? $_REQUEST[ self::$query_param ] : null;

			if ( ! empty( $interest ) ) {
				$term = wpmn_get_term_by( 'name', $interest, 'mla_academic_interests' );

				setcookie( self::$cookie_name, $term->term_taxonomy_id, null, '/' );
				$_COOKIE[ self::$cookie_name ] = $term->term_taxonomy_id;
			} else {
				setcookie( self::$cookie_name, null, null, '/' );
			}
		}
	}

	/**
	 * injects markup to support filtering a search/list by academic interest in member directory
	 */
	static function add_academic_interests_to_directory( $template ) {
		if ( in_array( 'members/members-loop.php', (array) $template ) && isset( $_COOKIE[ self::$cookie_name ] ) ) {
			$term_taxonomy_id = $_COOKIE[ self::$cookie_name ];

			if ( ! empty( $term_taxonomy_id ) ) {
				$term = wpmn_get_term_by( 'term_taxonomy_id', $term_taxonomy_id, 'mla_academic_interests' );
			}

			if ( $term ) {
				echo sprintf(
					'<div id="academic_interest">
						<h4>Academic Interest: %1$s <sup><a href="#" id="remove_academic_interest_filter">x</a></sup></h4>
					</div>
					<div id="message" class="academic_interest_removed" class="info notice" style="display:none">
						<p>"Academic Interest: %1$s" filter removed</p>
					</div>',
					$term->name
				);
			}
		}

		return $template;
	}
}
