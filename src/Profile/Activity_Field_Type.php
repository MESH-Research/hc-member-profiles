<?php

namespace MLA\Commons\Profile;

use \DOMDocument;
use \MLA\Commons\Profile;

class Activity_Field_Type extends \BP_XProfile_Field_Type {

	public $name = Profile::XPROFILE_FIELD_NAME_ACTIVITY;

	public $accepts_null_value = true;

	public function __construct() {
		parent::__construct();
	}

	public static function display_filter( $field_value, $field_id = '' ) {
		$max = 5;

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

	public function edit_field_html( array $raw_properties = [] ) {
		echo self::display_filter();
	}

	public function admin_field_html( array $raw_properties = [] ) {
		echo 'This field is not editable.';
	}

}
