<?php

// Function to register custom navigation menus
function register_custom_nav_menu() {
    register_nav_menus( array(
        'top_menu' => __( 'Top Menu', 'customTemplate' ),
        'primary_menu' => __( 'Primary Menu', 'customTemplate' ),
        'footer_menu'  => __( 'Footer Menu', 'customTemplate' ),
        'watch_now'  => __( 'Watch Now Menu', 'customTemplate' ),
    ) );
}
// Hook to run the function when the theme is being set up
add_action( 'after_setup_theme', 'register_custom_nav_menu', 0 );

// Check if ACF plugin is active, then add options pages
if( function_exists('acf_add_options_page') ) {

    // Add main options page
    acf_add_options_page(array(
        'page_title'    => 'Theme General Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
        'show_in_graphql' => true,
        'redirect'      => false
    ));

    // Add various subpages under the main options page
    acf_add_options_sub_page(array(
        'page_title'    => 'Theme Footer Settings',
        'menu_title'    => 'Footer',
        'parent_slug'   => 'theme-general-settings',
        'show_in_graphql' => true
    ));

    acf_add_options_sub_page(array(
        'page_title'    => 'Broadcast Expositors',
        'menu_title'    => 'Broadcast Expositors',
        'parent_slug'   => 'theme-general-settings',
        'show_in_graphql' => true
    ));

    acf_add_options_sub_page(array(
        'page_title'    => 'Chatroll Settings',
        'menu_title'    => 'Chatroll Settings',
        'parent_slug'   => 'theme-general-settings',
        'show_in_graphql' => true
    ));

    acf_add_options_sub_page(array(
        'page_title'    => 'Broadcast Schedule',
        'menu_title'    => 'Broadcast schedule',
        'parent_slug'   => 'theme-general-settings',
        'show_in_graphql' => true
    ));
}

// Enable support for post thumbnails (featured images)
add_theme_support('post-thumbnails');

