<?php

namespace MLA\Commons\Profile;

use \Exception;
use \WP_CLI;

class CLI {

	public function export() {
		$export = new Export;
		return $export->write_xprofile_data_csv();
	}

	/**
	 * DO NOT RUN THIS UNLESS YOU KNOW WHAT YOU'RE DOING!
	 */
	public function migrate() {
		try {
			$migration = new Migration;

			WP_CLI::log( "Converting friends to followers" );
			$migration->convert_friends_to_followers();

			WP_CLI::log( "Creating xprofile group" );
			$migration->create_xprofile_group();

			WP_CLI::log( "Creating xprofile fields" );
			$migration->create_xprofile_fields();

			WP_CLI::log( "Migrating xprofile field data" );
			$migration->migrate_xprofile_field_data();

			WP_CLI::log( "Migrating academic interests" );
			$migration->migrate_academic_interests();
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( "Finished migration." );
	}

}
