<?php

namespace MLA\Commons;

use \BP_Component;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

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
		add_filter( 'load_template', [ $this, 'filter_load_template' ] );

		if ( bp_is_user_profile() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
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

	// TODO clean
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
		<?php else: ?>
			<p><?php _e( 'Sorry, there were no deposits found.', 'buddypress' ); ?></p>
		<?php endif;
	}
}