// Allow SVG files to be uploaded
function add_svg_to_upload_mimes($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
// Hook to modify the allowed file types to include SVG
add_filter('upload_mimes', 'add_svg_to_upload_mimes');



// Post Types
function addArticlesandAllies() {
	register_post_type( 'allie', array(
		'labels' => array(
			'name' => 'Allies',
			'singular_name' => 'Allie',
			'menu_name' => 'Allies',
			'all_items' => 'All Allies',
			'edit_item' => 'Edit Allie',
			'view_item' => 'View Allie',
			'view_items' => 'View Allies',
			'add_new_item' => 'Add New Allie',
			'new_item' => 'New Allie',
			'parent_item_colon' => 'Parent Allie:',
			'search_items' => 'Search Allies',
			'not_found' => 'No allies found',
			'not_found_in_trash' => 'No allies found in Trash',
			'archives' => 'Allie Archives',
			'attributes' => 'Allie Attributes',
			'insert_into_item' => 'Insert into allie',
			'uploaded_to_this_item' => 'Uploaded to this allie',
			'filter_items_list' => 'Filter allies list',
			'filter_by_date' => 'Filter allies by date',
			'items_list_navigation' => 'Allies list navigation',
			'items_list' => 'Allies list',
			'item_published' => 'Allie published.',
			'item_published_privately' => 'Allie published privately.',
			'item_reverted_to_draft' => 'Allie reverted to draft.',
			'item_scheduled' => 'Allie scheduled.',
			'item_updated' => 'Allie updated.',
			'item_link' => 'Allie Link',
			'item_link_description' => 'A link to a allie.',
		),
		'public' => true,
		'show_in_rest' => true,
		'supports' => array(
			0 => 'title',
			1 => 'author',
			2 => 'editor',
			3 => 'thumbnail',
		),
		'delete_with_user' => false,
		'show_in_graphql' => true,
		'graphql_single_name' => 'allie',
		'graphql_plural_name' => 'allies',
		'taxonomies' => array( "category", "post_tag")
	));

	register_post_type( 'article', array(
		'labels' => array(
			'name' => 'Articles',
			'singular_name' => 'Article',
			'menu_name' => 'Articles',
			'all_items' => 'All Articles',
			'edit_item' => 'Edit Article',
			'view_item' => 'View Article',
			'view_items' => 'View Articles',
			'add_new_item' => 'Add New Article',
			'new_item' => 'New Article',
			'parent_item_colon' => 'Parent Article:',
			'search_items' => 'Search Articles',
			'not_found' => 'No articles found',
			'not_found_in_trash' => 'No articles found in Trash',
			'archives' => 'Article Archives',
			'attributes' => 'Article Attributes',
			'insert_into_item' => 'Insert into article',
			'uploaded_to_this_item' => 'Uploaded to this article',
			'filter_items_list' => 'Filter articles list',
			'filter_by_date' => 'Filter articles by date',
			'items_list_navigation' => 'Articles list navigation',
			'items_list' => 'Articles list',
			'item_published' => 'Article published.',
			'item_published_privately' => 'Article published privately.',
			'item_reverted_to_draft' => 'Article reverted to draft.',
			'item_scheduled' => 'Article scheduled.',
			'item_updated' => 'Article updated.',
			'item_link' => 'Article Link',
			'item_link_description' => 'A link to a article.',
		),
		'public' => true,
		'show_in_rest' => true,
		'supports' => array(
			0 => 'title',
			1 => 'author',
			2 => 'editor',
			3 => 'thumbnail',
		),
		'delete_with_user' => false,
		'show_in_graphql' => true,
		'graphql_single_name' => 'article',
		'graphql_plural_name' => 'articles',
		'taxonomies' => array( "category", "post_tag")
	));
}

function addBiosAndEvents() {
	register_post_type( 'bios', array(
		'labels' => array(
			'name' => 'Bios',
			'singular_name' => 'Bio',
			'menu_name' => 'Bios',
			'all_items' => 'All Bios',
			'edit_item' => 'Edit Bio',
			'view_item' => 'View Bio',
			'view_items' => 'View Bios',
			'add_new_item' => 'Add New Bio',
			'new_item' => 'New Bio',
			'parent_item_colon' => 'Parent Bio:',
			'search_items' => 'Search Bios',
			'not_found' => 'No bios found',
			'not_found_in_trash' => 'No bios found in Trash',
			'archives' => 'Bio Archives',
			'attributes' => 'Bio Attributes',
			'insert_into_item' => 'Insert into bio',
			'uploaded_to_this_item' => 'Uploaded to this bio',
			'filter_items_list' => 'Filter bios list',
			'filter_by_date' => 'Filter bios by date',
			'items_list_navigation' => 'Bios list navigation',
			'items_list' => 'Bios list',
			'item_published' => 'Bio published.',
			'item_published_privately' => 'Bio published privately.',
			'item_reverted_to_draft' => 'Bio reverted to draft.',
			'item_scheduled' => 'Bio scheduled.',
			'item_updated' => 'Bio updated.',
			'item_link' => 'Bio Link',
			'item_link_description' => 'A link to a bio.',
		),
		'public' => true,
		'show_in_rest' => true,
		'supports' => array(
			0 => 'title',
			1 => 'author',
			2 => 'editor',
			3 => 'thumbnail',
		),
		'delete_with_user' => false,
		'show_in_graphql' => true,
		'graphql_single_name' => 'bio',
		'graphql_plural_name' => 'bios',
		'taxonomies' => array( "category", "post_tag")
	) );

	register_post_type( 'event', array(
		'labels' => array(
			'name' => 'Events',
			'singular_name' => 'Event',
			'menu_name' => 'Events',
			'all_items' => 'All Events',
			'edit_item' => 'Edit Event',
			'view_item' => 'View Event',
			'view_items' => 'View Events',
			'add_new_item' => 'Add New Event',
			'new_item' => 'New Event',
			'parent_item_colon' => 'Parent Event:',
			'search_items' => 'Search Events',
			'not_found' => 'No events found',
			'not_found_in_trash' => 'No events found in Trash',
			'archives' => 'Event Archives',
			'attributes' => 'Event Attributes',
			'insert_into_item' => 'Insert into event',
			'uploaded_to_this_item' => 'Uploaded to this event',
			'filter_items_list' => 'Filter events list',
			'filter_by_date' => 'Filter events by date',
			'items_list_navigation' => 'Events list navigation',
			'items_list' => 'Events list',
			'item_published' => 'Event published.',
			'item_published_privately' => 'Event published privately.',
			'item_reverted_to_draft' => 'Event reverted to draft.',
			'item_scheduled' => 'Event scheduled.',
			'item_updated' => 'Event updated.',
			'item_link' => 'Event Link',
			'item_link_description' => 'A link to a event.',
		),
		'public' => true,
		'show_in_rest' => true,
		'supports' => array(
			0 => 'title',
			1 => 'author',
			2 => 'editor',
			3 => 'thumbnail',
		),
		'delete_with_user' => false,
		'show_in_graphql' => true,
		'graphql_single_name' => 'event',
		'graphql_plural_name' => 'events',
		'taxonomies' => array( "category", "post_tag")
	) );
}


function addToolkitsAndEpisode() {

	register_post_type( 'episode', array(
		'labels' => array(
			'name' => 'Episodes',
			'singular_name' => 'Episode',
			'menu_name' => 'Episode',
			'all_items' => 'All Episode',
			'edit_item' => 'Edit Episode',
			'view_item' => 'View Episode',
			'view_items' => 'View Episode',
			'add_new_item' => 'Add New Episode',
			'new_item' => 'New Episode',
			'parent_item_colon' => 'Parent Episode:',
			'search_items' => 'Search Episode',
			'not_found' => 'No episode found',
			'not_found_in_trash' => 'No episode found in Trash',
			'archives' => 'Episode Archives',
			'attributes' => 'Episode Attributes',
			'insert_into_item' => 'Insert into episode',
			'uploaded_to_this_item' => 'Uploaded to this episode',
			'filter_items_list' => 'Filter episode list',
			'filter_by_date' => 'Filter episode by date',
			'items_list_navigation' => 'Episode list navigation',
			'items_list' => 'Episode list',
			'item_published' => 'Episode published.',
			'item_published_privately' => 'Episode published privately.',
			'item_reverted_to_draft' => 'Episode reverted to draft.',
			'item_scheduled' => 'Episode scheduled.',
			'item_updated' => 'Episode updated.',
			'item_link' => 'Episode Link',
			'item_link_description' => 'A link to a episode.',
		),
		'public' => true,
		'hierarchical' => true,
		'supports' => array('title','editor','page-attributes','thumbnail','excerpt','custom-fields'),
		'delete_with_user' => false,
		'show_in_graphql' => true,
		'graphql_single_name' => 'episode',
		'graphql_plural_name' => 'episodes',
		'taxonomies' => array( "category", "post_tag"),
		'show_in_rest' => true
	));
}


function addResources() {
register_post_type( 'resource', array(
	'labels' => array(
		'name' => 'Resources',
		'singular_name' => 'Resource',
		'menu_name' => 'Resources',
		'all_items' => 'All Resources',
		'edit_item' => 'Edit Resource',
		'view_item' => 'View Resource',
		'view_items' => 'View Resources',
		'add_new_item' => 'Add New Resource',
		'new_item' => 'New Resource',
		'parent_item_colon' => 'Parent Resource:',
		'search_items' => 'Search Resources',
		'not_found' => 'No resources found',
		'not_found_in_trash' => 'No resources found in Trash',
		'archives' => 'Resource Archives',
		'attributes' => 'Resource Attributes',
		'insert_into_item' => 'Insert into resource',
		'uploaded_to_this_item' => 'Uploaded to this resource',
		'filter_items_list' => 'Filter resources list',
		'filter_by_date' => 'Filter resources by date',
		'items_list_navigation' => 'Resources list navigation',
		'items_list' => 'Resources list',
		'item_published' => 'Resource published.',
		'item_published_privately' => 'Resource published privately.',
		'item_reverted_to_draft' => 'Resource reverted to draft.',
		'item_scheduled' => 'Resource scheduled.',
		'item_updated' => 'Resource updated.',
		'item_link' => 'Resource Link',
		'item_link_description' => 'A link to a resource.',
	),
	'public' => true,
	'show_in_rest' => true,
	'supports' => array(
		0 => 'title',
		1 => 'author',
		2 => 'editor',
		3 => 'thumbnail',
	),
	'delete_with_user' => false,
    'show_in_graphql' => true,
    'graphql_single_name' => 'resource',
    'graphql_plural_name' => 'resources',
	'taxonomies' => array( "category", "post_tag")
));
};

function create_conference_post_type() {
    $labels = array(
        'name'                  => _x( 'Conferences', 'Post type general name', 'textdomain' ),
        'singular_name'         => _x( 'Conference', 'Post type singular name', 'textdomain' ),
        'menu_name'             => _x( 'Conferences', 'Admin Menu text', 'textdomain' ),
        'name_admin_bar'        => _x( 'Conference', 'Add New on Toolbar', 'textdomain' ),
        'add_new'               => __( 'Add New', 'textdomain' ),
        'add_new_item'          => __( 'Add New Conference', 'textdomain' ),
        'new_item'              => __( 'New Conference', 'textdomain' ),
        'edit_item'             => __( 'Edit Conference', 'textdomain' ),
        'view_item'             => __( 'View Conference', 'textdomain' ),
        'all_items'             => __( 'All Conferences', 'textdomain' ),
        'search_items'          => __( 'Search Conferences', 'textdomain' ),
        'parent_item_colon'     => __( 'Parent Conferences:', 'textdomain' ),
        'not_found'             => __( 'No conferences found.', 'textdomain' ),
        'not_found_in_trash'    => __( 'No conferences found in Trash.', 'textdomain' ),
        'featured_image'        => _x( 'Conference Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'archives'              => _x( 'Conference archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'textdomain' ),
        'insert_into_item'      => _x( 'Insert into conference', 'Overrides the “Insert into post”/“Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'textdomain' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this conference', 'Overrides the “Uploaded to this post”/“Uploaded to this page” phrase (used when viewing media attached to a post in the media library). Added in 4.4', 'textdomain' ),
        'filter_items_list'     => _x( 'Filter conferences list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/“Filter pages list”. Added in 4.4', 'textdomain' ),
        'items_list_navigation' => _x( 'Conferences list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/“Pages list navigation”. Added in 4.4', 'textdomain' ),
        'items_list'            => _x( 'Conferences list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/“Pages list”. Added in 4.4', 'textdomain' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'conference' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt'),
        'show_in_rest'       => true,
    );

    register_post_type( 'conference', $args );
}


function registerPostTypes() {
	addArticlesandAllies();
	addBiosAndEvents();
	addToolkitsAndEpisode();
	addResources();
    create_conference_post_type();
}

add_action( 'init', 'registerPostTypes');


add_action( 'init', function() {
	register_post_type( 'redirect', array(
	'labels' => array(
		'name' => 'Redirects',
		'singular_name' => 'Redirect',
		'menu_name' => 'Redirects',
		'all_items' => 'All Redirects',
		'edit_item' => 'Edit Redirect',
		'view_item' => 'View Redirect',
		'view_items' => 'View Redirects',
		'add_new_item' => 'Add New Redirect',
		'add_new' => 'Add New Redirect',
		'new_item' => 'New Redirect',
		'parent_item_colon' => 'Parent Redirect:',
		'search_items' => 'Search Redirects',
		'not_found' => 'No redirects found',
		'not_found_in_trash' => 'No redirects found in Trash',
		'archives' => 'Redirect Archives',
		'attributes' => 'Redirect Attributes',
		'insert_into_item' => 'Insert into redirect',
		'uploaded_to_this_item' => 'Uploaded to this redirect',
		'filter_items_list' => 'Filter redirects list',
		'filter_by_date' => 'Filter redirects by date',
		'items_list_navigation' => 'Redirects list navigation',
		'items_list' => 'Redirects list',
		'item_published' => 'Redirect published.',
		'item_published_privately' => 'Redirect published privately.',
		'item_reverted_to_draft' => 'Redirect reverted to draft.',
		'item_scheduled' => 'Redirect scheduled.',
		'item_updated' => 'Redirect updated.',
		'item_link' => 'Redirect Link',
		'item_link_description' => 'A link to a redirect.',
	),
	'public' => true,
	'exclude_from_search' => true,
	'show_in_rest' => true,
	'menu_icon' => 'dashicons-admin-links',
	'supports' => array(
		0 => 'title',
	),
	'delete_with_user' => false,
    'show_in_graphql' => true,
    'graphql_single_name' => 'redirect',
    'graphql_plural_name' => 'redirects',
) );
} );

// end Post Types


// Hook to register custom GraphQL fields for the 'Episode' post type
add_action('graphql_register_types', function() {
    register_graphql_fields('Episode', array(
        'zype_remote_id' => array(
            'type' => 'String',
            'description' => __('Remote ID from Zype', 'customTemplate'),
            'resolve' => function($post) {
                return get_post_meta($post->ID, 'zype_remote_id', true);
            }
        ),
        'thumbnails' => array(
            'type' => 'String',
            'description' => __('Thumbnails from Zype', 'customTemplate'),
            'resolve' => function($post) {
                return get_post_meta($post->ID, 'thumbnails', true);
            }
        ),
        'created_at' => array(
            'type' => 'String',
            'description' => __('Created At', 'customTemplate'),
            'resolve' => function($post) {
                return get_post_meta($post->ID, 'created_at', true);
            }
        ),
        'rating' => array(
            'type' => 'Float',
            'description' => __('Rating', 'customTemplate'),
            'resolve' => function($post) {
                return get_post_meta($post->ID, 'rating', true);
            }
        ),
        'episode_number' => array(
            'type' => 'Int',
            'description' => __('Episode number', 'customTemplate'),
            'resolve' => function($post) {
                return get_post_meta($post->ID, 'episode_number', true);
            }
        ),
        'duration' => array(
            'type' => 'Int',
            'description' => __('Episode duration', 'customTemplate'),
            'resolve' => function($post) {
                return get_post_meta($post->ID, 'duration', true);
            }
        )
    ));
});

// Function to render custom metadata fields in the post editor
function render_custom_metadata_box($post) {
    // Fetch metadata values
    $created_at = get_post_meta($post->ID, 'created_at', true);
    $zype_remote_id = get_post_meta($post->ID, 'zype_remote_id', true);
    $thumbnails = get_post_meta($post->ID, 'thumbnails', true);
    $rating = get_post_meta($post->ID, 'rating', true);
	$breaks = '<br><br>';

    // Output form fields to display metadata
    echo '<label for="created_at_field">Created_at:</label>';
    echo '<input type="text" id="created_atr_field" style="width: 100%; display: block;" name="created_at_field" value="' . esc_attr($created_at) . '"><br>';
    echo $breaks;
    echo '<label for="zype_remote_id_field">Zype Remote Id:</label>';
    echo '<input type="text" id="zype_remote_id_field" style="width: 100%; display: block;" name="zype_remote_id_field" value="' . esc_attr($zype_remote_id) . '"><br>';
    echo $breaks;
    echo '<label for="thumbnails_field">Thumbnails:</label>';
    echo '<input type="text" id="thumbnails_field" style="width: 100%; display: block;" name="thumbnails_field" value="' . esc_attr($thumbnails) . '">';
    echo $breaks;
    echo '<label for="rating_field">Rating:</label>';
    echo '<input type="text" id="rating_field" style="width: 100%; display: block;" name="rating_field" value="' . esc_attr($rating) . '">';
    echo $breaks;
    $theDate = date("2024-03-27T08:49:55.764-04:00");
}

// Function to add custom metaboxes for the 'episode' post type
function custom_post_type_meta_boxes() {
    add_meta_box(
        'custom-metadata',
        'Metadatos Personalizados',
        'render_custom_metadata_box',
        'episode',
        'normal',
        'high'
    );
}
// Hook to add metaboxes for custom metadata
add_action('add_meta_boxes', 'custom_post_type_meta_boxes');

// Function to register a P2P connection between 'episodes' and 'bios'
function register_p2p_connection() {
    p2p_register_connection_type( [
      'name' => 'episodes_to_bios',
      'from' => 'episode',
      'to'   => 'bios',
	  'can_create_post' => false
    ] );
}
// Hook to initialize the P2P connection
add_action( 'p2p_init', 'register_p2p_connection' );

// Function to register a P2P connection between 'resources' and 'bios'
function register_resources_to_bios_connection() {
    p2p_register_connection_type( [
      'name' => 'resources_to_bios',
      'from' => 'resource',
      'to'   => 'bios',
	  'title'=> "Resource Author",
	  'can_create_post' => false
    ] );
}
// Hook to initialize the P2P connection
add_action( 'p2p_init', 'register_resources_to_bios_connection' );

// Function to register a P2P connection between 'articles' and 'bios'
function register_article_to_bios_connection() {
    p2p_register_connection_type( [
      'name' => 'article_to_bios',
      'from' => 'article',
      'to'   => 'bios',
	  'title'=> "Article Author",
	  'can_create_post' => false
    ] );
}
// Hook to initialize the P2P connection
add_action( 'p2p_init', 'register_article_to_bios_connection' );

// Function to register a P2P connection between 'events' and 'bios'
function register_event_to_bios_connection() {
    p2p_register_connection_type( [
      'name' => 'event_to_bios',
      'from' => 'event',
      'to'   => 'bios',
	  'title'=> "Speakers",
	  'can_create_post' => false
    ] );
}
// Hook to initialize the P2P connection
add_action( 'p2p_init', 'register_event_to_bios_connection' );


//Taxonomies

add_action( 'init', function() {

	register_taxonomy( 'source', array(
		0 => 'toolkit',
	), array(
		'labels' => array(
			'name' => 'Source',
			'singular_name' => 'source',
			'menu_name' => 'Source',
			'all_items' => 'All Source',
			'edit_item' => 'Edit source',
			'view_item' => 'View source',
			'update_item' => 'Update source',
			'add_new_item' => 'Add New source',
			'new_item_name' => 'New source Name',
			'search_items' => 'Search Source',
			'not_found' => 'No source found',
			'no_terms' => 'No source',
			'items_list_navigation' => 'Source list navigation',
			'items_list' => 'Source list',
			'back_to_items' => '← Go to source',
			'item_link' => 'source Link',
			'item_link_description' => 'A link to a source',
		),
		'public' => true,
		'hierarchical' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
	) );

	register_taxonomy( 'topic', array(
		0 => 'article',
		1 => 'resource',
		2 => 'allie',
		3 => 'bios',
		4 => 'episode',
		5 => 'toolkit',
		6 => 'event',
	), array(
		'labels' => array(
			'name' => 'Topic',
			'singular_name' => 'Topic',
			'menu_name' => 'Topic',
			'all_items' => 'All Topic',
			'edit_item' => 'Edit Topic',
			'view_item' => 'View Topic',
			'update_item' => 'Update Topic',
			'add_new_item' => 'Add New Topic',
			'new_item_name' => 'New Topic Name',
			'search_items' => 'Search Topic',
			'not_found' => 'No topic found',
			'no_terms' => 'No topic',
			'items_list_navigation' => 'Topic list navigation',
			'items_list' => 'Topic list',
			'back_to_items' => '← Go to topic',
			'item_link' => 'Topic Link',
			'item_link_description' => 'A link to a topic',
		),
		'public' => true,
		'hierarchical' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
        'show_in_graphql' => true,
        'graphql_single_name' => 'Topic',
        'graphql_plural_name' => 'Topics'
	) );

	register_taxonomy( 'bio-category', array(
		0 => 'bios',
	), array(
		'labels' => array(
			'name' => 'Bio Categories',
			'singular_name' => 'Bio Category',
			'menu_name' => 'Bio Categories',
			'all_items' => 'All Bio Categories',
			'edit_item' => 'Edit Bio Category',
			'view_item' => 'View Bio Category',
			'update_item' => 'Update Bio Category',
			'add_new_item' => 'Add New Bio Category',
			'new_item_name' => 'New Bio Category Name',
			'parent_item' => 'Parent Bio Category',
			'parent_item_colon' => 'Parent Bio Category:',
			'search_items' => 'Search Bio Categories',
			'not_found' => 'No bio categories found',
			'no_terms' => 'No bio categories',
			'filter_by_item' => 'Filter by bio category',
			'items_list_navigation' => 'Bio Categories list navigation',
			'items_list' => 'Bio Categories list',
			'back_to_items' => '← Go to bio categories',
			'item_link' => 'Bio Category Link',
			'item_link_description' => 'A link to a bio category',
		),
		'public' => true,
		'hierarchical' => true,
		'show_in_menu' => true,
		'show_in_rest' => true,
		'show_in_graphql' => true,
        'graphql_single_name' => 'BioCategory',
        'graphql_plural_name' => 'BioCategories'
	) );

} );


function register_event_day_taxonomy() {
    register_taxonomy( 'day', array( 'event' ), array(
        'labels' => array(
            'name' => 'Event Days',
            'singular_name' => 'Event Day',
            'menu_name' => 'Event Day',
            'all_items' => 'All Event Days',
            'edit_item' => 'Edit Event Day',
            'view_item' => 'View Event Day',
            'update_item' => 'Update Event Day',
            'add_new_item' => 'Add New Event Day',
            'new_item_name' => 'New Event Day Name',
            'parent_item' => 'Parent Event Day',
            'parent_item_colon' => 'Parent Event Day:',
            'search_items' => 'Search Event Days',
            'not_found' => 'No event days found',
            'no_terms' => 'No event days',
            'filter_by_item' => 'Filter by event day',
            'items_list_navigation' => 'Event Day list navigation',
            'items_list' => 'Event Day list',
            'back_to_items' => '← Go to event days',
            'item_link' => 'Event Day Link',
            'item_link_description' => 'A link to an event day',
        ),
        'public' => true,
        'hierarchical' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'show_in_graphql' => true,
        'graphql_single_name' => 'EventDay',
        'graphql_plural_name' => 'EventDays',
        'show_admin_column' => true,
        'rewrite' => array(
            'hierarchical' => true,
        ),
    ) );
}
add_action( 'init', 'register_event_day_taxonomy' );

// End Custom Taxonomies


$customPostTypes = ["bios", "article", "event", "episode", "allie", "resource", "post"];

foreach( $customPostTypes as $customPostType ) {

    $hookName = "rest_prepare_{$customPostType}";

    // Adds a filter to modify the REST API response for each custom post type
    add_filter($hookName, function($data, $post, $request) {
        // Only modify the response if the context is 'view'
        if (isset($request['context']) && $request['context'] === 'view') {
            // Get all taxonomies associated with the post type and include their slugs in the response
            $taxonomy_names = get_object_taxonomies($post->post_type);
            foreach ($taxonomy_names as $taxonomy_name) {
                $terms = get_the_terms($post->ID, $taxonomy_name);
                if (!empty($terms) && !is_wp_error($terms)) {
                    $slugs = array_map(function($term) {
                        return $term->slug;
                    }, $terms);
                    $data->data[$taxonomy_name] = $slugs;
                }
            }

            // Add featured image URL to the response if the post has a featured image
            if (has_post_thumbnail($post->ID)) {
                $data->data['featured_media'] = generate_image_srcset($post->ID);
            } else {
                $data->data['featured_media'] = null;
            }
        }
        return $data;
    }, 10, 3);
}

// Add a custom field to the 'bios' post type to include connected episodes from the Posts 2 Posts plugin
add_action('rest_api_init', function() {
    register_rest_field('bios', 'connected_episodes', array(
        'get_callback'    => 'get_connected_posts',
        'update_callback' => null,
        'schema'          => array(
            'description' => __('Connected posts from Posts 2 Posts plugin.'),
            'type'        => 'array',
            'context'     => array('view', 'edit'),
        ),
    ));
});

// Add a custom field to the 'episode' post type to include connected bios from the Posts 2 Posts plugin
add_action('rest_api_init', function() {
    register_rest_field('episode', 'connected_bios', array(
        'get_callback'    => 'get_connected_posts',
        'update_callback' => null,
        'schema'          => array(
            'description' => __('Connected posts from Posts 2 Posts plugin.'),
            'type'        => 'array',
            'context'     => array('view', 'edit'),
        ),
    ));
});

// Function to retrieve connected posts (bios <-> episodes)
function get_connected_posts($post, $field_name, $request) {
    if (!function_exists('p2p_type')) {
        return [];
    }

    // Get connected posts using the Posts 2 Posts connection type
    $connected = p2p_type('episodes_to_bios')->get_connected($post['id']);

    $connected_posts = [];

    // Loop through connected posts and add necessary data (ID, title, slug, featured image)
    foreach ($connected->posts as $connected_post) {
        $connected_posts[] = [
            'ID' => $connected_post->ID,
            'name' => $connected_post->post_title,
            'slug' => $connected_post->post_name,
            'featured-image' => (generate_image_srcset($connected_post->ID)) ? generate_image_srcset($connected_post->ID) : null, //(get_the_post_thumbnail_url($connected_post->ID))? get_the_post_thumbnail_url($connected_post->ID) : null,
        ];
    }

    return $connected_posts;
}

// Add a custom field to the 'resource' post type to include connected bios from the Posts 2 Posts plugin
add_action('rest_api_init', function() {
    register_rest_field('resource', 'resource-author', array(
        'get_callback'    => 'get_connected_bios_to_resource',
        'update_callback' => null,
        'schema'          => array(
            'description' => __('Connected posts from Posts 2 Posts plugin.'),
            'type'        => 'array',
            'context'     => array('view', 'edit'),
        ),
    ));
});

// Function to retrieve connected bios for 'resource' post type
function get_connected_bios_to_resource($post, $field_name, $request) {
    if (!function_exists('p2p_type')) {
        return [];
    }

    // Get connected bios using the Posts 2 Posts connection type
    $connected = p2p_type('resources_to_bios')->get_connected($post['id']);

    $connected_posts = [];

    // Loop through connected bios and add necessary data (ID, title, slug, featured image)
    foreach ($connected->posts as $connected_post) {
        $connected_posts[] = [
            'ID' => $connected_post->ID,
            'name' => $connected_post->post_title,
            'slug' => $connected_post->post_name,
            'featured-image' => (generate_image_srcset($connected_post->ID)) ? generate_image_srcset($connected_post->ID) : null, //(get_the_post_thumbnail_url($connected_post->ID))? get_the_post_thumbnail_url($connected_post->ID) : null,
        ];
    }

    return $connected_posts;
}

// Add a custom field to the 'article' post type to include connected bios from the Posts 2 Posts plugin
add_action('rest_api_init', function() {
    register_rest_field('article', 'article-author', array(
        'get_callback'    => 'get_connected_bios_to_articles',
        'update_callback' => null,
        'schema'          => array(
            'description' => __('Connected posts from Posts 2 Posts plugin.'),
            'type'        => 'array',
            'context'     => array('view', 'edit'),
        ),
    ));
});

// Function to retrieve connected bios for 'article' post type
function get_connected_bios_to_articles($post, $field_name, $request) {
    if (!function_exists('p2p_type')) {
        return [];
    }

    // Get connected bios using the Posts 2 Posts connection type
    $connected = p2p_type('article_to_bios')->get_connected($post['id']);

    $connected_posts = [];

    // Loop through connected bios and add necessary data (ID, title, slug, featured image)
    foreach ($connected->posts as $connected_post) {
        $connected_posts[] = [
            'ID' => $connected_post->ID,
            'name' => $connected_post->post_title,
            'slug' => $connected_post->post_name,
            'featured-image' => (generate_image_srcset($connected_post->ID)) ? generate_image_srcset($connected_post->ID) : null, //(get_the_post_thumbnail_url($connected_post->ID)) ? get_the_post_thumbnail_url($connected_post->ID) : null,
        ];
    }

    return $connected_posts;
}


// Array of custom post types that will have search filters applied.
$searchPostTypes = ["bios", "article", "event", "episode", "allie", "toolkit", "resource", "post"];



// Add filter to allow searching by 'topic', by 'bios-category' and by "toolkit" for each post type.
foreach( $searchPostTypes as $searchPostType ):
	$filterName = "rest_{$searchPostType}_query";
	add_filter($filterName, function($args, $request) {
        $tax_query = array(); // Inicializa el array para tax_query
    
        // Check if 'by-topic' parameter is provided and apply the filter with multiple terms.
        if (isset($request['by-topic']) && !empty($request['by-topic'])) {
            $topics = array_map('sanitize_text_field', explode(',', $request['by-topic']));
            
            // Añade el filtro de 'topic' a tax_query
            $tax_query[] = array(
                'taxonomy' => 'topic',
                'field'    => 'slug',
                'terms'    => $topics,
                'operator' => 'IN',
            );
        }
    
        // Filter posts by 'bio-category' if provided in the request.
        if (isset($request['by-bios-category']) && !empty($request['by-bios-category'])) {
            $bio_categories = array_map('sanitize_text_field', explode(',', $request['by-bios-category']));
            
            // Añade el filtro de 'bio-category' a tax_query
            $tax_query[] = array(
                'taxonomy' => 'bio-category',
                'field'    => 'slug',
                'terms'    => $bio_categories,
                'operator' => 'IN',
            );
        }
    
        // Filter posts by 'toolkit' category if provided in the request.
        if (isset($request['by-toolkit']) && !empty($request['by-toolkit'])) {
            $toolkit_categories = array_map('sanitize_text_field', explode(',', $request['by-toolkit']));
            
            // Añade el filtro de 'category' (toolkit) a tax_query
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => $toolkit_categories,
                'operator' => 'IN',
            );
        }
    
        // Si tax_query tiene algún valor, lo añadimos a los args
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
    
        return $args;
    }, 20, 2);
endforeach;



// Function to add custom fields to the 'episode' post type in the REST API.
function add_custom_fields_to_episode_rest_api() {
    $custom_fields = ['created_at', 'zype_remote_id', 'episode_number', 'thumbnails', 'rating', 'duration', 'year'];

    // Loop through each custom field and register it in the REST API for 'episode'.
    foreach ($custom_fields as $field) {
        register_rest_field('episode', $field, array(
            'get_callback' => function($post_arr) use ($field) {
                return get_post_meta($post_arr['id'], $field, true);
            },
            'update_callback' => function($value, $post_arr, $field_name) {
                // Update or delete the meta field based on the value.
                if (!empty($value)) {
                    update_post_meta($post_arr->ID, $field_name, sanitize_text_field($value));
                } else {
                    delete_post_meta($post_arr->ID, $field_name);
                }
            },
            'schema' => array(
                'description' => ucfirst(str_replace('_', ' ', $field)),
                'type' => 'string',
                'context' => array('view', 'edit'),
            ),
        ));
    }
}
add_action('rest_api_init', 'add_custom_fields_to_episode_rest_api');

// Function to add custom fields to the 'event' post type in the REST API.
function add_custom_fields_to_event_rest_api() {
    $custom_fields = ['created_at', 'zype_remote_id', 'episode_number', 'thumbnails', 'rating', 'duration', 'year'];

    // Loop through each custom field and register it in the REST API for 'event'.
    foreach ($custom_fields as $field) {
        register_rest_field('event', $field, array(
            'get_callback' => function($post_arr) use ($field) {
                return get_post_meta($post_arr['id'], $field, true);
            },
            'update_callback' => function($value, $post_arr, $field_name) {
                // Update or delete the meta field based on the value.
                if (!empty($value)) {
                    update_post_meta($post_arr->ID, $field_name, sanitize_text_field($value));
                } else {
                    delete_post_meta($post_arr->ID, $field_name);
                }
            },
            'schema' => array(
                'description' => ucfirst(str_replace('_', ' ', $field)),
                'type' => 'string',
                'context' => array('view', 'edit'),
            ),
        ));
    }
}
add_action('rest_api_init', 'add_custom_fields_to_event_rest_api');



// Adds pagination details to the REST API response for specific endpoints.
function custom_add_pagination_headers($response, $server, $request) {

    // Checks if the response is a valid WP REST Response.
    if (!($response instanceof WP_REST_Response)) {
        return $response;
    }

    // Only processes GET requests.
    if ($request->get_method() !== 'GET') {
        return $response;
    }

    // Specifies which endpoints should include pagination data.
    $endPoints = ["bios", "article", "event", "episode", "allie", "toolkit", "resource", "posts", "conference", "redirect"];
    $valid_request = false;

    // Checks if the request matches one of the allowed endpoints.
    foreach ($endPoints as $endpoint) {
        if (strpos($request->get_route(), "/wp/v2/{$endpoint}") !== false) {
            $valid_request = true;
            break;
        }
    }

    // If the request is invalid, it returns the original response.
    if (!$valid_request) {
        return $response;
    }

    // Retrieves pagination headers.
    $headers = $response->get_headers();
    if (!isset($headers['X-WP-Total']) || !isset($headers['X-WP-TotalPages'])) {
        return $response;
    }

    // Gets the total number of posts and total pages.
    $total_posts = $response->get_headers()['X-WP-Total'];
    $total_pages = $response->get_headers()['X-WP-TotalPages'];

    // Adds pagination information to the response data.
    $data = $response->get_data();
    if (is_array($data)) {
        $page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
        $per_page = !empty($_GET['per_page']) ? (int) $_GET['per_page'] : 10;

        $pagination = array(
            'total_pages'    => (int) $total_pages,
            'total_items'    => (int) $total_posts,
            'current_page'   => (int) $page,
            'items_per_page' => (int) $per_page,
        );

        // Modifies the response to include pagination.
        $new_response = array(
            'items' => $data,
            'pagination' => $pagination,
        );
        $response->set_data($new_response);
    }

    return $response;
}
add_filter('rest_post_dispatch', 'custom_add_pagination_headers', 10, 3);

// Registers custom image sizes.
function custom_image_sizes() {
    add_image_size('xs', 480, 0, false);
    add_image_size('sm', 640, 0, false);
    add_image_size('md', 768, 0, false);
    add_image_size('lg', 1024, 0, false);
    add_image_size('xl', 1280, 0, false);
}
add_action('after_setup_theme', 'custom_image_sizes');

// Adds custom image sizes to media library options.
function custom_sizes($sizes) {
    return array_merge($sizes, [
        'xs' => __('xs'),
        'sm' => __('sm'),
        'md' => __('md'),
        'lg' => __('lg'),
        'xl' => __('xl'),
    ]);
}
add_filter('image_size_names_choose', 'custom_sizes');

// Removes default image sizes from the media library.
function remove_default_images($sizes) {
    unset($sizes['medium']);
    unset($sizes['medium_large']);
    unset($sizes['large']);
    unset($sizes['1536x1536']);
    unset($sizes['2048x2048']);

    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'remove_default_images');

// Registers a custom REST API endpoint to get all related bios.
add_action('rest_api_init', function() {
    register_rest_route('wp/v2/custom', '/related-bios', array(
        'methods'  => 'GET',
        'callback' => 'get_all_related_bios',
        'permission_callback' => '__return_true',
    ));
});


// Get array de featured images 
function generate_image_srcset($post_id) {
    // Get the ID of the thumbnail
    $thumbnail_id = get_post_thumbnail_id($post_id);
    
    // Get the metadata of the attachment
    $thumbnail_metadata = wp_get_attachment_metadata($thumbnail_id);
    
    $sizes_to_include = ['thumbnail', 'xs', 'sm', 'md', 'lg', 'xl'];

    $srcSet = [];
    
    if (isset($thumbnail_metadata['sizes'])) {
        foreach ($sizes_to_include as $size) {
            if (isset($thumbnail_metadata['sizes'][$size])) {
                // Get the full URL using WordPress settings (includes S3 if configured)
                $image_url = wp_get_attachment_image_src($thumbnail_id, $size)[0];
                $image_width = $thumbnail_metadata['sizes'][$size]['width'];
                $srcSet[] = $image_url . ' ' . $image_width . 'w';
            }
        }
    }

    // Return the srcSet format
    return ['srcSet' => implode(', ', $srcSet)];
}


// Fetches all related bios connected to resources or articles using the Posts 2 Posts plugin.
function get_all_related_bios($request) {
    if (!function_exists('p2p_type')) {
        return new WP_Error('plugin_not_active', 'Posts 2 Posts plugin is not active', array('status' => 500));
    }

    $posts = get_posts(array(
        'post_type' => array('resource', 'article'),
        'posts_per_page' => -1,
    ));

    $all_bios = [];

    // Loops through each post and retrieves connected bios.
    foreach ($posts as $post) {
        $connected_type = ($post->post_type === 'resource') ? 'resources_to_bios' : 'article_to_bios';
        $connected = p2p_type($connected_type)->get_connected($post->ID);

        // Adds unique bios to the array.
        foreach ($connected->posts as $bio) {
            if (!isset($all_bios[$bio->ID])) {
                $all_bios[$bio->ID] = [
                    'ID' => $bio->ID,
                    'name' => $bio->post_title,
                    'slug' => $bio->post_name,
                    'featured-image' => (generate_image_srcset($bio->ID)) ? generate_image_srcset($bio->ID) : null,//($image_urls) ? $image_urls : null, //(get_the_post_thumbnail_url($bio->ID)) ? get_the_post_thumbnail_url($bio->ID) : null,
                ];
            }
        }
    }

    return rest_ensure_response(array_values($all_bios));
}

// Adds support for SVG uploads.
function custom_mime_types($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'custom_mime_types');

// Filters articles by bios related through a custom REST API query parameter.
add_filter('rest_article_query', 'filter_articles_by_bio_slug', 10, 2);

function filter_articles_by_bio_slug($args, $request) {
    if (isset($request['article-author']) && !empty($request['article-author'])) {
        $bio_slug = sanitize_text_field($request['article-author']);

        $bio = get_posts(array(
            'name' => $bio_slug,
            'post_type' => 'bios',
            'post_status' => 'publish',
            'fields' => 'ids',
            'numberposts' => 1
        ));

        if (!empty($bio)) {
            $bio_id = $bio[0];

            if (function_exists('p2p_type')) {
                $connected_articles = get_posts(array(
                    'connected_type' => 'article_to_bios',
                    'connected_items' => $bio_id,
                    'fields' => 'ids',
                    'nopaging' => true
                ));

                if (!empty($connected_articles)) {
                    $args['post__in'] = $connected_articles;
                } else {
                    $args['post__in'] = array(0);
                }
            }
        } else {
            $args['post__in'] = array(0);
        }
    }

    return $args;
}

// Filters resources by bios related through a custom REST API query parameter.
add_filter('rest_resource_query', 'filter_resources_by_bio_slug', 10, 2);

function filter_resources_by_bio_slug($args, $request) {
    if (isset($request['resource-author']) && !empty($request['resource-author'])) {
        $bio_slug = sanitize_text_field($request['resource-author']);

        $bio = get_posts(array(
            'name' => $bio_slug,
            'post_type' => 'bios',
            'post_status' => 'publish',
            'fields' => 'ids',
            'numberposts' => 1
        ));

        if (!empty($bio)) {
            $bio_id = $bio[0];

            if (function_exists('p2p_type')) {
                $connected_articles = get_posts(array(
                    'connected_type' => 'resources_to_bios',
                    'connected_items' => $bio_id,
                    'fields' => 'ids',
                    'nopaging' => true
                ));

                if (!empty($connected_articles)) {
                    $args['post__in'] = $connected_articles;
                } else {
                    $args['post__in'] = array(0);
                }
            }
        } else {
            $args['post__in'] = array(0);
        }
    }

    return $args;
}

// Add a filter to modify the REST API query for the custom post type 'episode'
// This filter allows filtering episodes based on a related 'bio' slug.
add_filter('rest_episode_query', 'filter_episode_by_bio_slug', 10, 2);

function filter_episode_by_bio_slug($args, $request) {
    // Check if the 'by-bio' parameter is present in the request
    if (isset($request['by-bio']) && !empty($request['by-bio'])) {
        $bio_slug = sanitize_text_field($request['by-bio']);

        // Fetch the 'bio' post using the slug provided in the request
        $bio = get_posts(array(
            'name' => $bio_slug,
            'post_type' => 'bios',
            'post_status' => 'publish',
            'fields' => 'ids',
            'numberposts' => 1
        ));

        // If a matching 'bio' post is found, proceed to find related episodes
        if (!empty($bio)) {
            $bio_id = $bio[0];

            // Check if the Posts 2 Posts plugin is available to find connected episodes
            if (function_exists('p2p_type')) {
                $connected_episodes = get_posts(array(
                    'connected_type' => 'episodes_to_bios',
                    'connected_items' => $bio_id,
                    'fields' => 'ids',
                    'nopaging' => true
                ));

                // If connected episodes are found, modify the query to return those
                if (!empty($connected_episodes)) {
                    $args['post__in'] = $connected_episodes;
                } else {
                    $args['post__in'] = array(0); // No episodes found
                }
            }
        } else {
            $args['post__in'] = array(0); // No 'bio' found
        }
    }

    return $args; // Return the modified query arguments
}

// Add a filter to modify the REST API query for the custom post type 'bios'
// This filter allows filtering 'bios' based on a range of starting letters.
add_filter('rest_bios_query', 'filter_bios_by_letter_range', 10, 2);

function filter_bios_by_letter_range($args, $request) {
    global $wpdb;

    // Check if the 'letter_range' parameter is present in the request
    if (isset($request['letter_range'])) {
        $letter_range = sanitize_text_field($request['letter_range']);
        list($start_letter, $end_letter) = explode('-', $letter_range);

        // Convert letters to uppercase and validate that both are alphabetic and ordered correctly
        $start_letter = strtoupper($start_letter);
        $end_letter = strtoupper($end_letter);

        if (ctype_alpha($start_letter) && ctype_alpha($end_letter) && $start_letter <= $end_letter) {
            // Create an array of letters within the specified range
            $letters = range($start_letter, $end_letter);
            $like_clauses = array_map(function($letter) {
                return "post_title LIKE '{$letter}%'";
            }, $letters);
            $like_query = implode(' OR ', $like_clauses);

            // Execute a raw SQL query to find matching 'bios' posts by title
            $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'bios' AND ({$like_query}) AND post_status = 'publish'";
            $posts = $wpdb->get_col($query);

            // If matching posts are found, modify the query to return those
            if (!empty($posts)) {
                $args['post__in'] = $posts;
            } else {
                $args['post__in'] = array(0); // No posts found
            }
        } else {
            $args['post__in'] = array(0); // Invalid letter range
        }
    }

    // Set default sorting by title in ascending order
    $args['orderby'] = 'title';
    $args['order'] = 'ASC';

    return $args; // Return the modified query arguments
}


// Add filters to modify REST API queries for multiple custom post types
// This filter allows searching posts by title and tags for various custom post types.
add_filter('rest_episode_query', 'filter_posts_by_title_and_tags', 10, 2);
add_filter('rest_article_query', 'filter_posts_by_title_and_tags', 10, 2);
add_filter('rest_bios_query', 'filter_posts_by_title_and_tags', 10, 2);
add_filter('rest_allie_query', 'filter_posts_by_title_and_tags', 10, 2);
add_filter('rest_resource_query', 'filter_posts_by_title_and_tags', 10, 2);

function filter_posts_by_title_and_tags($args, $request) {
    // Check if the 'search' parameter is present in the request
    if (isset($request['search'])) {
        global $wpdb;

        $search = sanitize_text_field($request['search']);

        // Prepare a query to search post titles matching the search term
        $title_like = $wpdb->esc_like($search);
        $title_query = $wpdb->prepare("post_title LIKE %s", '%' . $title_like . '%');

        // Fetch tag IDs that match the search term
        $tags = get_terms(array(
            'taxonomy' => 'post_tag',
            'name__like' => $search,
            'fields' => 'ids',
        ));

        // Prepare a query to search posts associated with the found tag IDs
        if (!is_wp_error($tags) && !empty($tags)) {
            $tag_ids = implode(',', array_map('intval', $tags));
            $tag_query = "ID IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($tag_ids))";
        } else {
            $tag_query = "1=0"; // No matching tags
        }

        // Combine title and tag queries
        $search_query = "($title_query) OR ($tag_query)";

        // Disable the default search and add custom search logic to the query
        $args['s'] = false; 
        add_filter('posts_where', function($where) use ($search_query) {
            return $where . " AND ($search_query)";
        });
    }

    return $args; // Return the modified query arguments
}


//Transform ACF images to srcSet

function generate_srcset($image_data) {
    if (!isset($image_data['sizes'])) {
        return null;
    }

    // Get the different image sizes from the sizes array
    $sizes = $image_data['sizes'];

    // Create the srcSet by concatenating the URLs and corresponding widths
    $srcSet = sprintf(
        '%s %sw, %s %sw, %s %sw, %s %sw, %s %sw, %s %sw',
        $sizes['thumbnail'], $sizes['thumbnail-width'],
        $sizes['xs'], $sizes['xs-width'],
        $sizes['sm'], $sizes['sm-width'],
        $sizes['md'], $sizes['md-width'],
        $sizes['lg'], $sizes['lg-width'],
        $sizes['xl'], $sizes['xl-width']
    );

    return ['srcSet' => $srcSet];
}

// Register a custom REST API endpoint to retrieve categories
// This endpoint allows fetching all categories or a specific category by slug.
add_action('rest_api_init', function () {
    register_rest_route('wp/v2/custom', '/toolkit-categories', array(
        'methods' => 'GET',
        'callback' => 'get_all_categories',
        'args' => array(
            'slug' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param);
                },
            ),
            'search' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param);
                },
            ),
        ),
    ));
});

