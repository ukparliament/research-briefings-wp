<?php
include_once('taxonomy.php');

add_action('wp', 'research_briefings_wp_redirector');
function research_briefings_wp_redirector() {
	if(in_category(research_briefings_wp_get_or_create_category()) AND is_singular()) {
		$identifier = get_post_meta( get_the_ID(), 'identifier', true );
		if ( ! empty( $identifier ) ) {
		    wp_redirect( 'https://researchbriefings.parliament.uk/ResearchBriefing/Summary/' . $identifier);
		    exit;
		}
	}
}
