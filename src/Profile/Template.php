<?php

namespace MLA\Commons\Profile;

use \DOMDocument;
use \MLA\Commons\Profile;

class Template {

	public function get_follow_counts() {
		$follow_counts = 0;

		if ( function_exists( 'bp_follow_total_follow_counts' ) ) {
			$follow_counts = \bp_follow_total_follow_counts( [ 'user_id' => \bp_displayed_user_id() ] );
		}

		return $follow_counts;
	}

	public function get_academic_interests() {
		$tax = \get_taxonomy( 'mla_academic_interests' );
		$interests = \wpmn_get_object_terms( \bp_displayed_user_id(), 'mla_academic_interests', array( 'fields' => 'names' ) );
		$html = '<ul>';
		foreach ( $interests as $term_name ) {
			$search_url = \add_query_arg( array( 's' => urlencode( $term_name ) ), \bp_get_members_directory_permalink() );
			$html .= '<li><a href="' . \esc_url( $search_url ) . '" rel="nofollow">';
			$html .=  $term_name;
			$html .= '</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	public function get_academic_interests_edit() {
		global $mla_academic_interests;

		$tax = \get_taxonomy( 'mla_academic_interests' );

		$interest_list = $mla_academic_interests->mla_academic_interests_list();
		$input_interest_list = \wpmn_get_object_terms( \bp_displayed_user_id(), 'mla_academic_interests', [ 'fields' => 'names' ] );

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

	/**
	 * for edit view. use like bp_the_profile_field().
	 * works inside or outside the fields loop.
	 */
	public function get_edit_field( $field_name ) {
		\bp_has_profile( [ 'profile_group_id' => Profile::get_instance()->xprofile_group->id ] ); // select our group
		\bp_the_profile_group(); // start (abuse) the loop

		$html = '';

		while ( \bp_profile_fields() ) {
			\bp_the_profile_field();

			if ( \bp_get_the_profile_field_name() !== $field_name ) {
				continue;
			}

			ob_start();

			$field_type = \bp_xprofile_create_field_type( \bp_get_the_profile_field_type() );

			$field_type->edit_field_html();

			\do_action( 'bp_custom_profile_edit_fields_pre_visibility' );
			\bp_profile_visibility_radio_buttons();

			\do_action( 'bp_custom_profile_edit_fields' );

			$html = ob_get_clean();

			break; // once we output the field we want, no need to continue looping
		}

		return $html;
	}

	/**
	 * @uses DOMDocument
	 */
	public function get_activity( $max = 5 ) {
		if ( \bp_has_activities( \bp_ajax_querystring( 'activity' ) . "&max=$max&scope=just-me" ) ) {

			$actions_html = '';

			while ( \bp_activities() ) {
				\bp_the_activity();

				$action = trim( strip_tags( \bp_get_activity_action( [ 'no_timestamp' => true ] ), '<a>' ) );
				$activity_type = \bp_get_activity_type() ;
				$displayed_user_fullname = \bp_get_displayed_user_fullname();
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

		if ( \bp_has_groups( \bp_ajax_querystring( 'groups' ) ) ) {
			$html = '<ul>';
			while ( \bp_groups() ) {
				\bp_the_group();
				$html .= '<li><a href="' . \bp_get_group_permalink() . '">' . \bp_get_group_name() . '</a></li>';
			}
			$html .= '</ul>';
		}

		return $html;
	}

	public function get_sites() {
		$html = '';

		if ( \bp_has_blogs( \bp_ajax_querystring( 'blogs' ) ) ) {
			$html .= '<ul>';
			while ( \bp_blogs() ) {
				\bp_the_blog();
				$html .= '<li><a href="' . \bp_get_blog_permalink() . '">' . \bp_get_blog_name() . '</a></li>';
			}
			$html .= '</ul>';
		}

		return $html;
	}

	public function get_core_deposits() {
		$html = '';

		// bail unless humcore is installed & active
		if ( ! function_exists( 'humcore_has_deposits' ) ) {
			return $html;
		}

		$querystring = sprintf( 'facets[author_facet][]=%s', urlencode( bp_get_displayed_user_fullname() ) );

		if ( \humcore_has_deposits( $querystring ) ) {
			$html = '<ul>';

			while ( \humcore_deposits() ) {
				\humcore_the_deposit();
				$metadata = (array) \humcore_get_current_deposit();
				$item_url = sprintf( '%1$s/deposits/item/%2$s', \bp_get_root_domain(), $metadata['pid'] );
				$html .= '<li><a href="' . \esc_url( $item_url ) . '/">' . $metadata['title_unchanged'] . '</a></li>';
			}

			$html .= '</ul>';
		}

		return $html;
	}

	public function get_header_actions() {
		$html = '';

		ob_start();

		\do_action( 'bp_member_header_actions' ); // buttons dependent on context
		\bp_get_options_nav(); // nav links, but we're grouping everything together

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

	public function get_xprofile_field_data( $field_name = '' ) {
		foreach ( Profile::get_instance()->xprofile_group->fields as $field ) {
			if ( $field->name === $field_name ) {
				return $field->get_field_data( bp_displayed_user_id() )->value;
			}
		}
	}

	/**
	 * returns html linking to the twitter page of the user with the twitter handle as link text
	 */
	public function get_twitter_link() {
		$value = str_replace( '@', '', $this->get_xprofile_field_data( Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME ) );

		if ( ! empty( $value ) ) {
			$value = "<a href=\"https://twitter.com/$value\">@$value</a>";
		}

		return $value;
	}

	/**
	 * returns html linking to the twitter page of the user with the twitter handle as link text
	 */
	public function get_orcid_link() {
		$value = $this->get_xprofile_field_data( Profile::XPROFILE_FIELD_NAME_ORCID );

		if ( ! empty( $value ) ) {
			$value = "<a href=\"http://orcid.org/$value\">$value</a>";
		}

		return $value;
	}

	public function get_username_link() {
		$html = '<a href="' . bp_get_send_private_message_link() . '" title="Send private message">';
		$html .= '@' . bp_get_displayed_user_username();
		$html .= '</a>';
		return $html;
	}

	public function get_site_link() {
		$value = $this->get_xprofile_field_data( Profile::XPROFILE_FIELD_NAME_SITE );

		if ( ! empty( $value ) ) {
			$url = $value;

			// add scheme to value if necessary to create (hopefully) valid url for href
			if ( strpos( $value, 'http' ) !== 0 ) {
				$url = 'http://' . $value;
			}

			$value = "<a href=\"$url\">$value</a>";
		}

		return $value;
	}

	public function get_xprofile_field_visibility( $field_name = '' ) {
		foreach ( Profile::get_instance()->xprofile_group->fields as $field ) {
			if ( $field->name === $field_name ) {
				return \xprofile_get_field_visibility_level( $field->id, bp_displayed_user_id() );
			}
		}
	}

	public function is_field_visible( $field_name = '' ) {
		return $this->get_xprofile_field_visibility( $field_name ) === 'public';
	}

	/**
	 * returns field data with header label wrapped in a div
	 */
	public function get_field( $field_name = '' ) {
		$html = '';
		if ( $this->is_field_visible( $field_name ) ) {
			$html .= '<div class="' . \sanitize_title( $field_name ) . '">';
			$html .= "<h4>$field_name</h4>";
			$html .= $this->get_xprofile_field_data( $field_name );
			$html .= '</div>';
		}
		return $html;
	}
}