function get_all_categories($request) {
    $slug = $request->get_param('slug');
    $search = $request->get_param('search');

    if ($slug) {
        // Fetch a single category by slug
        $category = get_term_by('slug', $slug, 'category');
        if ($category && !is_wp_error($category)) {
            return rest_ensure_response(build_single_category($category));
        } else {
            return new WP_Error('no_category', 'Category not found', array('status' => 404));
        }
    } else {
        // Fetch all categories, optionally filtered by search term
        $args = array(
            'hide_empty' => false,
        );

        if ($search) {
            $args['search'] = $search;
        }

        $categories = get_categories($args);

        $category_tree = build_category_tree($categories);

        return rest_ensure_response($category_tree);
    }
}


// Function to build detailed information for a single category
function build_single_category($category) {
    // Retrieve the term object for the category
    $term = get_term($category->term_id, "category");

    // Build an array with category details
    $category_data = array(
        'id' => $category->term_id,
        'slug' => $category->slug,
        'title' => $category->name,
        'subtitle' => get_field("subtitle", $term), // Custom field 'subtitle'
        'description' => $category->description,
        'cardImage' => generate_srcset(get_field("card_image", $term)), // Custom field 'card_image'
        'desktopBanner' => $category->parent == 0 ? generate_srcset(get_field("desktop_banner", $term)) : null, // Custom field 'desktop_banner' for top-level categories
        'mobileBanner' => $category->parent == 0 ? generate_srcset(get_field("mobile_banner", $term)) : null, // Custom field 'mobile_banner' for top-level categories
        'subcategories' => array(), // Initialize an empty array for subcategories
    );

    // If the category is a top-level category, retrieve its subcategories
    if ($category->parent == 0) {
        $subcategories = get_categories(array(
            'hide_empty' => false,
            'parent' => $category->term_id,
        ));

        // Iterate through each subcategory and build its data
        foreach ($subcategories as $subcategory) {
            $subcategory_term = get_term($subcategory->term_id, "category");
            $category_data['subcategories'][] = array(
                'id' => $subcategory->term_id,
                'slug' => $subcategory->slug,
                'title' => $subcategory->name,
                'subtitle' => get_field("subtitle", $subcategory_term), // Custom field 'subtitle'
                'description' => $subcategory->description,
                'cardImage' => generate_srcset(get_field("card_image", $subcategory_term)), // Custom field 'card_image'
            );
        }
    }

    return $category_data; // Return the built category data
}

