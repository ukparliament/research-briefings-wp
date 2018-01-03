<?php
include_once('taxonomy.php');

add_action('admin_menu', 'research_briefings_wp_crosstagger_page');
function research_briefings_wp_crosstagger_page() {
	add_menu_page(
		'Research Briefings',
		'Research Briefings',
		'manage_options',
		'research-briefings',
		'research_briefings_wp_page_callback',
		plugins_url( '../icon.svg', __FILE__ ),
		'100'
	);
}

/**
 * Get custom CSS for admin
 */
function research_briefings_load_crosstagger_styles($hook) {
    // Load only on ?page=mypluginname
    if($hook != 'toplevel_page_research-briefings') {
        return;
    }
    wp_enqueue_script( 'custom_wp_admin_css', plugins_url('../public/js/drag-drop.js', __FILE__) );
    wp_enqueue_style( 'custom_wp_admin_css', plugins_url('../public/css/ingester.css', __FILE__) );
}
add_action( 'admin_enqueue_scripts', 'research_briefings_load_crosstagger_styles' );

function research_briefings_wp_page_callback() {
	if( isset($_POST['research-briefings-crosstag-update']) ) {
            update_option('research_briefings_crosstagged', $_POST['crosstagged-category']);
            $updated = true;
        }
        $currentSettings = get_option('research_briefings_crosstagged');
    ?>

    <div class="wrap">
        <h1>Ingester Information</h1>
        <p>The Ingester runs every hour. It will add any new items.</p>
        <div class="research-briefings-ingester">
            <?php if($updated): ?>
                <div class="notice notice-success"><p>Crosstags updated.</p></div>
            <?php endif; ?>
            <div class="research-briefings-column">
                <h2>Second Reading Categories</h2>
                <?php echo research_briefings_format_wp_categories($currentSettings); ?>
            </div>
            <div class="research-briefings-column">
                <h2>Unassigned Research Briefings Topics</h2>
                <ul class="research-briefings-sortable research-briefings-unassigned">
                    <?php echo research_briefings_format_rb_topics($currentSettings); ?>
                </ul>
            </div>
        </div>
        <form class="research-briefings-form" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
            <input type="hidden" name="research-briefings-crosstag-update" value="1">
            <!-- preserve previously set data -->
            <?php
                foreach ($currentSettings as $key => $value) {
                    foreach ($currentSettings[$key] as $topKey => $topVal) {
                        echo '<input class="hidden" type="text" name="crosstagged-category['.$key.']['.$topKey.']" value="'.$topVal.'">';
                    }
                }
            ?>
            <input type="submit" class="button action" value="Save">
        </form>
    </div>
    <?php
}

// Needs writing
function research_briefings_format_wp_categories($currentSettings) {
    $categories = get_categories(array('hide_empty' => false));
    foreach ($categories as $category) {
        $alreadySet = false;
        $extraLi = '';
        if(array_key_exists($category->term_id, $currentSettings)) {
            $alreadySet = true;
            foreach ($currentSettings[$category->term_id] as $key => $value) {
                $extraLi = $extraLi . '<li data-topic-name="'.$value.'">'.$key.'</li>';
            }
        }

        $formatted = $formatted . '<ul class="research-briefings-sortable" data-category-id="'.$category->term_id.'"><h3>' . $category->name . '</h3>'.$extraLi.'</ul>';
    }
    return $formatted;
}

function research_briefings_format_rb_topics($currentSettings) {

    // Get all posts in correct category
    $posts = get_posts(array(
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'fields' => 'ids',
        'category' => research_briefings_wp_get_or_create_category()
    ));

    $topicArray = [];

    //loop over each post
    foreach($posts as $p) {
        //get the meta you need form each post
        $topic = get_post_meta($p, 'topics', true);
        $alreadyTagged = [];

        if($topic !== '' && is_array($topic)) {

            foreach ($topic as $single) {

                foreach ($currentSettings as $key => $value) {
                    foreach ($currentSettings[$key] as $value2) {
                        array_push($alreadyTagged, $value2);
                    }
                }

                if(!in_array($single['_about'], $alreadyTagged)) {
                    $topicReformatted = array('title' => $single['prefLabel']['_value'], 'id' => $single['_about']);
                    array_push($topicArray, $topicReformatted);
                }
            }

        }
    }

    // Remove duplicates
    $topicArray = array_map('unserialize', array_unique(array_map('serialize', $topicArray)));

    $formatted = '';

    foreach ($topicArray as $single) {
        $formatted = $formatted . '<li data-topic-name="'.$single['id'].'">'.$single['title'].'</li>';
    }

    return $formatted;
}

$crossTagOptions = get_option('research_briefings_crosstagged');
if($crossTagOptions == false) {
    update_option('research_briefings_crosstagged', array());
}
