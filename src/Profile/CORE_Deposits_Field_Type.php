<?php

namespace MLA\Commons\Profile;

class CORE_Deposits_Field_Type extends \BP_XProfile_Field_Type {

	public $name = 'CORE Deposits';

	public $accepts_null_value = true;

	public function __construct() {
		parent::__construct();
	}

	public static function display_filter( $field_value, $field_id = '' ) {
		// bail unless humcore is installed & active
		if ( ! function_exists( 'humcore_has_deposits' ) ) {
			return;
		}

		$genres = humcore_deposits_genre_list();

		// deposits display under one of these genre headers in this order
		$genres_order = [
			'Monograph',
			'Book',
			'Article',
			'Book chapter',
			'Book section',
			'Code',
			'Conference proceeding',
			'Dissertation',
			'Documentary',
			'Essay',
			'Fictional work',
			'Music',
			'Performance',
			'Photograph',
			'Poetry',
			'Thesis',
			'Translation',
			'Video essay',
			'Visual art',
			'Conference paper',
			'Course material or learning objects',
			'Syllabus',
			'Abstract',
			'Bibliography',
			'Blog Post',
			'Book review',
			'Catalog',
			'Chart',
			'Code',
			'Data set',
			'Finding aid',
			'Image',
			'Interview',
			'Map',
			'Presentation',
			'Report',
			'Review',
			'Technical report',
			'White paper',
			'Other',
		];

		// genres with a plural form not equal to the value returned by humcore_deposits_genre_list()
		$genres_pluralized = [
			'Abstract' => 'Abstracts',
			'Article' => 'Articles',
			'Bibliography' => 'Bibliographies',
			'Blog Post' => 'Blog Posts',
			'Book' => 'Books',
			'Book chapter' => 'Book chapters',
			'Book review' => 'Book reviews',
			'Book section' => 'Book sections',
			'Catalog' => 'Catalogs',
			'Chart' => 'Charts',
			'Conference paper' => 'Conference papers',
			'Conference proceeding' => 'Conference proceedings',
			'Data set' => 'Data sets',
			'Dissertation' => 'Dissertations',
			'Documentary' => 'Documentaries',
			'Essay' => 'Essays',
			'Fictional work' => 'Fictional works',
			'Finding aid' => 'Finding aids',
			'Image' => 'Images',
			'Interview' => 'Interviews',
			'Map' => 'Maps',
			'Monograph' => 'Monographs',
			'Performance' => 'Performances',
			'Photograph' => 'Photographs',
			'Presentation' => 'Presentations',
			'Report' => 'Reports',
			'Review' => 'Reviews',
			'Syllabus' => 'Syllabi',
			'Technical report' => 'Technical reports',
			'Thesis' => 'Theses',
			'Translation' => 'Translations',
			'Video essay' => 'Video essays',
			'White paper' => 'White papers',
		];

		$html = '';
		$genres_html = [];

		$displayed_user = bp_get_displayed_user();
		$querystring = http_build_query( [
			'username' => $displayed_user->userdata->user_login,
			'per_page' => 99,
		] );

		if ( humcore_has_deposits( $querystring ) ) {
			while ( humcore_deposits() ) {
				humcore_the_deposit();
				$metadata = (array) humcore_get_current_deposit();
				$item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );

				$genres_html[ $metadata['genre'] ][] = '<li><a href="' . esc_url( $item_url ) . '/">' . $metadata['title_unchanged'] . '</a></li>';
			}

			// sort results according to $genres_order
			$genres_html = array_filter(
				array_replace( array_flip( $genres_order ), $genres_html ),
				'is_array'
			);

			foreach ( $genres_html as $genre => $genre_html ) {
				$html .= '<h5>' . ( isset( $genres_pluralized[$genre] ) ? $genres_pluralized[$genre] : $genre ) . '</h5>';
				$html .= '<ul>' . implode( '', $genre_html ) . '</ul>';
			}
		}

		return $html;
	}

	public function edit_field_html( array $raw_properties = [] ) {
		echo self::display_filter();
	}

	public function admin_field_html( array $raw_properties = [] ) {
		echo 'This field is not editable.';
	}

}
