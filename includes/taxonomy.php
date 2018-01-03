<?php
require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

function research_briefings_wp_get_or_create_category() {
	$category_name = 'Briefing paper';
	$category = get_term_by('name', $category_name, 'category');
	if(!$category) {
		$category_id = wp_create_category($category_name);
	} else {
		$category_id = $category->term_id;
	}
	return $category_id;
}