// Function to build a hierarchical tree of categories
function build_category_tree($categories) {
    $category_map = array();

    // Build a map of categories with their details
    foreach ($categories as $category) {
        $term = get_term($category->term_id, "category");
        $category_map[$category->term_id] = array(
            'id' => $category->term_id,
            'slug' => $category->slug,
            'title' => $category->name,
            'subtitle' => get_field("subtitle", $term), // Custom field 'subtitle'
            'description' => $category->description,
            'cardImage' => generate_srcset(get_field("card_image", $term)), // Custom field 'card_image'
        );

        // Add banners for top-level categories
        if ($category->parent == 0) {
            $category_map[$category->term_id]['desktopBanner'] = generate_srcset(get_field("desktop_banner", $term));
            $category_map[$category->term_id]['mobileBanner'] = generate_srcset(get_field("mobile_banner", $term));
            $category_map[$category->term_id]['subcategories'] = array(); // Initialize empty subcategories
        }
    }

    $category_tree = array();
    // Build the hierarchical tree structure
    foreach ($categories as $category) {
        if ($category->parent) {
            $category_map[$category->parent]['subcategories'][] = &$category_map[$category->term_id];
        } else {
            $category_tree[] = &$category_map[$category->term_id];
        }
    }

    return $category_tree; // Return the hierarchical category tree
}

