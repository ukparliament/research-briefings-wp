<?php
/*
Plugin Name: Research Briefings WP
Plugin URI: http://github.com/ukparliament/research-briefings-wp
Description: Ingests and displays UK Parliament's Research Briefings as posts
Version: 1.0.0
Author: Jake Mulley
*/

if(!defined( 'WPINC' )) {
    die;
}

include_once('includes/taxonomy.php');
include_once('includes/ingester.php');
include_once('includes/redirector.php');
include_once('includes/crosstagger.php');
include_once('includes/category-images.php');

function research_briefings_wp_cron_activation() {
    if(!wp_next_scheduled('research_briefings_wp_cron_ingester')) {
        wp_schedule_event(time(), 'hourly', 'research_briefings_wp_cron_ingester');
    }
}
register_activation_hook( __FILE__, 'research_briefings_wp_cron_activation');

function research_briefings_wp_cron_deactivation() {
    $timestamp = wp_next_scheduled('research_briefings_wp_cron_ingester');
    wp_unschedule_event($timestamp, 'research_briefings_wp_cron_ingester');
}
register_deactivation_hook( __FILE__, 'research_briefings_wp_cron_deactivation');

add_action('research_briefings_wp_cron_ingester', 'research_briefings_wp_read_research_briefings');
?>
