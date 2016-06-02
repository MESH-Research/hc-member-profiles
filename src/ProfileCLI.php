<?php

namespace MLA\Commons;

use \WP_CLI;
use \BP_Follow;

class ProfileCLI {
	function friends_to_followers( $args, $assoc_args ) {
		global $wpdb;

		$sql = "SELECT * FROM wp_bp_friends";
		$rows = $wpdb->get_results($sql, ARRAY_A);

		$progress = WP_CLI\Utils\make_progress_bar( 'Migrating friends to followers:', count( $rows ) );

		try {
			foreach( $rows as $row ){
				//print_r($row);
				extract( $row );

				$follow = new BP_Follow( $initiator_user_id, $friend_user_id );
				$follow->save();

				if( $is_confirmed == 1 ){
					$follow = new BP_Follow( $friend_user_id, $initiator_user_id );
					$follow->save();
				}

				$progress->tick();
			}
		} catch ( Exception $e ) {
			WP_CLI::error( "Something went wrong: " . $e->getMessage() );
		} finally {
			$progress->finish();
			WP_CLI::success( "Finished migrating friends to followers." );
		}
	}

}

if( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'profile', __NAMESPACE__ . '\ProfileCLI' );
}