// Filter bios based on episode slug
add_filter('rest_bios_query', 'filter_bios_by_episode_slug', 10, 2);

function filter_bios_by_episode_slug($args, $request) {
    // Check if the 'episode_slug' parameter is present in the request
    if (isset($request['episode_slug'])) {
        global $wpdb;

        $episode_slug = sanitize_text_field($request['episode_slug']);

        // Retrieve the episode post by slug
        $episode = get_page_by_path($episode_slug, OBJECT, 'episode');
        if ($episode) {
            // Get connected bios for the episode
            $connected_bios = p2p_type('episodes_to_bios')->get_connected($episode->ID)->posts;

            // If connected bios are found, filter the query by these bios
            if (!empty($connected_bios)) {
                $bio_ids = wp_list_pluck($connected_bios, 'ID');
                $args['post__in'] = $bio_ids;
            } else {
                $args['post__in'] = array(0); // No connected bios
            }
        } else {
            $args['post__in'] = array(0); // Episode not found
        }
    }

    return $args; // Return the modified query arguments
}



// Add a custom field 'year' to the REST API response for the 'event' post type
add_action('rest_api_init', function () {
    register_rest_field('event', 'year', array(
        'get_callback'    => function ($post) {
            return get_post_meta($post['id'], 'year', true); // Retrieve the 'year' meta value for the event
        },
        'update_callback' => null, // No update functionality for this field
        'schema'          => array(
            'description' => 'Year of the event', // Description of the field
            'type'        => 'integer', // Data type of the field
            'context'     => array('view', 'edit'), // Contexts where this field is available
        ),
    ));
});

