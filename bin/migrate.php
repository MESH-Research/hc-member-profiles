<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// For some reason (BP bug?) this is not correctly populated with the filtered value during eval-file - force it here.
buddypress()->profile->field_types = array_keys( hcmp_register_xprofile_field_types( array_keys( buddypress()->profile->field_types ) ) );

//$delete_all_fields = function() {
//	for ( $i = 1; $i < 30; $i++ ) {
//		xprofile_delete_field( $i );
//	}
//	for ( $i = 1000000; $i < 1000030; $i++ ) {
//		xprofile_delete_field( $i );
//	}
//};
//$delete_all_fields();
//die;

// Update real "Name" field to use correct name.
$name_field = xprofile_get_field( 1 );
$name_field->name = 'Name';
$name_field->save();

// Update some existing field groups: site, deposits, cv.
foreach ( [ 16, 1000028, 1000029 ] as $id ) {
	$field = xprofile_get_field( $id );
	$field->group_id = 1;
	$field->save();
}

// Delete deprecated fields.

$field_ids_to_delete = [
	2,  // Institutional or Other Affiliation (old)
	3,  // Title (old)
	4,  // <em>Twitter</em> user name
	5,  // Blog
	7,  // Interests
	9,  // Academic Interests
	10, // Education
	11, // Publications
	12, // Site
	13, // Name
];

foreach ( $field_ids_to_delete as $id ) {
	WP_CLI::log( "deleting field $id" );
	xprofile_delete_field( $id );
}

// Move existing fields & create new ones.

$result = _hcmp_create_xprofile_fields();




/*


+---------+---------------------------------------+----------------------------------------+---------------+----------+-------------+
| id      | name                                  | description                            | type          | group_id | is_required |
+---------+---------------------------------------+----------------------------------------+---------------+----------+-------------+
| 1       | Name (old)                            |                                        | textbox       | 1        | 1           |
| 2       | Institutional or Other Affiliation (o | e.g., &quot;College of Yoknapatawpha&q | textbox       | 1        | 0           |
|         | ld)                                   | uot;                                   |               |          |             |
| 3       | Title (old)                           | e.g., &quot;Adjunct Instructor&quot;   | textbox       | 1        | 0           |
| 4       | <em>Twitter</em> user name            |                                        | textbox       | 1        | 0           |
| 5       | Blog                                  |                                        | textbox       | 1        | 0           |
| 7       | Interests                             | Tell us something about yourself!      | textarea      | 1        | 0           |
| 9       | Academic Interests                    |                                        | textbox       | 1        | 0           |
| 10      | Education                             |                                        | textbox       | 1        | 0           |
| 11      | Publications                          |                                        | textbox       | 1        | 0           |
| 12      | Site                                  |                                        | textbox       | 1        | 0           |
| 13      | Name                                  |                                        | textbox       | 2        | 0           |
| 14      | Institutional or Other Affiliation    |                                        | textbox       | 2        | 0           |
| 15      | Title                                 |                                        | textbox       | 2        | 0           |
| 16      | Site                                  |                                        | textbox       | 2        | 0           |
| 17      | <em>Twitter</em> handle               |                                        | textbox       | 2        | 0           |
| 18      | <em>ORCID</em> iD                     |                                        | textbox       | 2        | 0           |
| 19      | About                                 |                                        | textarea      | 2        | 0           |
| 20      | Education                             |                                        | textarea      | 2        | 0           |
| 21      | Other Publications                    |                                        | textarea      | 2        | 0           |
| 22      | Projects                              |                                        | textarea      | 2        | 0           |
| 23      | Upcoming Talks and Conferences        |                                        | textarea      | 2        | 0           |
─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────

*/
