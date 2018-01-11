<?php
global $wpdb;

include_once('taxonomy.php');

function research_briefings_wp_read_research_briefings() {
	static $called = false;

	// If currently called, stop.
	if(isset($called) && $called) {
		return false;
	}

	$research_briefings = file_get_contents('https://lda.data.parliament.uk/researchbriefings.json?_pageSize=20');

	// If file_get_contents fails, stop.
	if(!$research_briefings) {
		return false;
	}

	$research_briefings_json = json_decode($research_briefings, true);

	foreach($research_briefings_json['result']['items'] as $briefing) {
		$meta = array(
			'identifier' => $briefing['identifier']['_value'],
			'topics'     => $briefing['topic']
		);

		if($briefing['publisher']['prefLabel']['_value'] == 'House of Commons Library' && $briefing['title'] !== 'Autumn Budget & Finance (No.2) Bill 2017') {
			$post = array(
				'post_title'     => wp_strip_all_tags( $briefing['title'] ),
				'post_content'   => $briefing['description'][0],
				'post_status'    => 'publish',
				'post_type'      => 'post',
				'post_date'      => date('Y-m-d H:i:s', strtotime($briefing['date']['_value'])),
				'comment_status' => 'closed',
				'meta_input'     => $meta
			);

			research_briefings_wp_create_research_briefing($post);
		}

	}

}

function research_briefings_wp_create_research_briefing($post) {
	// If the post doesn't already exist, create it
	$prevPost = get_page_by_title(html_entity_decode($post['post_title']), 'OBJECT', 'post');
	if(is_null($prevPost)) {
		// Get categories to attach to
		$categories_to_attach = research_briefings_wp_get_categories_to_attach($post);

		$insert = wp_insert_post($post);
		wp_set_object_terms($insert, $categories_to_attach, 'category');

		$actualRbImage = null;
		foreach ($categories_to_attach as $cat) {
			$imageId = get_term_meta($cat, 'rb_image', true);
			if($imageId) {
				$actualRbImage = $imageId;
			}
		}

		if($actualRbImage) {
			// Set post thumbnail
			set_post_thumbnail(
				(int) $insert,
				(int) $actualRbImage
			);
		}

	} else {
		$post['post_status'] = 'publish';
		wp_update_post($post);
	}
}

function research_briefings_wp_get_categories_to_attach($post) {
	// Get overall category (Briefing papers)
	$overall_cat_id = research_briefings_wp_get_or_create_category();

	// Get crosstagged categories
	$crosstagged = get_option('research_briefings_crosstagged');

	// Reset category array with original 'overall' category
	$categoryArray = array($overall_cat_id);

	// Briefing topics
	$postTopics = $post['meta_input']['topics'];
	$noramlisedTopics = [];

	// Get crosstagged categories
	if(is_array($postTopics)) {
		foreach($postTopics as $postTopic) {
			array_push($noramlisedTopics, $postTopic['prefLabel']['_value']);
		}
		foreach($crosstagged as $crosstaggedKey => $crosstaggedValue) {
			foreach($crosstagged[$crosstaggedKey] as $taggedTopicKey => $taggedTopicValue) {
				if(in_array($taggedTopicKey, $noramlisedTopics)) {
					array_push($categoryArray, $crosstaggedKey);
				}
			}
		}
	}

	return $categoryArray;
}