// Filter 'event' posts by 'year' in the REST API query
add_filter('rest_event_query', function ($args, $request) {
    $year = $request->get_param('year'); // Retrieve 'year' parameter from the request

    if ($year) {
        $args['meta_query'] = array(
            array(
                'key'     => 'year', // Meta key to filter by
                'value'   => $year, // Value to match
                'compare' => '=', // Comparison operator
                'type'    => 'NUMERIC', // Data type for comparison
            ),
        );
    }

    return $args; // Return modified query arguments
}, 10, 2);

// Register a custom REST API route to get all bios connected as speakers in events
add_action('rest_api_init', function() {
    register_rest_route('wp/v2/custom', '/speakers', array(
        'methods'  => 'GET',
        'callback' => 'get_all_related_speakers', // Function to handle the request
        'permission_callback' => '__return_true', // Allow access without permissions check
        'args' => array(
            'year' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param); // Validate that 'year' is numeric
                }
            )
        )
    ));
});

// Function to retrieve all bios connected as speakers in events
function get_all_related_speakers($request) {
    // Check if the Posts 2 Posts plugin is active
    if (!function_exists('p2p_type')) {
        return new WP_Error('plugin_not_active', 'Posts 2 Posts plugin is not active', array('status' => 500)); // Return error if plugin is not active
    }

    $year = $request->get_param('year'); // Get 'year' parameter from the request

    // Set up query arguments to fetch events
    $query_args = array(
        'post_type' => 'event',
        'posts_per_page' => -1, // Fetch all events
    );

    if ($year) {
        $query_args['meta_query'] = array(
            array(
                'key' => 'year',
                'value' => $year,
                'compare' => '='
            )
        );
    }

    $posts = get_posts($query_args); // Fetch events based on query arguments

    // Prepare an array to store bios data
    $all_bios = [];

    // Loop through each event post to get its connected bios
    foreach ($posts as $post) {
        $connected_type = 'event_to_bios'; // Connection type
        $connected = p2p_type($connected_type)->get_connected($post->ID); // Get connected bios

        // Add each connected bio to the array if not already added
        foreach ($connected->posts as $bio) {
            if (!isset($all_bios[$bio->ID])) {
                $all_bios[$bio->ID] = [
                    'ID' => $bio->ID,
                    'name' => $bio->post_title,
                    'slug' => $bio->post_name,
                    'featured-image' => (generate_image_srcset($bio->ID)) ? generate_image_srcset($bio->ID) : null ,// (get_the_post_thumbnail_url($bio->ID)) ? get_the_post_thumbnail_url($bio->ID) : null,
                ];
            }
        }
    }

    // Return the list of bios as a REST API response
    return rest_ensure_response(array_values($all_bios));
}


// Add Year column to the Events admin post list
add_filter('manage_event_posts_columns', 'add_year_column');
function add_year_column($columns) {
    $columns['year'] = __('Year'); // Add 'Year' column header
    return $columns; // Return modified columns
}

// Populate the Year column in the Events admin post list
add_action('manage_event_posts_custom_column', 'fill_year_column', 10, 2);
function fill_year_column($column, $post_id) {
    if ($column === 'year') {
        $year = get_post_meta($post_id, 'year', true); // Retrieve 'year' meta value
        echo $year ? $year : __('No Year', 'text_domain'); // Display year or 'No Year' if not available
    }
}

// Make the Year column sortable in the Events admin post list
add_filter('manage_edit-event_sortable_columns', 'make_year_column_sortable');
function make_year_column_sortable($columns) {
    $columns['year'] = 'year'; // Define 'year' as a sortable column
    return $columns; // Return modified columns
}

// Sort events by Year in the Events admin post list
add_action('pre_get_posts', 'sort_by_year');
function sort_by_year($query) {
    if (!is_admin()) {
        return; // Ensure this applies only to the admin area
    }

    $orderby = $query->get('orderby'); // Get the 'orderby' parameter

    if ($orderby === 'year') {
        $query->set('meta_key', 'year'); // Set meta key for sorting
        $query->set('orderby', 'meta_value_num'); // Sort by numeric value
    }
}

// Filter Conferences by Year in REST API queries
function filter_conference_by_year($query_args, $request) {
    $year = $request->get_param('year'); // Retrieve 'year' parameter from the request

    if ($year) {
        $meta_query = array(
            array(
                'key'   => 'year', // Meta key to filter by
                'value' => $year, // Value to match
                'compare' => '=', // Comparison operator
            ),
        );

        $query_args['meta_query'] = $meta_query; // Set meta query for filtering
    }

    return $query_args; // Return modified query arguments
}

add_filter('rest_conference_query', 'filter_conference_by_year', 10, 2);

// Filter Episodes by Created At date in REST API queries
function filter_episode_by_created_at($query_args, $request) {
    $year = $request->get_param('year'); // Retrieve 'year' parameter from the request
    $month = $request->get_param('month'); // Retrieve 'month' parameter from the request

    if ($year || $month) {
        $meta_query = array(
            'relation' => 'AND', // Combine conditions with AND
        );

        if ($year) {
            $meta_query[] = array(
                'key'     => 'created_at', // Meta key to filter by
                'value'   => $year, // Value to match
                'compare' => 'LIKE', // Comparison operator
            );
        }

        if ($month) {
            $meta_query[] = array(
                'key'     => 'created_at', // Meta key to filter by
                'value'   => sprintf('-%02d-', $month), // Format month for matching
                'compare' => 'LIKE', // Comparison operator
            );
        }

        $query_args['meta_query'] = $meta_query; // Set meta query for filtering
    }

    return $query_args; // Return modified query arguments
}

add_filter('rest_episode_query', 'filter_episode_by_created_at', 10, 2);

// Add thumbnail URL to Conference REST API responses
function add_thumbnail_url_to_conference($response, $post, $request) {
    $thumbnail_id = get_post_thumbnail_id($post->ID); // Retrieve the ID of the featured image
    $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'full') : ''; // Get the URL of the featured image or an empty string
    $response->data['thumbnail_url'] = (generate_image_srcset($post->ID)) ? generate_image_srcset($post->ID) : null;//$thumbnail_url; // Add the thumbnail URL to the response data

    return $response; // Return modified response
}
add_filter('rest_prepare_conference', 'add_thumbnail_url_to_conference', 10, 3); // Apply this filter to the 'conference' REST API response

