<?php

namespace MLA\Commons;

use \BP_Component;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \DOMDocument;

class Profile extends BP_Component {
	protected static $instance;

	public $plugin_dir;
	public $plugin_templates_dir;
	public $template_files;

	public function __construct() {
		$this->plugin_dir = plugin_dir_path( __DIR__ . '/../..' );
		$this->plugin_templates_dir = trailingslashit( $this->plugin_dir . 'templates' );
		$this->template_files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $this->plugin_templates_dir ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		add_action( 'bp_init', [ $this, 'init' ] );
	}

	public static function get_instance() {
		return self::$instance = ( null === self::$instance ) ? new self : self::$instance;
	}

	public function init() {
		if ( ! bp_is_user_change_avatar() && ( bp_is_user_profile() || bp_is_user_profile_edit() ) ) {
			add_filter( 'load_template', [ $this, 'filter_load_template' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'xprofile_updated_profile', [ $this, 'save_academic_interests' ] );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'mla_commons_profile_css', plugins_url() . '/profile/css/main.css' );
		wp_enqueue_script( 'mla_commons_profile_js', plugins_url() . '/profile/js/main.js' );
	}

	public function filter_load_template( $path ) {
		$their_slug = str_replace( trailingslashit( STYLESHEETPATH ), '', $path );

		foreach( $this->template_files as $name => $object ){
			$our_slug = str_replace( $this->plugin_templates_dir, '', $name );

			if ( $our_slug === $their_slug ) {
				return $name;
			}
		}

		return $path;
	}

	/**
	 * @uses bp_follow_total_follow_counts()
	 */
	public function get_follow_counts() {
		$follow_counts = 0;

		if ( function_exists( 'bp_follow_total_follow_counts' ) ) {
			$follow_counts = bp_follow_total_follow_counts();
		} else {
			// TODO log error
		}

		return $follow_counts;
	}

	public function get_academic_interests() {
		$tax = get_taxonomy( 'mla_academic_interests' );
		$interests = wp_get_object_terms( bp_displayed_user_id(), 'mla_academic_interests', array( 'fields' => 'names' ) );
		$html = '<ul>';
		foreach ( $interests as $term_name ) {
			$search_url = add_query_arg( array( 's' => urlencode( $term_name ) ), bp_get_members_directory_permalink() );
			$html .= '<li><a href="' . esc_url( $search_url ) . '" rel="nofollow">' . $term_name . '</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	public function get_academic_interests_edit() {
		global $mla_academic_interests;

		$tax = get_taxonomy( 'mla_academic_interests' );

		$html = '<span class="description">Enter interests from the existing list, or add new interests if needed.</span><br />';
		$html .= '<select name="academic-interests[]" class="js-basic-multiple-tags interests" multiple="multiple" data-placeholder="Enter interests.">';

		$interest_list = $mla_academic_interests->mla_academic_interests_list();
		$input_interest_list = wp_get_object_terms( bp_displayed_user_id(), 'mla_academic_interests', array( 'fields' => 'names' ) );

		foreach ( $interest_list as $interest_key => $interest_value ) {
			$html .= sprintf('			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
				( in_array( $interest_key, $input_interest_list ) ) ? 'selected="selected"' : '',
				$interest_key,
				$interest_value
			);
		}

		$html .= '</select>';

		echo $html;
	}

	public function save_academic_interests( $user_id ) {
		$tax = get_taxonomy( 'mla_academic_interests' );

		// If array add any new keywords.
		if ( is_array( $_POST['academic-interests'] ) ) {
			foreach ( $_POST['academic-interests'] as $term_id ) {
				$term_key = term_exists( $term_id, 'mla_academic_interests' );
				if ( empty( $term_key ) ) {
					$term_key = wp_insert_term( sanitize_text_field( $term_id ), 'mla_academic_interests' );
				}
				if ( ! is_wp_error( $term_key ) ) {
					$term_ids[] = intval( $term_key['term_id'] );
				} else {
					error_log( '*****CAC Academic Interests Error - bad tag*****' . var_export( $term_key, true ) );
				}
			}
		}

		// Set object terms for tags.
		$term_taxonomy_ids = wp_set_object_terms( $user_id, $term_ids, 'mla_academic_interests' );
		clean_object_term_cache( $user_id, 'mla_academic_interests' );

		// Set user meta for theme query.
		delete_user_meta( $user_id, 'academic_interests' );
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			add_user_meta( $user_id, 'academic_interests', $term_taxonomy_id, $unique = false );
		}
	}

	/**
	 * for edit view. use like bp_the_profile_field().
	 * works inside or outside the fields loop.
	 * TODO optimize: find some way to look up fields directly rather than (re)winding the loop every time.
	 */
	public function get_edit_field( $field_name ) {
		global $profile_template;

		$profile_template->rewind_fields(); // reset the loop

		while ( bp_profile_fields() ) {
			bp_the_profile_field();

			if ( bp_get_the_profile_field_name() !== $field_name ) {
				continue;
			}

			$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
			$field_type->edit_field_html();

			do_action( 'bp_custom_profile_edit_fields_pre_visibility' );
			bp_profile_visibility_radio_buttons();

			do_action( 'bp_custom_profile_edit_fields' );
			break; // once we output the field we want, no need to continue looping
		}
	}

	public function get_activity( $max = 5 ) {
		if ( bp_has_activities( bp_ajax_querystring( 'activity' ) . "&max=$max&scope=just-me" ) ) {
			echo '<ul>';

			while ( bp_activities() ) {
				bp_the_activity();

				$action = trim( strip_tags( bp_get_activity_action( [ 'no_timestamp' => true ] ), '<a>' ) );
				$activity_type = bp_get_activity_type() ;
				$displayed_user_fullname = bp_get_displayed_user_fullname();
				$link_text_char_limit = 30;
				// shorten/change some action descriptions
				switch ( $activity_type ) {
				case 'updated_profile':
					$action = "updated profile"; // default action is "<name>'s profile was updated"
					break;
				}
				// some types end their action strings with ':' - remove it
				$action = preg_replace( '/:$/', '', $action );
				// div wrapper not only serves to contain the action text but also helps DOMDocument traverse the "tree" without breaking it
				$action = "<li class=\"$activity_type\">" . $action . '</li>';
				$action_doc = new DOMDocument;
				// encoding prevents mangling of multibyte characters
				// constants ensure no <body> or <doctype> tags are added
				$action_doc->loadHTML( mb_convert_encoding( $action, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
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
				echo $action;
			}

			echo '</ul>';
		}
	}

	public function get_groups() {
		if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) {
			echo '<ul>';
			while ( bp_groups() ) {
				bp_the_group();
				?>
				<li>
					<a href="<?php bp_group_permalink(); ?>">
						<span>
						<?php
							//echo str_replace( ' ', '</span><span>', bp_get_group_name() );
							echo bp_get_group_name();
						?>
						</span>
					</a>
				</li>
				<?
			}
			echo '</ul>';
		}
	}

	public function get_sites() {
		if ( bp_has_blogs( bp_ajax_querystring( 'blogs' ) ) ) {
			echo '<ul>';
			while ( bp_blogs() ) {
				bp_the_blog();
				?>
				<li>
					<a href="<?php bp_blog_permalink(); ?>">
						<span>
						<?php
							//echo str_replace( ' ', '</span><span>', bp_get_blog_name() );
							echo bp_get_blog_name();
						?>
						</span>
					</a>
				</li>
				<?php
			}
			echo '</ul>';
		}
	}

	public function get_core_deposits() {
		$my_querystring = sprintf( 'facets[author_facet][]=%s', urlencode( bp_get_displayed_user_fullname() ) );
		// If the ajax string is empty, that usually means that
		// it's the first page of the "everything" filter.
		$querystring = bp_ajax_querystring( 'deposits' );
		if ( empty( $querystring ) ) {
			$querystring = $my_querystring;
		} else {
			$querystring = implode( '&', array( $my_querystring, $querystring ) );
		}
		if ( humcore_has_deposits( $querystring ) ):
		?>
			<ul>
			<?php while ( humcore_deposits() ) : humcore_the_deposit(); ?>
				<?php
					$metadata = (array) humcore_get_current_deposit();
					$item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );
				?>
				<li>
					<a href="<?php echo esc_url( $item_url ); ?>/"><?php echo $metadata['title_unchanged']; ?></a>
				</li>
			<?php endwhile; ?>
			</ul>
		<?php endif;
	}

	/**
	 * no filter exists for these, so DIY
	 */
	public function get_header_actions() {
		$html = '';

		ob_start();

		do_action( 'bp_member_header_actions' ); // buttons dependent on context
		bp_get_options_nav(); // nav links, but we're grouping everything together

		$html = ob_get_clean();

		$html = preg_replace( '/generic-button/', '', $html ); // remove button class from action buttons

		// turn nav <li>s into <div>s
		$html = preg_replace( '/<li/', '<div', $html );
		$html = preg_replace( '/li>/', 'div>', $html );

		return $html;
	}

}
