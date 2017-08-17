<?php

namespace MLA\Commons\Profile;

use \DOMDocument;
use \MLA\Commons\Profile;

class Template {

	public function get_follow_counts() {
		$follow_counts = 0;

		if ( function_exists( 'bp_follow_total_follow_counts' ) ) {
			$follow_counts = bp_follow_total_follow_counts( [ 'user_id' => bp_displayed_user_id() ] );
		}

		return $follow_counts;
	}

	public function get_academic_interests() {
		$tax = get_taxonomy( 'mla_academic_interests' );
		$interests = wpmn_get_object_terms( bp_displayed_user_id(), 'mla_academic_interests', array( 'fields' => 'names' ) );
		$html = '<ul>';
		foreach ( $interests as $term_name ) {
			$search_url = add_query_arg( [ 'academic_interests' => urlencode( $term_name ) ], bp_get_members_directory_permalink() );
			$html .= '<li><a href="' . esc_url( $search_url ) . '" rel="nofollow">';
			$html .=  $term_name;
			$html .= '</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * @uses DOMDocument
	 */
	public function get_academic_interests_edit() {
		global $mla_academic_interests;

		$doc = new DOMDocument;

		ob_start();
		$mla_academic_interests->edit_user_mla_academic_interests_section();

		// encoding prevents mangling of multibyte characters
		// constants ensure no <body> or <doctype> tags are added
		$doc->loadHTML(
			mb_convert_encoding( ob_get_clean(), 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		// we only want the actual select element, not the header or table wrapper etc.
		return $doc->saveHTML( $doc->getElementsByTagName( 'select' )[0] );
	}

	/**
	 * for edit view. use like bp_the_profile_field().
	 * works inside or outside the fields loop.
	 * TODO handle hideable fields like get_field() here rather than in template
	 */
	public function get_edit_field( $field_name ) {
		bp_has_profile( [ 'profile_group_id' => Profile::get_instance()->xprofile_group->id ] ); // select our group
		bp_the_profile_group(); // start (abuse) the loop

		$html = '';

		while ( bp_profile_fields() ) {
			bp_the_profile_field();

			if ( bp_get_the_profile_field_name() !== $field_name ) {
				continue;
			}

			ob_start();

			$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );

			$field_type->edit_field_html();

			do_action( 'bp_custom_profile_edit_fields_pre_visibility' );
			bp_profile_visibility_radio_buttons();

			do_action( 'bp_custom_profile_edit_fields' );

			$html = ob_get_clean();

			break; // once we output the field we want, no need to continue looping
		}

		return $html;
	}

	/**
	 * @uses DOMDocument
	 */
	public function get_activity( $max = 5 ) {
		$querystring = bp_ajax_querystring( 'activity' ) . '&' . http_build_query( [
			'max' => $max,
			'scope' => 'just-me',
			// action & type are blank to override cookies setting filters from directory
			'action' => '',
			'type' => '',
		] );

		if ( bp_has_activities( $querystring ) ) {

			$actions_html = '';

			while ( bp_activities() ) {
				bp_the_activity();

				$action = trim( strip_tags( bp_get_activity_action( [ 'no_timestamp' => true ] ), '<a>' ) );
				if ( 'activity_update' === bp_get_activity_type() && bp_activity_has_content() ) {
					$action .= ': ' . trim( bp_get_activity_content_body() );
				}

				$activity_type = bp_get_activity_type() ;
				$displayed_user_fullname = bp_get_displayed_user_fullname();
				$link_text_char_limit = 30;

				if ( $activity_type === 'updated_profile' ) {
					continue;
				}

				// some types end their action strings with ':' - remove it
				$action = preg_replace( '/:$/', '', $action );

				// replace "blog post" with "post"
				$action = str_ireplace( 'blog post', 'post', $action );

				// wrapper not only serves to contain the action text but also helps DOMDocument traverse the "tree" without breaking it
				$action = "<li class=\"$activity_type\">" . $action . '</li>';
				$action_doc = new DOMDocument;

				// encoding prevents mangling of multibyte characters
				// constants ensure no <body> or <doctype> tags are added
				$action_doc->loadHTML(
					mb_convert_encoding( $action, 'HTML-ENTITIES', 'UTF-8' ),
					LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
				);

				// for reasons yet unknown, removeChild() causes the next anchor to be skipped entirely.
				// using a second foreach is a workaround.
				foreach ( $action_doc->getElementsByTagName( 'a' ) as $anchor ) {
					if ( $anchor->nodeValue === $displayed_user_fullname ) {
						$anchor->parentNode->removeChild( $anchor );
						break;
					}
				}
				foreach ( $action_doc->getElementsByTagName( 'a' ) as $anchor ) {
					if ( strlen( $anchor->nodeValue ) > $link_text_char_limit ) {
						$anchor->nodeValue = substr( $anchor->nodeValue, 0, $link_text_char_limit - 1 ) . '…';
					}
				}

				$action = $action_doc->saveHTML();

				// only add actions which are unique in the feed
				if ( empty( $actions_html ) || strpos( $action, $actions_html ) === false ) {
					$actions_html .= $action;
				}
			}

			return "<ul>$actions_html</ul>";
		}
	}

	public function get_groups() {
		$html = '';
		$group_types = bp_groups_get_group_types();

		foreach ( $group_types as $group_type ) {
			$querystring = bp_ajax_querystring( 'groups' ) . '&' . http_build_query( [
				'group_type' => $group_type,
				// action & type are blank to override cookies setting filters from directory
				'action' => '',
				'type' => '',
				// use alpha order rather than whatever directory set
				'orderby' => 'name',
				'order' => 'ASC',
			] );

			if ( bp_has_groups( $querystring ) ) {
				$html .= '<h5>' . strtoupper( $group_type ) . '</h5>';
				$html .= '<ul class="group-type-' . $group_type . '">';
				while ( bp_groups() ) {
					bp_the_group();
					$html .= '<li><a href="' . bp_get_group_permalink() . '">' . bp_get_group_name() . '</a></li>';
				}
				$html .= '</ul>';
			}
		}

		return $html;
	}

	public function get_sites() {
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

	public function get_header_actions() {
		$html = '';

		ob_start();

		do_action( 'bp_member_header_actions' ); // buttons dependent on context
		bp_get_options_nav(); // nav links, but we're grouping everything together

		$html = ob_get_clean();

		$html_doc = new DOMDocument;

		// encoding prevents mangling of multibyte characters
		// constants ensure no <body> or <doctype> tags are added
		// wrapping <ul> ensures document is parsed with correct hierarchy so we can append a child
		$html_doc->loadHTML(
			mb_convert_encoding( '<ul>' . $html . '</ul>', 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		// move "edit" element to the end
		$edit_node = $html_doc->getElementById( 'edit-personal-li' );
		if ( $edit_node ) {
			$edit_node->firstChild->setAttribute(
				'class',
				$edit_node->firstChild->getAttribute( 'class' ) . ' button'
			);
			$html_doc->appendChild( $edit_node ); // this ends up after the <ul>, but we remove that anyway
		}

		$html = $html_doc->saveHTML();

		// remove wrapping <ul> now that DOMDocument is finished
		$html = preg_replace( '/<\/?ul>/', '', $html );

		// remove button class from action buttons
		$html = str_replace( 'generic-button', '', $html );

		// turn nav <li>s into <div>s
		$html = str_replace( '<li', '<div', $html );
		$html = str_replace( 'li>', 'div>', $html );

		return $html;
	}

	/**
	 * TODO find a way to directly access the field value without looping
	 */
	public function get_xprofile_field_data( $field_name = '' ) {
		global $profile_template;

		$args = [
			'profile_group_id' => Profile::get_instance()->xprofile_group->id,
			'hide_empty_fields' => false, // some custom fields are "empty" by design e.g. 'CORE Deposits'
		];

		$retval = '';

		if ( bp_has_profile( $args ) ) {
			while ( bp_profile_groups() ) {
				bp_the_profile_group();

				if ( bp_profile_group_has_fields() ) {
					while ( bp_profile_fields() ) {
						bp_the_profile_field();

						if ( bp_get_the_profile_field_name() === $field_name ) {
							$retval = bp_get_the_profile_field_value();
							break;
						}
					}
				}
			}
		}

		return apply_filters( 'commons_profile_field_value_' . sanitize_title( $field_name ), $retval );
	}

	/**
	 * helper function for url fields. handle user input including:
	 * username/path
	 * (for twitter) '@username'
	 * domain + username/path
	 * scheme + domain + username/path
	 *
	 * @param $field_name
	 * @return string normalized value
	 */
	public function get_normalized_url_field_value( $field_name ) {
		$domains = [
			Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME => 'twitter.com',
			Profile::XPROFILE_FIELD_NAME_FACEBOOK => 'facebook.com',
			Profile::XPROFILE_FIELD_NAME_LINKEDIN => 'linkedin.com/in',
			Profile::XPROFILE_FIELD_NAME_ORCID => 'orcid.org',
		];

		$patterns = [
			'#@#',
			'#(https?://)?(www\.)?' . preg_quote( $domains[ $field_name ], '#' ) . '/?#',
		];

		$value = strip_tags( preg_replace(
			$patterns,
			'',
			$this->get_xprofile_field_data( $field_name )
		) );

		if ( ! empty( $value ) ) {
			$value = "<a href=\"https://{$domains[ $field_name ]}/$value\">$value</a>";
		}

		return $value;
	}

	/**
	 * returns html linking to the twitter page of the user with the twitter handle as link text
	 */
	public function get_twitter_link() {
		return $this->get_normalized_url_field_value( Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME );
	}

	/**
	 * returns html linking to the orcid page of the user
	 */
	public function get_orcid_link() {
		return $this->get_normalized_url_field_value( Profile::XPROFILE_FIELD_NAME_ORCID );
	}

	/**
	 * returns html linking to the facebook page of the user
	 */
	public function get_facebook_link() {
		return $this->get_normalized_url_field_value( Profile::XPROFILE_FIELD_NAME_FACEBOOK );
	}

	/**
	 * returns html linking to the linkedin page of the user
	 */
	public function get_linkedin_link() {
		return $this->get_normalized_url_field_value( Profile::XPROFILE_FIELD_NAME_LINKEDIN );
	}

	public function get_username_link() {
		$html = '<a href="' . bp_get_send_private_message_link() . '" title="Send private message">';
		$html .= '@' . bp_get_displayed_user_username();
		$html .= '</a>';
		return $html;
	}

	public function get_site_link() {
		return $this->get_xprofile_field_data( Profile::XPROFILE_FIELD_NAME_SITE );
	}

	public function get_xprofile_field_visibility( $field_name = '' ) {
		foreach ( Profile::get_instance()->xprofile_group->fields as $field ) {
			if ( $field->name === $field_name ) {
				return xprofile_get_field_visibility_level( $field->id, bp_displayed_user_id() );
			}
		}
	}

	public function is_field_visible( $field_name = '' ) {
		return $this->get_xprofile_field_visibility( $field_name ) === 'public';
	}

	/**
	 * returns field data or edit form with header label wrapped in a div
	 */
	public function get_field( $field_name = '' ) {
		$always_hidden_fields = [];

		$user_hideable_fields = [
			Profile::XPROFILE_FIELD_NAME_ABOUT,
			Profile::XPROFILE_FIELD_NAME_EDUCATION,
			Profile::XPROFILE_FIELD_NAME_PUBLICATIONS,
			Profile::XPROFILE_FIELD_NAME_PROJECTS,
			Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES,
			Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS,
		];

		$show_more_fields = [
			Profile::XPROFILE_FIELD_NAME_PUBLICATIONS,
			Profile::XPROFILE_FIELD_NAME_CORE_DEPOSITS,
		];

		$classes = [
			sanitize_title( $field_name ),
		];

		if ( in_array( $field_name, $always_hidden_fields ) ) {
			$classes[] = 'hidden';
		}

		if ( in_array( $field_name, $user_hideable_fields ) ) {
			$classes[] = 'hideable';
		}

		if ( in_array( $field_name, $show_more_fields ) ) {
			$classes[] = 'show-more';
		}

		if ( bp_is_user_profile_edit() ) {
			$classes[] = 'editable';
			$content = $this->get_edit_field( $field_name );
		} else if ( $this->is_field_visible( $field_name ) ) {
			$content = $this->get_xprofile_field_data( $field_name );
		}

		if ( isset( $content ) && ! empty( $content ) ) {
			return sprintf(
				// must be on one line with no extra whitespace due to 'white-space: pre-wrap;'
				'<div class="%s"><h4>%s</h4>%s</div>',
				implode( ' ', $classes ),
				Profile::$display_names[ $field_name ],
				$content
			);
		}
	}
}