// Register a custom REST API endpoint for retrieving feed items by category
function register_feed_items_by_category_endpoint() {
    register_rest_route('wp/v2', '/feed-items', array(
        'methods' => 'GET', // Define HTTP GET method for this endpoint
        'callback' => 'get_feed_items_by_category', // Function to handle the request
        'permission_callback' => '__return_true', // Allow public access to this endpoint
        'args' => array(
            'categories' => array(
                'default' => '', // Default value for the 'categories' parameter
                'sanitize_callback' => 'sanitize_text_field', // Sanitize input
            ),
            'per_page' => array(
                'default' => 10, // Default value for the 'per_page' parameter
                'sanitize_callback' => 'absint', // Sanitize input
            ),
            'page' => array(
                'default' => 1, // Default value for the 'page' parameter
                'sanitize_callback' => 'absint', // Sanitize input
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_feed_items_by_category_endpoint'); // Hook to initialize REST API

// Function to handle the request and return feed items by category
function get_feed_items_by_category($request) {
    $categories = $request['categories']; // Retrieve categories parameter from request
    $per_page = $request['per_page']; // Retrieve per_page parameter from request
    $page = $request['page']; // Retrieve page parameter from request

    $args = array(
        'post_type' => 'wprss_feed_item', // Specify custom post type
        'posts_per_page' => $per_page, // Set number of posts per page
        'paged' => $page, // Set pagination
    );

    // Filter by categories if provided
    if ($categories) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'wprss_category', // Custom taxonomy to filter by
                'field' => 'slug', // Use slugs for matching
                'terms' => explode(',', $categories), // Convert comma-separated string to array
            ),
        );
    }

    // Perform the query to retrieve feed items
    $query = new WP_Query($args);
    $feed_items = $query->posts; // Get the posts

    // Handle case where no feed items are found
    if (empty($feed_items)) {
        return new WP_Error('no_feed_items', 'No feed items found', array('status' => 404)); // Return 404 error
    }

    // Prepare data for response
    $data = array();

    foreach ($feed_items as $post) {
        $post_categories = wp_get_post_terms($post->ID, 'wprss_category', array('fields' => 'names')); // Get category names
	    $thumbnail_url = get_post_meta($post->ID, 'wprss_item_thumbnail', true);
        // $thumbnail_def = get_post_meta($post->ID, 'wprss_item_thumbnail', true); // Get thumbnail URL
        $wprss_best_images = get_post_meta($post->ID, 'wprss_best_images', true); // Get Best Image URL
        $wprss_images = get_post_meta($post->ID, 'wprss_images', true); // Get image URL
        $author = get_post_meta($post->ID, 'wprss_item_source_name', true); // Get author name
        // Compairing images values for thumbnails
       /*
        if($thumbnail_def != ''){
            $thumbnail_url = $thumbnail_def; // Assign thumbnail if this variable has a value
        } elseif($wprss_best_images != '') {
            $thumbnail_url = $wprss_best_images; // Assign thumbnail if this variable has a value
        } elseif($wprss_images != '') {
            $thumbnail_url = $wprss_images; // Assign thumbnail if this variable has a value
        } else {
            $thumbnail_url = ''; // Assign empty string if no thumbnail is available
        }
            */

        // Build the response data array
        $data[] = array(
            'id' => $post->ID,
            'title' => get_the_title($post), // Post title
            'content' => apply_filters('the_content', $post->post_content), // Post content with filters applied
            'link' => get_post_meta($post->ID, 'wprss_item_permalink', true), // Post permalink
            'date' => get_the_date('c', $post), // Post date in ISO 8601 format
            'categories' => $post_categories, // Post categories
            'thumbnail' => $thumbnail_url, // Post Thumbnail url
            'author' => $author, // Post author
        );
    }

    // Prepare the full response including pagination data
    $response = array(
        'items' => $data, // Feed items data
        'pagination' => array(
            'total_pages' => $query->max_num_pages, // Total number of pages
            'total_items' => $query->found_posts, // Total number of items
            'current_page' => $page, // Current page
            'items_per_page' => $per_page, // Items per page
        ),
    );

    return new WP_REST_Response($response, 200); // Return the response with HTTP 200 status
}

// Register a custom REST API endpoint to retrieve WPRSS categories
function register_wprss_category_endpoint() {
    register_rest_route('wp/v2', '/news-feed-categories', array(
        'methods' => 'GET', // Define HTTP GET method for this endpoint
        'callback' => 'get_wprss_categories', // Function to handle the request
        'permission_callback' => '__return_true', // Allow public access to this endpoint
    ));
}
add_action('rest_api_init', 'register_wprss_category_endpoint'); // Hook to initialize REST API

// Function to handle the request and return WPRSS categories
function get_wprss_categories() {
    // Retrieve terms from the 'wprss_category' taxonomy
    $categories = get_terms(array(
        'taxonomy' => 'wprss_category', // Custom taxonomy to fetch terms from
        'hide_empty' => false, // Include terms that have no posts
    ));

    // Handle case where no categories are found or if an error occurs
    if (empty($categories) || is_wp_error($categories)) {
        return new WP_Error('no_categories', 'No categories found', array('status' => 404)); // Return 404 error
    }

    // Prepare data for response
    $data = array();

    foreach ($categories as $category) {
        // Build the response data array for each category
        $data[] = array(
            'id' => $category->term_id, // Category ID
            'name' => $category->name, // Category name
            'slug' => $category->slug, // Category slug
            'description' => $category->description, // Category description
            'count' => $category->count, // Number of posts in the category
        );
    }

    // Return the categories data with HTTP 200 status
    return new WP_REST_Response($data, 200);
}

// Register a custom REST API endpoint for merging posts from multiple custom post types
add_action('rest_api_init', function () {
    register_rest_route('wp/v2/custom', '/resources', array(
        'methods' => 'GET', // Define HTTP GET method for this endpoint
        'callback' => 'get_merged_posts', // Function to handle the request
        'args' => array(
            'search' => array(
                'required' => false, // Parameter is optional
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param); // Validate that parameter is a string
                }
            ),
            'page' => array(
                'required' => false, // Parameter is optional
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param); // Validate that parameter is numeric
                }
            ),
            'category' => array(
                'required' => false, // Parameter is optional
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param); // Validate that parameter is a string
                }
            ),
            'post_type' => array(
                'required' => false, // Parameter is optional
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param) && in_array($param, array('resource', 'bios', 'article', 'episode', 'allie')); // Validate that parameter is a valid post type
                }
            ),
        ),
    ));
}); // Hook to initialize REST API


// Function to handle the request and retrieve posts based on search, pagination, category, and post type
function get_merged_posts($request) {
    // Retrieve parameters from the request
    $search = $request->get_param('search'); // Search term for post title or tags
    $page = $request->get_param('page') ? (int) $request->get_param('page') : 1; // Current page number
    $category = $request->get_param('category'); // Category filter
    $post_type = $request->get_param('post_type'); // Post type filter

    // Define allowed post types
    $allowed_post_types = array('resource', 'bios', 'article', 'episode', 'allie');

    // Determine which post types to query based on the provided post type
    $post_types = !empty($post_type) && in_array($post_type, $allowed_post_types) ? array($post_type) : $allowed_post_types;

    // Number of posts per page
    $posts_per_page = 9;

    // Arrays to hold post IDs from title and tag searches
    $title_post_ids = array();
    $tag_post_ids = array();

    // Perform search query based on post titles
    if (!empty($search)) {
        $title_query_args = array(
            'post_type' => $post_types,
            'posts_per_page' => -1, // Retrieve all matching posts
            'post_status' => 'publish',
            's' => $search, // Search term
            'fields' => 'ids', // Only return post IDs
        );
        $title_query = new WP_Query($title_query_args);
        $title_post_ids = $title_query->posts;
    }

    // Perform search query based on post tags
    if (!empty($search)) {
        $tag_query_args = array(
            'post_type' => $post_types,
            'posts_per_page' => -1, // Retrieve all matching posts
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'post_tag', // Filter by tags
                    'field' => 'name',
                    'terms' => $search,
                ),
            ),
            'fields' => 'ids', // Only return post IDs
        );
        $tag_query = new WP_Query($tag_query_args);
        $tag_post_ids = $tag_query->posts;
    }

    // Combine and remove duplicate post IDs from title and tag queries
    $combined_post_ids = array_unique(array_merge($title_post_ids, $tag_post_ids));

    // Prepare arguments for final query based on provided filters
    if (!empty($post_type)) {
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'post__in' => $combined_post_ids,
        );
    } else {
        $args = array(
            'post_type' => $post_types,
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'post__in' => $combined_post_ids,
        );
    }

    // Add category filter if provided
    if (!empty($category)) {
        $args['category_name'] = $category;
    }

    // Execute the query
    $query = new WP_Query($args);

    // Prepare response data
    $items = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID(); // Get current post ID
            $post_type = get_post_type(); // Get current post type

            $tags = wp_get_post_tags($post_id, array('fields' => 'names')); // Get tags for the post

            $is_pdf = false;
            // Determine if the post is a PDF resource
            if ($post_type === 'resource') {
                $pdf_file = get_field('pdf', $post_id);
                $is_pdf_url = get_field('is_it_a_pdf_the_resource_url', $post_id);
                $is_pdf = $pdf_file ? true : ($is_pdf_url ? true : false);
            }

            $url = null;
            // Get URL for the resource or ally post types
            if ($post_type === 'resource') {
                $pdf_url = get_field('pdf', $post_id);
                $resource_url = get_field('resource_url', $post_id);
                $url = $pdf_url ? $pdf_url : ($resource_url ? $resource_url : null);
            }

            if( $post_type === 'allie'){
                $url = get_field('url', $post_id);
            }

            // Get the date for the post, with special handling for episodes
            $date = ($post_type === 'episode') ? get_post_meta($post_id, 'created_at', true) : get_the_date('c', $post_id);

            // Build the response data array for the post
            $items[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'slug' => get_post_field('post_name', $post_id),
                'tags' => $tags,
                'isPdf' => $is_pdf,
                'excerpt' => get_the_excerpt(),
                'collectionType' => $post_type,
                'url' => $url,
                'date' => $date,
            );
        }
        wp_reset_postdata(); // Reset post data after query
    }

    // Prepare and return the final response with pagination information
    $response = array(
        'items' => $items,
        'pagination' => array(
            'total_pages' => $query->max_num_pages,
            'total_items' => $query->found_posts,
            'current_page' => $page,
            'items_per_page' => $posts_per_page,
        ),
    );

    return new WP_REST_Response($response, 200); // Return the response with HTTP 200 status
}

