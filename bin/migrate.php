<?php
/**
 * Migrate script for new field types.
 *
 * @package HC_Member_Profiles
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// For some reason (BP bug?) this is not correctly populated with the filtered value during eval-file - force it here.
buddypress()->profile->field_types = array_keys( hcmp_register_xprofile_field_types( array_keys( buddypress()->profile->field_types ) ) );

// Update real "Name" field to use correct name.
$name_field       = xprofile_get_field( 1 );
$name_field->name = 'Name';
$name_field->save();

// Update some existing field groups: site, deposits, cv.
foreach ( [ 16, 1000028, 1000029 ] as $id ) {
	$field           = xprofile_get_field( $id );
	$field->group_id = 1;
	$field->save();
}

// Delete deprecated fields.
$field_ids_to_delete = [
	2,  // Institutional or Other Affiliation (old).
	3,  // Title (old).
	4,  // <em>Twitter</em> user name.
	5,  // Blog.
	7,  // Interests.
	9,  // Academic Interests.
	10, // Education.
	11, // Publications.
	12, // Site.
	13, // Name.
];

foreach ( $field_ids_to_delete as $id ) {
	WP_CLI::log( "deleting field $id" );
	xprofile_delete_field( $id );
}

// Move existing fields & create new ones.
$result = _hcmp_create_xprofile_fields();




/*
Expected before:

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


Expected after:

+---------+------------------------------------+-------------+--------------------+----------+-------------+
| id      | name                               | description | type               | group_id | is_required |
+---------+------------------------------------+-------------+--------------------+----------+-------------+
| 1       | Name                               |             | textbox            | 1        | 1           |
| 14      | Institutional or Other Affiliation |             | textbox            | 1        | 0           |
| 15      | Title                              |             | textbox            | 1        | 0           |
| 16      | Site                               |             | textbox            | 1        | 0           |
| 17      | <em>Twitter</em> handle            |             | textbox            | 1        | 0           |
| 18      | <em>ORCID</em> iD                  |             | textbox            | 1        | 0           |
| 19      | About                              |             | textarea           | 1        | 0           |
| 20      | Education                          |             | textarea           | 1        | 0           |
| 21      | Other Publications                 |             | textarea           | 1        | 0           |
| 22      | Projects                           |             | textarea           | 1        | 0           |
| 23      | Upcoming Talks and Conferences     |             | textarea           | 1        | 0           |
| 24      | Memberships                        |             | textarea           | 1        | 0           |
| 1000025 | Facebook URL                       |             | url                | 1        | 0           |
| 1000026 | LinkedIn URL                       |             | url                | 1        | 0           |
| 1000027 | Website URL                        |             | url                | 1        | 0           |
| 1000028 | CORE Deposits                      |             | core_deposits      | 1        | 0           |
| 1000029 | CV                                 |             | bp_attachment      | 1        | 0           |
| 1000030 | Academic Interests                 |             | academic_interests | 1        | 0           |
| 1000031 | Commons Groups                     |             | bp_groups          | 1        | 0           |
| 1000032 | Recent Commons Activity            |             | bp_activity        | 1        | 0           |
| 1000033 | Commons Sites                      |             | bp_blogs           | 1        | 0           |
+---------+------------------------------------+-------------+--------------------+----------+-------------+

*/
