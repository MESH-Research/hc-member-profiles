<?php

namespace MLA\Commons;

use \DOMDocument;

class Template {

	/**
	 * for edit view. use like bp_the_profile_field().
	 * works inside or outside the fields loop.
	 *
	 * @param $field_name
	 * @return string html
	 */
	static function get_edit_field( $field_name ) {
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
	 * Return html representing member header actions & options nav
	 *
	 * @return string html
	 */
	static function get_header_actions() {
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
	static public function get_xprofile_field_data( $field_name = '' ) {
		$args = [
			'profile_group_id' => Profile::get_instance()->xprofile_group->id,
			'hide_empty_fields' => false, // some custom fields are "empty" by design e.g. 'CORE Deposits'
		];

		$retval = '';

		bp_has_profile( $args );
		bp_profile_groups();
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
	static function get_normalized_url_field_value( $field_name ) {
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
			self::get_xprofile_field_data( $field_name )
		) );

		if ( ! empty( $value ) ) {
			$value = "<a href=\"https://{$domains[ $field_name ]}/$value\">$value</a>";
		}

		return $value;
	}

	/**
	 * getter for username as link to private message the displayed user
	 *
	 * @return string html
	 */
	static function get_username_link() {
		$html = '<a href="' . bp_get_send_private_message_link() . '" title="Send private message">';
		$html .= '@' . bp_get_displayed_user_username();
		$html .= '</a>';
		return $html;
	}

	/**
	 * check if a field is visible
	 *
	 * @param $field_name
	 * @return bool whether field is public or not
	 */
	static function is_field_visible( string $field_name = '' ) {
		foreach ( Profile::get_instance()->xprofile_group->fields as $field ) {
			if ( $field->name === $field_name ) {
				return xprofile_get_field_visibility_level( $field->id, bp_displayed_user_id() ) === 'public';
			}
		}
	}

	/**
	 * getter for complete field html
	 * automatically renders either edit or view mode based on current action
	 *
	 * @param $field_name
	 * @return string html representing field data or edit form with header label wrapped in a div
	 */
	static function get_field( string $field_name = '' ) {
		$always_hidden_fields = [
			Profile::XPROFILE_FIELD_NAME_NAME,
			Profile::XPROFILE_FIELD_NAME_TITLE,
			Profile::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION
		];

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
			Profile::XPROFILE_FIELD_NAME_GROUPS,
			Profile::XPROFILE_FIELD_NAME_BLOGS,
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
			$content = self::get_edit_field( $field_name );
		} else if ( self::is_field_visible( $field_name ) ) {
			$content = self::get_xprofile_field_data( $field_name );
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

	/**
	 * filter teeny mce args to mimick bbpress for consistent ux
	 *
	 * @param array $args teeny mce args
	 * @return array filtered teeny mce args
	 */
	static function filter_teeny_mce_before_init( array $args ) {
		$args['plugins'] = 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview,wpembed,image';
		$args['toolbar1'] = 'bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,tabindent,link,unlink,spellchecker,print,paste,undo,redo';
		$args['toolbar3'] = 'tablecontrols';

		return $args;
	}

	/**
	 * filter allowed tags to add a few for formatting xprofile field values
	 *
	 * @param array $allowed_tags
	 * @return array filtered allowed tags
	 */
	static function filter_xprofile_allowed_tags( $allowed_tags ) {
		$allowed_tags['br'] = [];
		$allowed_tags['ul'] = [];
		$allowed_tags['li'] = [];
		return $allowed_tags;
	}

	/**
	 * scripts/styles that apply on profile & related pages only
	 *
	 * @return null
	 */
	static function enqueue_local_scripts() {
		wp_enqueue_style( 'mla-commons-profile-local', plugins_url() . '/profile/css/profile.css' );
		wp_enqueue_script( 'mla-commons-profile-jqdmh', plugins_url() . '/profile/js/lib/jquery.dynamicmaxheight.min.js' );
		wp_enqueue_script( 'mla-commons-profile-local', plugins_url() . '/profile/js/main.js' );
	}

	/**
	 * register plugin template overrides with buddypress
	 *
	 * @return string plugin templates dir
	 */
	static function register_template_stack() {
		return Profile::$plugin_templates_dir;
	}

	/**
	 * filter admin bar to change "Portfolio" to "Profile"
	 *
	 * @return null
	 */
	static function filter_admin_bar() {
		global $wp_admin_bar;

		// Portfolio -> Profile
		foreach ( [ 'my-account-xprofile', 'my-account-settings-profile' ] as $field_id ) {
			$clone = $wp_admin_bar->get_node( $field_id );
			if ( $clone ) {
				$clone->title = 'Profile';
				$wp_admin_bar->add_menu( $clone );
			}
		}
	}
}