/**
 * Hide the Clear REST Cache button from the admin header
 */
function display_clear_cache_button($show) {
    return false; // Do not display the button
}
add_filter('wp_rest_cache/display_clear_cache_button', 'display_clear_cache_button', 10, 1);

/**
 * Register custom endpoints with the wp_rest_cache to enable caching
 */
function wprc_add_custom_endpoints($allowed_endpoints) {
    if (!in_array('custom', $allowed_endpoints['wp/v2'] )) {
        $allowed_endpoints['wp/v2'][] = 'custom'; // Allow 'custom' endpoint
    }

    return $allowed_endpoints;
}
add_filter('wp_rest_cache/allowed_endpoints', 'wprc_add_custom_endpoints', 10, 1);

/**
 * Register the feed-items endpoint with the wp_rest_cache to enable caching
 */
function wprc_add_feed_items_endpoint($allowed_endpoints) {
    if (!in_array('feed-items', $allowed_endpoints['wp/v2'] )) {
        $allowed_endpoints['wp/v2'][] = 'feed-items'; // Allow 'feed-items' endpoint
    }

    return $allowed_endpoints;
}
add_filter('wp_rest_cache/allowed_endpoints', 'wprc_add_feed_items_endpoint', 10, 1);

/**
 * Register the news-feed-categories endpoint with the wp_rest_cache to enable caching
 */
function wprc_add_news_feed_categories_endpoint($allowed_endpoints) {
    if (!in_array('news-feed-categories', $allowed_endpoints['wp/v2'] )) {
        $allowed_endpoints['wp/v2'][] = 'news-feed-categories'; // Allow 'news-feed-categories' endpoint
    }

    return $allowed_endpoints;
}
add_filter('wp_rest_cache/allowed_endpoints', 'wprc_add_news_feed_categories_endpoint', 10, 1);

/**
 * Set a custom timeout for HTTP requests
 */
function custom_http_request_timeout($timeout) {
  return 30; // Set timeout to 30 seconds
}
add_filter('http_request_timeout', 'custom_http_request_timeout', 10, 1);

// Upload and optimize images to Amazon S3

add_action('after_image_optimized', function($attachment_id) {
    if (class_exists('AS3CF_Plugin')) {
        $as3cf = new AS3CF_Plugin();
        $as3cf->upload_attachment($attachment_id);
    }
});



add_action('graphql_register_types', function() {
    register_graphql_field('RootQuery', 'uniqueEventYears', [
        'type' => ['list_of' => 'String'],
        'description' => __('Get unique event years', 'customTemplate'),
        'resolve' => function() {
            global $wpdb;

            // Query the database for unique year meta values
            $results = $wpdb->get_col("
                SELECT DISTINCT meta_value 
                FROM $wpdb->postmeta 
                WHERE meta_key = 'year' 
                AND post_id IN (
                    SELECT ID FROM $wpdb->posts 
                    WHERE post_type = 'event'
                    AND post_status = 'publish'
                )
                ORDER BY meta_value DESC
            ");

            // Return the unique years
            return $results;
        }
    ]);
});


// add Topic column in dashboard admin

function add_topic_column($columns) {
    $columns['topic'] = __('Topic', 'textdomain');
    return $columns;
}

function display_topic_column($column, $post_id) {
    if ($column === 'topic') {
        $terms = get_the_terms($post_id, 'topic');
        if (!empty($terms)) {
            $topics = array();
            foreach ($terms as $term) {
                $topics[] = esc_html($term->name);
            }
            echo implode(', ', $topics);
        } else {
            echo __('No Topic', 'textdomain');
        }
    }
}

function add_topic_column_to_post_types() {
    $post_types = array('bios', 'resource', 'article', 'allie', 'event', 'episode');
    
    foreach ($post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'add_topic_column');
        add_action("manage_{$post_type}_posts_custom_column", 'display_topic_column', 10, 2);
    }
}
add_action('admin_init', 'add_topic_column_to_post_types');


// Add new column for the 'target' field
function add_resource_columns($columns) {
    $columns['target'] = __('Target', 'textdomain');
    return $columns;
}
add_filter('manage_resource_posts_columns', 'add_resource_columns');

// Populate the column with the value from the ACF 'target' field
function fill_resource_columns($column, $post_id) {
    if ($column === 'target') {
        $target = get_field('target', $post_id);
        echo esc_html($target);
    }
}

add_action('manage_resource_posts_custom_column', 'fill_resource_columns', 10, 2);


// Add new columns to the post list of the custom post type 'redirect'
function add_redirect_columns($columns) {
    $columns['type'] = __('Type');
    $columns['old_url'] = __('Old URL');
    $columns['new_url'] = __('New URL');
    return $columns;
}
add_filter('manage_redirect_posts_columns', 'add_redirect_columns');

// Populate the custom columns with the values from the ACF fields
function fill_redirect_columns($column, $post_id) {
    if ($column === 'type') {
        echo get_field('type', $post_id);
    }
    if ($column === 'old_url') {
        echo get_field('old_url', $post_id);
    }
    if ($column === 'new_url') {
        echo get_field('new_url', $post_id);
    }
}
add_action('manage_redirect_posts_custom_column', 'fill_redirect_columns', 10, 2);

// Make the custom columns sortable
function redirect_sortable_columns($columns) {
    $columns['type'] = 'type';
    $columns['old_url'] = 'old_url';
    $columns['new_url'] = 'new_url';
    return $columns;
}
add_filter('manage_edit-redirect_sortable_columns', 'redirect_sortable_columns');



function modify_resource_rest_response($response, $post, $request) {
    // Check if the post type is 'resource'
    if ($post->post_type === 'resource' && get_field('pdf', $post->ID)) {
        $response->data['acf']['resource_url'] = get_field('pdf', $post->ID);  
    }

    return $response;
}
add_filter('rest_prepare_resource', 'modify_resource_rest_response', 10, 3);


// Redirect log

function create_custom_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_redirects';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        date int(11) NOT NULL,
        old_url varchar(255) NOT NULL,
        new_url varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Log a message to ensure the function is being called
    error_log('Creating custom table...');

    dbDelta($sql);

    // Log the result of the table creation
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log('Table creation failed.');
    } else {
        error_log('Table created successfully.');
    }
}

register_activation_hook(__FILE__, 'create_custom_table');


add_action( 'graphql_register_types', function() {
    register_graphql_mutation( 'addCustomRedirect', [
        'inputFields'         => [
            'date'    => [
                'type'        => 'Int',
                'description' => __( 'Timestamp of the redirect (in seconds)', 'your-textdomain' ),
            ],
            'oldUrl'  => [
                'type'        => 'String',
                'description' => __( 'Old URL to be redirected', 'your-textdomain' ),
            ],
            'newUrl'  => [
                'type'        => 'String',
                'description' => __( 'New URL for the redirect', 'your-textdomain' ),
            ],
        ],
        'outputFields'        => [
            'success' => [
                'type'        => 'Boolean',
                'description' => __( 'Whether the redirect was successfully added', 'your-textdomain' ),
            ],
            'message' => [
                'type'        => 'String',
                'description' => __( 'Message describing the result', 'your-textdomain' ),
            ],
        ],
        'mutateAndGetPayload' => function( $input, $context, $info ) {
            // Check if the user is authenticated (JWT validation)
            if ( ! is_user_logged_in() ) {
                return [
                    'success' => false,
                    'message' => 'Authentication required. Invalid or missing token.',
                ];
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_redirects';

            // Sanitize the input fields
            $date   = intval( $input['date'] );  // Ensure date is stored as integer (timestamp in seconds)
            $oldUrl = esc_url_raw( $input['oldUrl'] );
            $newUrl = esc_url_raw( $input['newUrl'] );

            // Validate that all fields are provided
            if ( empty( $date ) || empty( $oldUrl ) || empty( $newUrl ) ) {
                return [
                    'success' => false,
                    'message' => 'All fields (date, oldUrl, newUrl) are required.',
                ];
            }

            // Insert the data into the custom table
            $result = $wpdb->insert(
                $table_name,
                [
                    'date'    => $date,   // Store timestamp in seconds as integer
                    'old_url' => $oldUrl,
                    'new_url' => $newUrl,
                ]
            );

            // Return the result of the mutation
            return [
                'success' => (bool) $result,
                'message' => $result ? 'Redirect successfully added.' : 'Failed to add redirect.',
            ];
        },
    ] );
} );



// Hook to add the submenu page under 'Redirects' custom post type
function add_redirect_log_submenu() {
    add_submenu_page(
        'edit.php?post_type=redirect',   // Parent slug (the 'Redirect' custom post type)
        'Redirect Logs',                  // Page title
        'Redirect Logs',                  // Menu title
        'manage_options',                 // Capability required to access the page
        'redirect-logs',                  // Menu slug
        'display_redirect_logs_page'      // Callback function to display the content
    );
}
add_action('admin_menu', 'add_redirect_log_submenu');

// Callback function to display the logs
function display_redirect_logs_page() {
    echo '<div class="wrap">';
    echo '<h1>Redirect Logs</h1>';

    // Here, you can display your log data
    // Example placeholder content
    echo '<p>Here you will display the log of redirects.</p>';

    // Display actual log data (this is a placeholder, replace with actual log display)
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_redirects'; // Assuming you have a log table

    // Get log data from the database
    $logs = $wpdb->get_results("SELECT * FROM $table_name");

    if ($logs) {
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID</th><th>Date</th><th>Old URL</th><th>New URL</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log->id) . '</td>';
            echo '<td>' . esc_html(date('Y-m-d H:i:s', $log->date)) . '</td>';
            echo '<td>' . esc_html($log->old_url) . '</td>';
            echo '<td>' . esc_html($log->new_url) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No logs found.</p>';
    }

    echo '</div>';
}


// Update plugins in local
add_filter('https_ssl_verify', '__return_false');
