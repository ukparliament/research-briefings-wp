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

		if($briefing['publisher']['prefLabel']['_value'] == 'House of Commons Library') {
		
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
	$checkTitle = str_replace('&', '&amp;', html_entity_decode($post['post_title']));
	$alreadyCreated = get_page_by_title($checkTitle, 'ARRAY_A', 'post');
	if(is_null($alreadyCreated)) {
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
		
		$alreadyCreated['post_status'] = 'publish';
		$alreadyCreated['post_date'] = $post['post_date'];
		$alreadyCreated['post_content'] = $post['post_content'];
		wp_update_post($alreadyCreated);
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
