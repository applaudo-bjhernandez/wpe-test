<?php
/**
 * This file handles registering the add-on settings and rendering the settings tab
 *
 * @since 1.0
 */

/**
 * Handles the registration of settings and sections, and the rendering of the settings page.
 *
 * @since 1.0
 */
final class WPRSS_FTP_Settings
{
    /*===== CONSTANTS AND STATIC MEMBERS ======================================================================*/

    /**
     * The name of the options array, as stored in the database.
     */
    const OPTIONS_NAME = 'wprss_settings_ftp';
    /**
     * FTP Settings tab slug
     */
    const TAB_SLUG = 'ftp_settings';

    /**
     * The Singleton instance
     */
    private static $instance;

    /*===== CONSTRUCTOR AND SINGLETON GETTER ==================================================================*/

    /**
     * Constructor
     *
     * @since 1.0
     */
    public function __construct()
    {
        if (self::$instance === null) {
            # Initialize
            add_action('wprss_admin_init', [$this, 'register_settings']);

            /*
             * @since 3.7.4
             * Making sure that FeedsAPI can never be selected
             */
            add_action('wprss_ftp_option_value_full_text_rss_service', [$this, 'feedsapi_setting_fallback'], 10, 3);
        } else {
            wp_die(__('WPRSS_FTP_Settings class is a singleton class and cannot be redeclared.', WPRSS_TEXT_DOMAIN));
        }
    }

    /**
     * Returns the singleton instance
     *
     * @return WPRSS_FTP_Settings
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /*===== SETTINGS GETTERS =================================================================================*/

    /**
     * Returns the default settings.
     *
     * @since 1.0
     * @return array An associative array of key => value setting pairs.
     */
    public function get_defaults()
    {
        static $fallback_author = null;

        if ($fallback_author === null) {
            $fallback_author = WPRSS_FTP_Utils::get_admin('user_login');
        }

        $wp_comment_status = get_option('default_comment_status', 'open');

        return apply_filters('wprss_ftp_default_settings', [
            // The post site in which to import posts
            'post_site'              => '',
            // The Post type to use
            'post_type'              => 'post',
            // The status to assign to imported posts
            'post_status'            => 'draft',
            // The format to assign to imported posts
            'post_format'            => 'standard',
            // The post's publish date
            'post_date'              => 'original',
            // The post's comment status
            'comment_status'         => $wp_comment_status,
            // Whether to link back to the original post
            'source_link'            => 'false',
            // Whether to only show the source link on singular posts
            'source_link_singular'   => 'false',
            'source_link_text'       => 'This *post* was originally published on **this site**',
            'source_link_position'   => 'before',
            // The flag that determines whether to forcefully retrieval the full feed content.
            'force_full_content'     => 'false',
            // Whether to import the post excerpt.
            'import_excerpt'         => 'false',
            // The default word limit for post content. Empty value disables the limit
            'post_word_limit'        => '',
            // The default value for whether to include a rel="canonical" link in the page head for imported posts
            'canonical_link'         => 'true',
            // 'true' | 'false' | 'general'. Falls back to 'general'.
            'word_limit_enabled'     => 'general',
            // The word limit. 0 for disabled.
            'word_limit'             => '0',
            // The trimming type. 'db' or 'excerpt'. Falls back to 'db'.
            'trimming_type'          => 'db',
            // The default author to use for imported posts
            'def_author'             => '',
            // The method to use when the feed author is not found
            'author_fallback_method' => 'existing',
            // The author to fall back upon when 'using existing'
            'fallback_author'        => $fallback_author,
            // The action to take when no author is found. Either 'fallback' or 'skip'
            'no_author_found'        => 'fallback',
            // The default post taxonomy
            'post_taxonomy'          => 'category',
            // The default post taxonomy terms
            'post_terms'             => [],
            // The default setting for whether to auto create tax terms for feed categories
            'post_auto_tax_terms'    => false,
            // The default tags to attach to posts
            'post_tags'              => '',
            // Whether to use featured images
            'use_featured_image'     => 'true',
            // Which image to use as a featured image. 'first', 'last' or 'thumb'
            'featured_image'         => 'first',
            // Whether to fall back to the image provided by the feed
            'fallback_to_feed_image' => 'true',
            // Whether to remove the chosen featured image from post content
            'remove_ft_image'        => 'false',
            // Whether posts must have a featured image to be imported
            'must_have_ft_image'     => 'false',
            // The minimum width of images to import
            'image_min_width'        => '80',
            // The minimum height of images to import
            'image_min_height'       => '80',
            // If true, images are saved locally in the media. If false, they are linked from the source.
            'save_images_locally'    => 'true',
            // If true, image `srcset` attributes are processed and all image sizes are saved
            'save_all_image_sizes'   => 'true',
            'audio_player'           => 'false',
            'audio_player_pos'       => 'false',
            // The post language - only applies if WPML is active
            'post_language'          => 'en',
            // The default text to prepend to posts
            'post_prepend'           => '',
            // The default text to append to posts
            'post_append'            => '',
            // Whether to add custom content in the feed
            'add_content_in_feed'    => false,
            // Whether to disable the visual editors by default
            'disable_visual_editor'  => false,
            // The CSS selectors for the elements to remove from post content
            'extraction_rules'       => [],
            // The manipulation types for each extraction rule
            'extraction_rules_types' => [],
            // The affiliate link suffix to add
            'affiliate_link'         => 'false',
            // Allowing of embedded content in posts
            'allow_embedded_content' => 'false',
            // Full text RSS service type.
            'full_text_rss_service'  => 'free',
            // The feed namespaces added by the user.
            'user_feed_namespaces'   => [
                'names' => [],
                'urls'  => [],
            ],
            // The custom field mappings default value
            'custom_fields'          => [],
            // Allowing requests to URLs, which would normally be blocked by wp_http_validate_url()
            //'allow_local_requests'		=>	'false',
            # Allows the use of wprss_feed_item when set to TRUE
            'legacy_enabled'         => 'true',
            'taxonomies'             => '',
            'powerpress_enabled'     => 'false',
            'link_posts_to_original' => 'false',
        ]);
    }

    /**
     * Returns the default value for the given setting.
     * Will throw an exception if the name given is not found.
     *
     * @since 1.0
     * @param string $option The name of the option whose default to return.
     * @return mixed The value of the option for the given option name
     */
    public function get_default($option)
    {
        $all = $this->get_defaults();
        return $all[$option];
    }

    /**
     * Returns an option of sub-option form the database.
     *
     * @since 1.0
     * @param mixed        The value to return if the option was not found. Ommit or
     *                    use '!default' to get the default value from get_default()
     * @param array    Optional. The key of the sub option to retrieve.
     * @return mixed    The value of the (sub)option with the key(s)
     */
    public function get($sub_option = null, $default = '!default')
    {
        $option = get_option(self::OPTIONS_NAME, $this->get_defaults());
        if ($sub_option !== null) {
            if (array_key_exists($sub_option, $option)) {
                return $this->_normalizeOptionValue($option[$sub_option], $sub_option);
            } elseif (strtolower($default) === '!default') {
                return $this->_normalizeOptionValue($this->get_default($sub_option), $sub_option);
            } else {
                return $this->_normalizeOptionValue($default, $sub_option);
            }
        } else {
            $final_options = [];
            foreach ($this->get_defaults() as $key => $value) {
                if (isset($option[$key])) {
                    $value = $this->_normalizeOptionValue($option[$key], $key);
                }
                $final_options[$key] = $value;
            }
            return $final_options;
        }
    }

    /**
     * Normalizes an option's value before it is returned.
     *
     * @since 3.7.4
     *
     * @param mixed $value The value to normalize.
     * @param string $name The name of the option.
     * @param int|string|null $postId The ID of the post, to which the option belongs, if any.
     * @return mixed The normalized value.
     */
    protected function _normalizeOptionValue($value, $name, $postId = null)
    {
        $value = apply_filters('wprss_ftp_option_value', $value, $name, $postId);
        return apply_filters('wprss_ftp_option_value_' . $name, $value, $name, $postId);
    }

    /**
     * Returns the final, computed options for a feed.
     * These settings are the general settings, merged against the feed's own meta data settings.
     *
     * @since 1.0
     */
    public function get_computed_options($post_id)
    {
        $fields = WPRSS_FTP_Meta::get_instance()->get_meta_fields('all');
        $options = $this->get();

        $meta = [];
        foreach ($fields as $key => $_) {
            $value = WPRSS_FTP_Meta::get_instance()->get_meta($post_id, $key);

            if (!empty($value)) {
                $meta[$key] = $value;
            }
        }

        return array_merge($options, $meta);
    }

#== SETTINGS REGISTRATION =====================================================================================

    public function get_section_docs_link_template()
    {
        /** @noinspection HtmlUnknownTarget */
        return apply_filters('wprss_ftp_section_docs_link_template',
            '<a class="wprss-section-tooltip-handle fa fa-info-circle %3$s" href="%1$s" title="%2$s" target="_blank"></a>');
    }

    public function get_section_docs_link_url($sectionCode = null, $default = null)
    {
        $docUrls = apply_filters('wprss_ftp_section_docs_link_urls', [
            'wprss_settings_ftp_general_section'    => 'https://kb.wprssaggregator.com/article/305-how-to-set-up-feed-to-posts-general-plugin-settings',
            'wprss_settings_ftp_taxonomies_section' => 'https://kb.wprssaggregator.com/article/306-how-to-set-up-feed-to-posts-taxonomy-options-categories-tags-etc',
            'wprss_settings_ftp_authors_section'    => 'https://kb.wprssaggregator.com/article/307-how-to-set-up-feed-to-posts-author-options',
            'wprss_settings_ftp_images_section'     => 'https://kb.wprssaggregator.com/article/308-how-to-set-up-feed-to-posts-image-options',
            'wprss_settings_ftp_full_text_section'  => 'https://kb.wprssaggregator.com/article/96-an-introduction-to-full-text-rss-feeds',
            'wprss_settings_ftp_namespaces_section' => 'https://kb.wprssaggregator.com/article/311-how-to-set-up-feed-to-posts-custom-field-mapping',
        ]);

        if (is_null($sectionCode)) {
            return $docUrls;
        }

        return isset($docUrls[$sectionCode])
            ? $docUrls[$sectionCode]
            : $default;
    }

    public function get_section_docs_link_html($sectionCode)
    {
        $template = $this->get_section_docs_link_template();
        $class = '';

        if (!($linkUrl = $this->get_section_docs_link_url($sectionCode))) {
            return apply_filters('wprss_ftp_section_docs_link_html', null, $sectionCode, $template, $linkUrl, $class);
        }

        return apply_filters(
            'wprss_ftp_section_docs_link_html',
            sprintf(
                $template,
                $linkUrl,
                __('Click here to view documentation for this section in a new tab', WPRSS_TEXT_DOMAIN),
                $class
            ),
            $sectionCode,
            $template,
            $linkUrl,
            $class
        );
    }

    /**
     * Registers the settings page, sections and fields.
     *
     * @since 1.0
     */
    public function register_settings()
    {
        // Register Page
        register_setting(
            self::OPTIONS_NAME,
            self::OPTIONS_NAME,
            [$this, 'validate_settings']
        );

        // Register Sections

        add_settings_section(
            'wprss_settings_ftp_general_section',
            __('General Settings', WPRSS_TEXT_DOMAIN)
            . $this->get_section_docs_link_html('wprss_settings_ftp_general_section'),
            [$this, 'render_general_section'],
            'wprss_settings_ftp'
        );

        add_settings_section(
            'wprss_settings_ftp_taxonomies_section',
            __('Taxonomies', WPRSS_TEXT_DOMAIN)
            . $this->get_section_docs_link_html('wprss_settings_ftp_taxonomies_section'),
            [$this, 'render_taxonomies_section'],
            'wprss_settings_ftp'
        );

        add_settings_section(
            'wprss_settings_ftp_authors_section',
            __('Authors', WPRSS_TEXT_DOMAIN)
            . $this->get_section_docs_link_html('wprss_settings_ftp_authors_section'),
            [$this, 'render_authors_section'],
            'wprss_settings_ftp'
        );

        add_settings_section(
            'wprss_settings_ftp_images_section',
            __('Images', WPRSS_TEXT_DOMAIN)
            . $this->get_section_docs_link_html('wprss_settings_ftp_images_section'),
            [$this, 'render_images_section'],
            'wprss_settings_ftp'
        );

        add_settings_section(
            'wprss_settings_ftp_full_text_section',
            __('Full Text RSS', WPRSS_TEXT_DOMAIN)
            . $this->get_section_docs_link_html('wprss_settings_ftp_full_text_section'),
            [$this, 'render_full_text_section'],
            'wprss_settings_ftp'
        );

        add_settings_section(
            'wprss_settings_ftp_namespaces_section',
            __('Custom Namespaces', WPRSS_TEXT_DOMAIN)
            . $this->get_section_docs_link_html('wprss_settings_ftp_namespaces_section'),
            [$this, 'render_namespaces_section'],
            'wprss_settings_ftp'
        );

        #== GENERAL SECTION ==========

        // POST TYPE
        add_settings_field(
            'wprss-settings-ftp-post-type',
            __('Post type', WPRSS_TEXT_DOMAIN),
            [$this, 'render_post_type'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // POST STATUS
        add_settings_field(
            'wprss-settings-ftp-post-status',
            __('Post status', WPRSS_TEXT_DOMAIN),
            [$this, 'render_post_status'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // POST FORMAT
        add_settings_field(
            'wprss-settings-ftp-post-format',
            __('Post format', WPRSS_TEXT_DOMAIN),
            [$this, 'render_post_format'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // POST DATE
        add_settings_field(
            'wprss-settings-ftp-post-date',
            __('Post date', WPRSS_TEXT_DOMAIN),
            [$this, 'render_post_date'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // ENABLE COMMENTS
        add_settings_field(
            'wprss-settings-ftp-comment-status',
            __('Enable comments', WPRSS_TEXT_DOMAIN),
            [$this, 'render_comment_status'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // Link posts to the original article
        add_settings_field(
            'wprss-link-posts-to-original',
            __('Posts redirect to the original article', 'wprss'),
            [$this, 'render_link_posts_to_original'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // SOURCE LINK
        add_settings_field(
            'wprss-settings-ftp-source-link',
            __('Link back to source?', WPRSS_TEXT_DOMAIN),
            [$this, 'render_source_link'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // SOURCE LINK ONLY FOR SINGULAR POSTS
        add_settings_field(
            'wprss-settings-ftp-source-link-singular',
            __('Only add source link when viewing singular posts ', WPRSS_TEXT_DOMAIN),

            [$this, 'render_source_link_singular'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // SOURCE LINK TEXT
        add_settings_field(
            'wprss-settings-ftp-source-link-text',
            __('Source link text', WPRSS_TEXT_DOMAIN),
            [$this, 'render_source_link_text'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // SOURCE LINK POSITION
        add_settings_field(
            'wprss-settings-ftp-source-link-position',
            __('Source link position', WPRSS_TEXT_DOMAIN),
            [$this, 'render_source_link_position'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // SHOW CUSTOM CONTENT IN FEED
        add_settings_field(
            'wprss-settings-ftp-add-content-in-feed',
            __('Add custom content to the site\'s RSS feed', WPRSS_TEXT_DOMAIN),
            [$this, 'render_add_content_in_feed'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // OPEN LINKS BEHAVIOUR
        /* Removed setting - not needed in add-on
        add_settings_field(
            'wprss-settings-ftp-open-dd',
            __( 'Open Links Behaviour', WPRSS_TEXT_DOMAIN ),
            'wprss_setting_open_dd_callback',
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );
        */

        // SET LINKS NO FOLLOW
        /* Removed setting - not needed in add-on
        add_settings_field(
            'wprss-settings-ftp-follow-dd',
            __( 'Set links as nofollow', WPRSS_TEXT_DOMAIN ),
            'wprss_setting_follow_dd_callback',
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );
        */

        // VIDEO LINKS
        /* Removed setting - not needed in add-on
        add_settings_field(
            'wprss-settings-ftp-video-links',
            __( 'For video feed items use', WPRSS_TEXT_DOMAIN ),
            'wprss_setting_video_links_callback',
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );
        */

        // WORD LIMIT
        add_settings_field(
            'wprss-settings-ftp-word-limit',
            __('Word limit', WPRSS_TEXT_DOMAIN),
            [$this, 'render_word_limit'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // TRIMMING TYPE
        add_settings_field(
            'wprss-settings-ftp-trimming-type',
            __('Trimming type', WPRSS_TEXT_DOMAIN),
            [$this, 'render_trimming_type'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        // CANONICAL LINK
        add_settings_field(
            'wprss-settings-ftp-canonical-link',
            __('Canonical link', WPRSS_TEXT_DOMAIN),
            [$this, 'render_canonical_link'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_general_section'
        );

        #== TAXONOMIES SECTION ==========

        // POST TAXONOMY
        add_settings_field(
            'wprss-settings-ftp-post-taxonomy',
            __("Taxonomy", WPRSS_TEXT_DOMAIN),
            [$this, 'render_post_taxonomy'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_taxonomies_section'
        );

        #== AUTHORS SECTION ==========

        // DEFAULT AUTHOR
        add_settings_field(
            'wprss-settings-ftp-def-author',
            __('Post author', WPRSS_TEXT_DOMAIN),
            [$this, 'render_def_author'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_authors_section'
        );

        #== IMAGES SECTION ==========

        // SAVE IMAGES LOCALLY
        add_settings_field(
            'wprss-settings-ftp-save-images-locally',
            __("Save images locally", WPRSS_TEXT_DOMAIN),
            [$this, 'render_save_images_locally'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_images_section'
        );

        // IMAGE MINIMUM SIZE
        add_settings_field(
            'wprss-settings-ftp-image-min-size',
            __("Image minimum size", WPRSS_TEXT_DOMAIN),
            [$this, 'render_image_minimum_size'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_images_section'
        );

        // USE FEATURED IMAGE
        add_settings_field(
            'wprss-settings-ftp-use-featured-image',
            __('Use a featured image', WPRSS_TEXT_DOMAIN),
            [$this, 'render_use_featured_image'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_images_section'
        );

        // FEATURED IMAGE TO USE
        add_settings_field(
            'wprss-settings-ftp-featured-image',
            __("Featured image to use", WPRSS_TEXT_DOMAIN),
            [$this, 'render_featured_image'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_images_section'
        );

        // FALLBACK TO FEED IMAGE
        add_settings_field(
            'wprss-settings-ftp-fallback-to-feed-image',
            __('Fallback to feed image', WPRSS_TEXT_DOMAIN),
            [$this, 'render_fallback_to_feed_image'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_images_section'
        );

        // ALLOW LOCAL REQUESTS
        /*
        add_settings_field(
            'wprss-settings-ftp-allow-local-requests',
            __( 'Allow local requests', WPRSS_TEXT_DOMAIN ),
            array( $this, 'render_allow_local_requests' ),
            'wprss_settings_ftp',
            'wprss_settings_ftp_images_section'
        );*/

        #== FULL TEXT RSS SECTION ==========

        add_settings_field(
            'wprss-settings-ftp-full-text-rss-service',
            __("Full Text RSS service", WPRSS_TEXT_DOMAIN),
            [$this, 'render_full_text_rss_service'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_full_text_section'
        );

        #== CUSTOM NAMESPACES SECTION ==========

        add_settings_field(
            'wprss-settings-ftp-custom-namespaces',
            __("Namespaces", WPRSS_TEXT_DOMAIN),
            [$this, 'render_custom_namespaces'],
            'wprss_settings_ftp',
            'wprss_settings_ftp_namespaces_section'
        );

        #== LICENSE SETTINGS ==========
        if (version_compare(WPRSS_VERSION, '4.5', '<')) {
            add_settings_section(
                'wprss_settings_ftp_licenses_section',
                __('Feed to Post License', WPRSS_TEXT_DOMAIN),
                [$this, 'license_section_callback'],
                'wprss_settings_license_keys'
            );

            add_settings_field(
                'wprss-settings-license',
                __('License key', WPRSS_TEXT_DOMAIN),
                [$this, 'license_callback'],
                'wprss_settings_license_keys',
                'wprss_settings_ftp_licenses_section'
            );

            add_settings_field(
                'wprss-settings-license-activation',
                __('Activate license', WPRSS_TEXT_DOMAIN),
                [$this, 'license_activation_callback'],
                'wprss_settings_license_keys',
                'wprss_settings_ftp_licenses_section'
            );
        }

        // Add tab to Aggregator Settings page
        add_action('wprss_options_tabs', [$this, 'add_tab']);
        // Add action to register field sections to tab
        add_action('wprss_add_settings_fields_sections', [$this, 'render_settings_page'], 10, 1);

        do_action('wprss_ftp_after_settings_register', $this);
    }


#== SECTION RENDERERS ===============================================================================

    /**
     * General Section
     *
     * @since 1.0
     */
    public function render_general_section()
    {
        echo '<p>' . __('General settings about imported posts', WPRSS_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Authors Section
     *
     * @since 1.0
     */
    public function render_authors_section()
    {
        echo '<p>' . __('Settings about post authors and users.', WPRSS_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Taxonomies Section
     *
     * @since 1.0
     */
    public function render_taxonomies_section()
    {
        echo '<p>' . __('Settings about post taxonomies and tags.', WPRSS_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Images Section
     *
     * @since 1.0
     */
    public function render_images_section()
    {
        echo '<p>' . __('Configure how to handle images found in feeds.', WPRSS_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Full Text RSS Section
     *
     * @since 1.0
     */
    public function render_full_text_section()
    {
        echo '<p>' . __('Configure your full text RSS options.', WPRSS_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Custom Namespaces Section
     *
     * @since 1.0
     */
    public function render_namespaces_section()
    {
        echo '<p>' . __('Manage your RSS feed Namespaces.', WPRSS_TEXT_DOMAIN) . '</p>';
    }


#== FIELD RENDERERS =================================================================================

    #== General Section ========================

    /**
     * Renders the post_type dropdown
     *
     * @since 1.0
     */
    public function render_post_type()
    {
        $post_type = $this->get('post_type');
        $all_post_types = self::get_post_types();
        echo WPRSS_FTP_Utils::array_to_select($all_post_types, [
            'id'       => 'ftp-post-type',
            'name'     => self::OPTIONS_NAME . '[post_type]',
            'selected' => $post_type,
        ]);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'post_type');
    }

    /**
     * Renders the post_status dropdown
     *
     * @since 1.0
     */
    public function render_post_status()
    {
        $post_status = $this->get('post_status');
        $post_statuses = self::get_post_statuses();
        echo WPRSS_FTP_Utils::array_to_select($post_statuses, [
            'id'       => 'ftp-post-status',
            'name'     => self::OPTIONS_NAME . '[post_status]',
            'selected' => $post_status,
        ]);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'post_status');
    }

    /**
     * Renders the post_format dropdown
     *
     * @since 1.0
     */
    public function render_post_format()
    {
        $post_format = $this->get('post_format');
        $post_formats = self::get_post_formats();
        echo WPRSS_FTP_Utils::array_to_select($post_formats, [
            'id'       => 'ftp-post-format',
            'name'     => self::OPTIONS_NAME . '[post_format]',
            'selected' => $post_format,
        ]);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'post_format');
    }

    /**
     * Renders the post_date dropdown
     *
     * @since 1.0
     */
    public function render_post_date()
    {
        $post_date = $this->get('post_date');
        $options = self::get_post_date_options();
        echo WPRSS_FTP_Utils::array_to_select($options, [
            'id'       => 'ftp-post-date',
            'name'     => self::OPTIONS_NAME . '[post_date]',
            'selected' => $post_date,
        ]);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'post_date');
    }

    /**
     * Renders the comment status checkbox
     *
     * @since 1.4.1
     */
    public function render_comment_status()
    {
        $comment_status = $this->get('comment_status');
        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            WPRSS_FTP_Utils::multiboolean($comment_status),
            [
                'id'    => 'ftp-comment-status',
                'name'  => self::OPTIONS_NAME . '[comment_status]',
                'value' => 'true',
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'comment_status');
    }

    /**
     * @since 3.11
     */
    public function render_link_posts_to_original()
    {
        $value = $this->get('link_posts_to_original');
        $value = WPRSS_FTP_Utils::multiboolean($value);

        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            $value,
            [
                'id'    => 'ftp-link-posts-to-original',
                'name'  => self::OPTIONS_NAME . '[link_posts_to_original]',
                'value' => 'true',
            ]
        );

        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'link_posts_to_original');
    }

    /**
     * Renders the source_link checkbox
     *
     * @since 1.0
     */
    public function render_source_link()
    {
        $source_link = $this->get('source_link');
        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            WPRSS_FTP_Utils::multiboolean($source_link),
            [
                'id'    => 'ftp-source-link',
                'name'  => self::OPTIONS_NAME . '[source_link]',
                'value' => 'true',
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'source_link');
    }

    /**
     * Renders the source_link_singular checkbox
     *
     * @since 3.3.2
     */
    public function render_source_link_singular()
    {
        $source_link = $this->get('source_link_singular');
        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            WPRSS_FTP_Utils::multiboolean($source_link),
            [
                'id'    => 'ftp-source-link-singular',
                'name'  => self::OPTIONS_NAME . '[source_link_singular]',
                'value' => 'true',
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'source_link_singular');
    }

    /**
     * Renders the source_link_text text field
     *
     * @since 1.0
     */
    public function render_source_link_text()
    {
        $source_link_text = $this->get('source_link_text');
        printf(
            '<input type="text" name="%s[source_link_text]" id="ftp-source-link-text" placeholder="%s" value="%s" />',
            self::OPTIONS_NAME,
            esc_attr(__("Source link text", WPRSS_TEXT_DOMAIN)),
            $source_link_text
        );

        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'source_link_text');
    }

    /**
     * Renders the render_source_link_position text field
     *
     * @since 3.12
     */
    public function render_source_link_position()
    {
        $source_link_position = $this->get('source_link_position');
        $options = [
            'before' => 'Before the post content',
            'after'  => 'After the post content',
        ];
        $select = [
            'id'       => 'ftp-source-link-position',
            'name'     => self::OPTIONS_NAME . '[source_link_position]',
            'selected' => $source_link_position,
        ];

        echo WPRSS_FTP_Utils::array_to_select($options, $select);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'source_link_position');
    }

    /**
     * Renders the add_content_in_feed text field
     *
     * @since 3.12
     */
    public function render_add_content_in_feed()
    {
        $add_content_in_feed = WPRSS_FTP_Utils::multiboolean($this->get('add_content_in_feed'));
        $checkbox = [
            'id'    => 'ftp-source-link-singular',
            'name'  => self::OPTIONS_NAME . '[add_content_in_feed]',
            'value' => 'true',
        ];

        echo WPRSS_FTP_Utils::boolean_to_checkbox($add_content_in_feed, $checkbox);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'add_content_in_feed');
    }

    /**
     * Renders the word_limit number roller field
     *
     * @since 3.3
     */
    public function render_word_limit()
    {
        $word_limit = $this->get('word_limit');
        echo '<input type="number" class="wprss-number-roller" name="' . self::OPTIONS_NAME . '[word_limit]" id="ftp-word-limit" placeholder="Disabled" value="' . $word_limit . '" min="0" />';
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'word_limit_general');
    }

    /**
     * Renders the trimming_type number roller field
     *
     * @since 3.3
     */
    public function render_trimming_type()
    {
        $trimming_type = $this->get('trimming_type');
        $options = [
            'db'      => __('Trim the content', WPRSS_TEXT_DOMAIN),
            'excerpt' => __('Generate an excerpt', WPRSS_TEXT_DOMAIN),
        ];
        echo WPRSS_FTP_Utils::array_to_select($options, [
            'id'       => 'ftp-trimming-type',
            'name'     => self::OPTIONS_NAME . '[trimming_type]',
            'selected' => $trimming_type,
        ]);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'trimming_type_general');
    }

    /**
     * Renders the canonical_link checkbox
     *
     * @since 1.8
     */
    public function render_canonical_link()
    {
        $canonical_link = $this->get('canonical_link');
        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            WPRSS_FTP_Utils::multiboolean($canonical_link),
            [
                'id'    => 'ftp-canonical-link',
                'name'  => self::OPTIONS_NAME . '[canonical_link]',
                'value' => 'true',
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'canonical_link'); ?>
        <label class="description" for="ftp-canonical-link">
            <a href="https://webdesign.about.com/od/seo/a/rel-canonical.htm" target="_blank">
                <?php _e('Learn more about canonical pages.', WPRSS_TEXT_DOMAIN); ?>
            </a>
        </label>
        <?php
    }


    #== Authors Section ========================

    /**
     * Renders the author settings
     *
     * @since 1.9.3
     */
    public function render_def_author()
    {
        $this->render_author_options();
    }

    #== Taxonomies Section ========================

    /**
     * Renders the taxonomies dropdown
     *
     * @since 1.0
     */
    public function render_post_taxonomy()
    {
        $settings = $this->get('taxonomies');
        // Check if has old taxonomies settings
        if ($settings === '') {
            $settings = self::convert_post_taxonomy_settings();
        }
        $post_type = $this->get('post_type');

        echo '</td></tr></table>';
        ob_start();
        ?>
        <div id="wprss-ftp-taxonomy-table-container" style="position: relative; max-width: 800px; display: block;">
            <table id="wprss-ftp-taxonomy-table" class="form-table wprss-form-table">
                <tbody>
                    <?php echo wprss_ftp_taxonomy_sections($post_type, $settings); ?>
                    <tr id="wprss-ftp-taxonomies-add-section" class="wprss-tr-hr">
                        <th colspan="1">
                            <button type="button" class="button-secondary" id="ftp-add-taxonomy">
                                <i class="fa fa-fw fa-plus"></i> Add New
                            </button>
                            <?php echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'taxonomies'); ?>
                        </th>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php echo ob_get_clean();
        echo '<table style="display: none"><tr><td>';
    }

    #== Images Section ========================

    /**
     * Renders the dropdown for using featured images
     *
     * @since 1.0
     */
    public function render_use_featured_image()
    {
        $use_featured_image = $this->get('use_featured_image');
        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            WPRSS_FTP_Utils::multiboolean($use_featured_image),
            [
                'id'    => 'ftp-use-featured-image',
                'name'  => self::OPTIONS_NAME . '[use_featured_image]',
                'value' => 'true',
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'use_featured_image');
    }

    /**
     *
     *
     * @since 1.0
     */
    public function render_featured_image()
    {
        $featured_image = $this->get('featured_image');
        $options = WPRSS_FTP_Meta::get_instance()->get_meta_fields('images');
        $options = $options['featured_image']['options'];
        echo WPRSS_FTP_Utils::array_to_select($options,
            [
                'id'       => 'ftp-featured-image',
                'name'     => self::OPTIONS_NAME . '[featured_image]',
                'selected' => $featured_image,
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'featured_image');
    }

    /**
     * Renders the dropdown for using featured images
     *
     * @since 1.0
     */
    public function render_fallback_to_feed_image()
    {
        $fallback_to_featured_image = $this->get('fallback_to_feed_image');
        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            WPRSS_FTP_Utils::multiboolean($fallback_to_featured_image),
            [
                'id'    => 'ftp-fallback-to-feed-image',
                'name'  => self::OPTIONS_NAME . '[fallback_to_feed_image]',
                'value' => 'true',
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'fallback_to_feed_image');
    }

    /**
     * Renders the two dropdowns for the minimum dimensions for the images to import
     *
     * @since 1.0
     */
    public function render_image_minimum_size()
    {
        $min_width = $this->get('image_min_width');
        $min_height = $this->get('image_min_height');

        $number_roller = function ($id, $name, $value) {
            printf(
                '<input class="wprss-number-roller" type="number" id="%s" name="%s" value="%s" min="0" placeholder="Ignore" />',
                esc_attr($id),
                esc_attr(self::OPTIONS_NAME . '[' . $name . ']'),
                esc_attr($value)
            );
        }

        ?>
        <p>
            <?php $number_roller('ftp-min-width', 'image_min_width', $min_width) ?>

            <span class="dimension-divider"><i class="fa fa-times"></i></span>

            <?php $number_roller('ftp-min-height', 'image_min_height', $min_height) ?>

            <?php echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'image_min_dimensions'); ?>
        </p>
        <?php
    }

    /**
     * Renders the checkbox for the option to save images locally
     *
     * @since 1.3
     */
    public function render_save_images_locally()
    {
        $save_images_locally = $this->get('save_images_locally');
        echo WPRSS_FTP_Utils::boolean_to_checkbox(
            WPRSS_FTP_Utils::multiboolean($save_images_locally),
            [
                'id'    => 'ftp-save-images-locally',
                'name'  => self::OPTIONS_NAME . '[save_images_locally]',
                'value' => 'true',
            ]
        );
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'save_images_locally');
    }

    /**
     * Renders the dropdown to choose the full text RSS service type
     *
     * @since 2.7
     */
    public function render_full_text_rss_service()
    {
        // Get the saved option value, and the dropdown options
        $selected = $this->get('full_text_rss_service');
        $options = self::get_full_text_rss_service_options();
        $selectable = self::get_full_text_rss_selectable_services();
        $dropdownArgs = [
            'id'         => 'ftp-full-text-rss-service',
            'name'       => self::OPTIONS_NAME . '[full_text_rss_service]',
            'selected'   => $selected,
            'selectable' => $selectable,
        ];
        // Render the dropdown
        echo WPRSS_FTP_Utils::array_to_select($options, $dropdownArgs);
        echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'full_text_rss_service');
        do_action('wprss_ftp_after_full_text_rss_service_options', $options, $dropdownArgs);
    }

    /**
     * Renders the custom namespaces list option
     *
     * @since 2.8
     */
    public function render_custom_namespaces()
    {
        // Get the option value
        $namespaces = $this->get('user_feed_namespaces');

        // Parse with default values
        $namespaces = wp_parse_args(
            $namespaces,
            [
                'names' => [],
                'urls'  => [],
            ]
        );

        // PRINT SAVED NAMESPACES
        $remove_btn = '<button type="button" class="button-secondary wprss-ftp-namespace-remove"><i class="fa fa-trash-o"></i></button>';

        for ($i = 0; $i < count($namespaces['names']); $i++) {
            $name = $namespaces['names'][$i];
            $url = $namespaces['urls'][$i];

            echo '<div class="wprss-ftp-namespace-section">';
            echo '<input type="text" name="' . self::OPTIONS_NAME . '[user_feed_namespaces][names][]" value="' . esc_attr($name) . '" placeholder="' . esc_attr__('Name',
                    WPRSS_TEXT_DOMAIN) . '" />';
            echo '<input type="text" name="' . self::OPTIONS_NAME . '[user_feed_namespaces][urls][]" value="' . esc_attr($url) . '" class="wprss-ftp-namespace-url" placeholder="' . esc_attr__('URL',
                    WPRSS_TEXT_DOMAIN) . '" />';
            echo $remove_btn;
            echo '</div>';
        }
        ?>

        <span id="wprss-ftp-namespaces-marker"></span>

        <button type="button" id="wprss-ftp-add-namespace" class="button-secondary">
            <?php _e('Add another namespace', WPRSS_TEXT_DOMAIN); ?>
        </button>

        <?php // Print the field template and the remove btn as a script variables
        $field_template = '<input type="text" name="' . self::OPTIONS_NAME . '[user_feed_namespaces]" value="" placeholder="" />';
        ?>

        <?php echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'user_namespaces'); ?>

        <script type="text/javascript">
          let wprss_namespace_input_template = "<?php echo addslashes($field_template); ?>"
          let wprss_namespace_remove_btn = "<?php echo addslashes($remove_btn); ?>"
        </script>

        <?php
    }

    /**
     * Renders the license section text.
     *
     * @since 1.0
     */
    public function license_section_callback()
    {
        // Do nothing
    }

    /**
     * Renders the license text field.
     *
     * @since 1.0
     */
    public function license_callback()
    {
        $license_keys = get_option('wprss_settings_license_keys');
        $ftp_license_key = (isset($license_keys['ftp_license_key'])) ? $license_keys['ftp_license_key'] : '';
        echo "<input id='wprss-ftp-license-key' name='wprss_settings_license_keys[ftp_license_key]' type='text' value='" . esc_attr($ftp_license_key) . "' />";
        echo "<label class='description' for='wprss-ftp-license-key'>" . __('Enter your license key',
                WPRSS_TEXT_DOMAIN) . '</label>';
    }

    /**
     * Renders the activate/deactivate license button.
     *
     * @since 1.0
     */
    public function license_activation_callback()
    {
        $status = WPRSS_FTP::get_instance()->get_license_status();
        if ($status === 'site_inactive') {
            $status = 'inactive';
        }

        $valid = $status == 'valid';
        $btn_text = $valid ? 'Deactivate license' : 'Activate license';
        $btn_name = 'wprss_ftp_license_' . ($valid ? 'deactivate' : 'activate');
        wp_nonce_field('wprss_ftp_license_nonce', 'wprss_ftp_license_nonce');

        printf(
            '<input type="submit" class="button-secondary" name="%s" value="%s" />',
            esc_attr($btn_name),
            esc_attr(__($btn_text, WPRSS_TEXT_DOMAIN))
        );

        switch ($status) {
            case 'valid':
            {
                $status_text = _x('Valid', 'Refers to license status', WPRSS_TEXT_DOMAIN);
                $icon_color = 'green';
                $icon_name = 'fa-check';
                break;
            }
            case 'invalid':
            {
                $status_text = _x('Invalid', 'Refers to license status', WPRSS_TEXT_DOMAIN);
                $icon_color = '#b71919';
                $icon_name = 'fa-times';
                break;
            }
            default:
            case 'inactive':
            {
                $status_text = _x('Inactive', 'Refers to license status', WPRSS_TEXT_DOMAIN);
                $icon_color = '#d19e5b';
                $icon_name = 'fa-warning';
                break;
            }
        }
        ?>

        <span id="wprss-ftp-license-status-text">
            <strong>
                <?php echo _x('Status:', 'Refers to license status', WPRSS_TEXT_DOMAIN) ?>
                <span style="color: <?php echo $icon_color ?>">
                    <?php echo $status_text ?> <i class="fa fa-<?php echo $icon_name ?>"></i>
                </span>
            </strong>
        </span>

        <style>
          #wprss-ftp-license-status-text {
            margin-left: 8px;
            line-height: 27px;
            vertical-align: middle;
          }
        </style>

        <?php
    }

#== SETTINGS VALIDATOR =================================================================================

    public function validate_settings($input)
    {
        /**
         * @todo Santize options
         */
        $output = $input;

        // Check if the core settings are included in the POST data
        if (isset($_POST['wprss_settings_general']) && is_array($_POST['wprss_settings_general'])) {
            // get the option in the database
            $db_option = get_option('wprss_settings_general', []);
            // update each sub-option
            foreach ($_POST['wprss_settings_general'] as $key => $value) {
                $db_option[$key] = $value;
            }
            // Update the option
            update_option('wprss_settings_general', $db_option);
        }

        // Check for missing values
        foreach ($this->get_defaults() as $key => $def_value) {
            if (!array_key_exists($key, $input)) {
                $output[$key] = 'false';
            }
        }

        // Taxonomies saving - Since the form names use meta field names
        $prefix = WPRSS_FTP_Meta::META_PREFIX;
        if (!empty($_POST[$prefix . 'post_taxonomy'])) {
            $taxonomies = $_POST[$prefix . 'post_taxonomy'];
            $n = count($taxonomies);

            $terms = isset($_POST[$prefix . 'post_terms'])
                ? $_POST[$prefix . 'post_terms']
                : array_fill(0, $n, []);

            $autos = isset($_POST[$prefix . 'auto_terms'])
                ? $_POST[$prefix . 'auto_terms']
                : array_fill(0, $n, "false");

            $whole_words = isset($_POST[$prefix . 'whole_words'])
                ? $_POST[$prefix . 'whole_words']
                : array_fill(0, $n, "false");

            $subjects = isset($_POST[$prefix . 'filter_subject'])
                ? $_POST[$prefix . 'filter_subject']
                : array_fill(0, $n + 1, "");

            $keywords = isset($_POST[$prefix . 'filter_keywords'])
                ? $_POST[$prefix . 'filter_keywords']
                : array_fill(0, $n + 1, "");

            $compare_methods = isset($_POST[$prefix . 'post_taxonomy_compare_method'])
                ? $_POST[$prefix . 'post_taxonomy_compare_method']
                : array_fill(0, $n + 1, WPRSS_FTP_Meta::get_instance()->getDefaultTaxonomyCompareMethod());

            $output['taxonomies'] = [];
            for ($i = 0; $i < count($taxonomies); $i++) {
                $output['taxonomies'][$i] = [];
                $output['taxonomies'][$i]['taxonomy'] = $taxonomies[$i];
                $output['taxonomies'][$i]['terms'] = $terms[$i];
                $output['taxonomies'][$i]['auto'] = $autos[$i];
                $output['taxonomies'][$i]['whole_words'] = $whole_words[$i];
                $output['taxonomies'][$i]['filter_subject'] = $subjects[$i];
                $output['taxonomies'][$i]['filter_keywords'] = $keywords[$i];
                $output['taxonomies'][$i]['post_taxonomy_compare_method'] = $compare_methods[$i];
            }
        }

        return $output;
    }


#== CUSTOM RENDERERS ======================================================================================

    /**
     * Renders the author settings
     *
     * @since 1.9.3
     */
    public function render_author_options($post_id = null, $meta_row_title = '', $meta_label_for = '')
    {
        // Get the options
        $options = WPRSS_FTP_Settings::get_instance()->get_computed_options($post_id);
        $def_author = ($post_id !== null) ? $options['def_author'] : $this->get('def_author');
        $author_fallback_method = ($post_id !== null) ? $options['author_fallback_method']
            : $this->get('author_fallback_method');
        $author_fallback_method = (strtolower($author_fallback_method) === 'use_existing') ? 'existing'
            : $author_fallback_method;
        $fallback_author = ($post_id !== null) ? $options['fallback_author'] : $this->get('fallback_author');
        $no_author_found = ($post_id !== null) ? $options['no_author_found'] : $this->get('no_author_found');

        // Set the HTML tag ids
        $ids = [
            'def_author'             => 'ftp-def-author',
            'author_fallback_method' => 'ftp-author-fallback-method',
            'fallback_author'        => 'ftp-fallback-author',
            'no_author_found'        => 'ftp-no-author-skip',
        ];
        // If in meta, copy the keys into the values
        if ($post_id !== null) {
            foreach ($ids as $field => $id) {
                $ids[$field] = $field;
            }
        }
        // Set the HTML tag names
        $names = [
            'def_author'             => 'def_author',
            'author_fallback_method' => 'author_fallback_method',
            'fallback_author'        => 'fallback_author',
            'no_author_found'        => 'no_author_found',
        ];
        // Set the names appropriately according to the page, meta or settings
        foreach ($names as $field => $name) {
            if ($post_id !== null) {
                $names[$field] = WPRSS_FTP_Meta::META_PREFIX . $name;
            } else {
                $names[$field] = self::OPTIONS_NAME . "[$name]";
            }
        }

        // If in meta, print the table row
        if ($post_id !== null) : ?>
            <tr>
            <th>
                <label for="<?php echo $meta_label_for; ?>">
                    <?php echo $meta_row_title; ?>
                </label>
            </th>
            <td>
        <?php endif; ?>

        <!-- Author to use -->
        <span id="wprss-ftp-authors-options">
            <?php
            $userIds = WPRSS_FTP_Admin_User_Ajax::get_instance()->is_over_threshold()
                ? [$def_author, get_current_user_id()]
                : false;
            $users = WPRSS_FTP_Meta::get_users_array($userIds);
            ?>
            <?php echo WPRSS_FTP_Utils::array_to_select($users, [
                'id'       => $ids['def_author'],
                'name'     => $names['def_author'],
                'selected' => $def_author,
            ]);
            ?>
            <script type="text/javascript">
                top.wprss.f2p.userAjax.addElement('#<?php echo $ids['def_author'] ?>')
            </script>
            <?php
            echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'post_author'); ?>
        </span>

        <!-- Separator -->
        <?php if ($post_id !== null) : ?>
        </td></tr>
        <tr class="wprss-tr-hr wprss-ftp-authors-hide-if-using-existing">
        <th>
        </th>
        <td>
    <?php endif; ?>

        <!-- Section that hides when using an existing user -->
        <span class="wprss-ftp-authors-hide-if-using-existing">

            <!-- Radio group if author has no user -->
            <span class="ftp-author-using-in-feed">
                <label for="<?php echo $ids['author_fallback_method']; ?>">
                    <?php _e('If the article author is not an existing WordPress user', WPRSS_TEXT_DOMAIN); ?>:
                </label>
                <br />
                <?php
                echo implode('', WPRSS_FTP_Utils::array_to_radio_buttons(
                    [
                        'existing' => __('Use the fallback user', WPRSS_TEXT_DOMAIN),
                        'create'   => __('Create a user for the author', WPRSS_TEXT_DOMAIN),
                    ],
                    [
                        'id'      => $ids['author_fallback_method'],
                        'name'    => $names['author_fallback_method'],
                        'checked' => $author_fallback_method,
                    ]
                ));
                ?>
            </span>

            <!-- Radio group if author not found in feed -->
            <span class="ftp-author-using-in-feed">
                <label for="<?php echo $ids['no_author_found']; ?>">
                    <?php _e('If the author is missing from the feed', WPRSS_TEXT_DOMAIN); ?>
                </label>
                <br />
                <?php
                echo implode(WPRSS_FTP_Utils::array_to_radio_buttons(
                    [
                        'fallback' => __('Use the fallback user', WPRSS_TEXT_DOMAIN),
                        'skip'     => __('Do not import the post', WPRSS_TEXT_DOMAIN),
                    ],
                    [
                        'id'      => $ids['no_author_found'],
                        'name'    => $names['no_author_found'],
                        'checked' => $no_author_found,
                    ]
                ));
                ?>
            </span>
        </span>


        <?php if ($post_id !== null) : ?>
        </td></tr>
        <tr class="wprss-tr-hr wprss-ftp-authors-hide-if-using-existing">
        <th>
            <label for="<?php echo $ids['fallback_author']; ?>">
                <?php _e('Fallback user', WPRSS_TEXT_DOMAIN); ?>
            </label>
        </th>
        <td>
    <?php endif; ?>

        <!-- Section that hides when using an existing user -->
        <span class="wprss-ftp-authors-hide-if-using-existing">
            <?php if ($post_id === null) : ?>
                <label for="<?php echo $ids['fallback_author']; ?>">
                <?php _e('Fallback user:', WPRSS_TEXT_DOMAIN); ?>
            </label>
            <?php endif; ?>
            <?php
            $userIds = WPRSS_FTP_Admin_User_Ajax::get_instance()->is_over_threshold()
                ? array_merge($userIds, [$fallback_author])
                : false;
            $fallback_users = WPRSS_FTP_Meta::get_users_array($userIds, true, true) ?>
            <?php
            echo WPRSS_FTP_Utils::array_to_select($fallback_users, [
                'id'       => $ids['fallback_author'],
                'name'     => $names['fallback_author'],
                'selected' => $fallback_author,
            ]);
            ?>
            <!--suppress BadExpressionStatementJS, CommaExpressionJS -->
            <script type="text/javascript">
                top.wprss.f2p.userAjax.addElement(
                  '#<?php echo $ids['fallback_author'] ?>',
                  {
                <?php echo WPRSS_FTP_Admin_User_Ajax::REQUEST_VAR_EXISTING_USERS_ONLY ?>:
                true,
                <?php echo WPRSS_FTP_Admin_User_Ajax::REQUEST_VAR_LOGIN_NAMES ?>:
                true,
                }
                )
            </script>
            <?php echo WPRSS_Help::get_instance()->do_tooltip(WPRSS_FTP_HELP_PREFIX . 'fallback_author'); ?>
        </span>

        <?php // Add scripts
        ?>

        <script type="text/javascript">
          (function ($) {
            $(document).ready(function () {

              // Set a pointer to the dropdowns
              let dropdown1 = $('#<?php echo $ids['def_author']; ?>')

              // Create the function that shows/hides the second section
              let authorsSection2UI = function () {
                // Show second section only if the option to use the author in the feed is chosen
                $('.wprss-ftp-authors-hide-if-using-existing').toggle(dropdown1.val() === '.')
              }

              // Set the on change handlers
              dropdown1.change(authorsSection2UI)

              // Run the function at least once
              authorsSection2UI()

            })
          })(jQuery)
        </script>
        <?php // End of scripts

        // If in meta, close the table row
        if ($post_id !== null) {
            ?></td></tr><?php
        }
    }


#== PAGE RENDERER ======================================================================================

    /**
     * Add settings fields and sections
     *
     * @since 1.0
     */
    public function render_settings_page($active_tab)
    {
        if ($active_tab === self::TAB_SLUG) {
            # Render all sections for this page
            settings_fields('wprss_settings_ftp');
            do_settings_sections('wprss_settings_ftp');
        }
    }


#== ADD AGGREGATOR TAB =================================================================================

    /**
     * Add a settings tabs for the Feed-to-Post add-on on the Settings page
     *
     * @since 1.0
     */
    public function add_tab($args)
    {
        $args['ftp'] = [
            'label' => 'Feed to Post',
            'slug'  => self::TAB_SLUG,
        ];
        return $args;
    }


#== MISC ===============================================================================================

    /**
     * Converts the old taxonomy meta fields into the new format.
     * Does NOT save into database.
     *
     * @since 3.1
     * @return array The new settings fields
     */
    public static function convert_post_taxonomy_settings()
    {
        // Prepare the old fields
        $old_fields = [
            'post_taxonomy',
            'post_terms',
            'post_auto_tax_terms',
            'post_tags',
        ];
        // Prepare the new fields array
        $settings = [];
        // Generate the new fields
        foreach ($old_fields as $old_field) {
            $settings[$old_field] = self::get_instance()->get($old_field);
        }
        // Return the new fields
        return WPRSS_FTP_Meta::convert_taxonomy_option($settings);
    }

    /**
     * Returns the registered post types.
     *
     * @since 2.9.5
     */
    public static function get_post_types()
    {
        // Get all post types, as objects
        $post_types = get_post_types([], 'objects');

        unset($post_types['attachment']);
        unset($post_types['revision']);
        unset($post_types['nav_menu_item']);
        unset($post_types['custom_css']);
        unset($post_types['customize_changeset']);
        unset($post_types['oembed_cache']);
        unset($post_types['user_request']);
        unset($post_types['wp_block']);
        unset($post_types['wprss_blacklist']);
        unset($post_types['wprss_feed']);
        unset($post_types['wprss_feed_template']);

        // Return the list, mapping the post type objects to their singular name
        return array_map([__CLASS__, 'post_type_singular_name'], $post_types);
    }

    /**
     * Returns the singular name for the given post type object.
     * Used as a callback for array_map calls.
     *
     * @since 2.9.5
     */
    public static function post_type_singular_name($post_type)
    {
        return $post_type->labels->singular_name;
    }

    public static function get_post_formats()
    {
        return [
            'standard' => __('Standard'),
            'aside'    => __('Aside'),
            'chat'     => __('Chat'),
            'link'     => __('Link'),
            'quote'    => __('Quote'),
            'status'   => __('Status'),
            'audio'    => __('Audio'),
            'image'    => __('Image'),
            'video'    => __('Video'),
            'gallery'  => __('Gallery'),
        ];
    }

    public static function get_post_statuses($args = [])
    {
        $args = array_merge([
            'show_in_admin_status_list' => true,
        ], $args);

        $stati = [];
        foreach (get_post_stati($args, 'objects') as $_code => $_status) {
            /* @var $status stdClass */
            $stati[$_code] = $_status->label;
        }

        return apply_filters('wprss_ftp_post_stati', $stati);
    }

    /* The following functions are used as filters for array_map function calls, to return specific user data. */
    private static function wprss_ftp_user_id($user)
    {
        return $user->ID;
    }

    private static function wprss_ftp_user_login($user)
    {
        return $user->user_login;
    }

    private static function wprss_ftp_term_slug($term)
    {
        return $term->slug;
    }

    private static function wprss_ftp_term_name($term)
    {
        return $term->name;
    }

    /**
     * Returns an array of users on the site.
     *
     * Rewritten as of 2.0, due to various bugs.
     *
     * @since 2.0
     * @param $assoc    boolean        If true, an associative array of user ids pointing to user logins is
     *                                returned. If false, a regular array of user logins is returned.
     */
    public static function get_users($assoc = true, $onlyTheseIds = false)
    {
        // Get all users
        if ($onlyTheseIds !== false) {
            $onlyTheseIds = (array) $onlyTheseIds;
            foreach ($onlyTheseIds as $_idx => $_id) {
                if (is_numeric($_id)) {
                    continue;
                }
                $user = get_user_by('login', $_id);
                $onlyTheseIds[$_idx] = $user ? $user->ID : null;
            }

            $user_query = new WP_User_Query(['include' => $onlyTheseIds]);
            $users = $user_query->get_results();
        } else {
            $users = get_users();
        }

        if (count($users) === 0) {
            return [];
        }

        // Get the user logins and ids
        $user_logins = array_map(['WPRSS_FTP_Settings', 'wprss_ftp_user_login'], $users);
        $user_ids = array_map(['WPRSS_FTP_Settings', 'wprss_ftp_user_id'], $users);

        // If the assoc param is true, return an associative array of user keys pointing to their logins.
        // Otherwise, return just an array with the user logins.
        return ($assoc === true) ? array_combine($user_ids, $user_logins) : $user_logins;
    }

    public static function get_term_names($taxonomy, $args = [], $assoc = true)
    {
        $args['fields'] = 'all';
        $term_objs = get_terms($taxonomy, $args);
        if (is_wp_error($term_objs)) {
            return null;
        }

        $term_slugs = array_map(['WPRSS_FTP_Settings', 'wprss_ftp_term_slug'], $term_objs);
        $term_names = array_map(['WPRSS_FTP_Settings', 'wprss_ftp_term_name'], $term_objs);

        if ($assoc === true) {
            if (count($term_names) > 0) {
                $term_names = array_combine($term_slugs, $term_names);
            } else {
                $term_names = [];
            }
        }
        return $term_names;
    }

    public static function get_post_date_options()
    {
        return [
            'original' => __('Original post date', WPRSS_TEXT_DOMAIN),
            'imported' => __('Feed import date', WPRSS_TEXT_DOMAIN),
        ];
    }

    /**
     * Returns the options for the full_text_rss_service option
     *
     * @since 2.7
     */
    public static function get_full_text_rss_service_options()
    {
        return apply_filters(
            'wprss_ftp_full_text_rss_service_options',
            [
                'free' => __('Free services', WPRSS_TEXT_DOMAIN),
            ]
        );
    }

    /**
     * Returns an array of the full text rss service option IDs as keys,
     * and a TRUE/FALSE flag signifying whether or not they are selectable.
     *
     * @since 3.2.4
     * @return array
     */
    public static function get_full_text_rss_selectable_services()
    {
        $services = self::get_full_text_rss_service_options();
        $services = array_keys($services);
        $selectable = array_fill(0, count($services), true);
        $final = array_combine($services, $selectable);
        return apply_filters('wprss_ftp_full_text_rss_selectable_services', $final);
    }

    /**
     * Returns the array of default namespaces
     *
     * @since 2.8
     */
    public static function get_default_namespaces()
    {
        return apply_filters(
            'wprss_ftp_default_namespaces',
            [
                __('No namespace', WPRSS_TEXT_DOMAIN) => '',
            ]
        );
    }

    /**
     * Returns the array of namespaces available.
     *
     * @since 2.8
     */
    public static function get_namespaces()
    {
        // The default namespaces
        $def_namespaces = self::get_default_namespaces();

        // Change the array into the same format as the user saved namespaces
        $def_namespaces = [
            'names' => array_keys($def_namespaces),
            'urls'  => array_values($def_namespaces),
        ];

        // Get the namespaces added by the user
        $user_namespaces = self::get_instance()->get('user_feed_namespaces');
        if (!is_array($user_namespaces) || count($user_namespaces) === 0) {
            $user_namespaces = self::get_instance()->get_default('user_feed_namespaces');
        }

        // Return both as 1 array
        return [
            'names' => array_merge($def_namespaces['names'], $user_namespaces['names']),
            'urls'  => array_merge($def_namespaces['urls'], $user_namespaces['urls']),
        ];
    }

    /**
     * Gets the Namespace URL for the given Namespae Name
     *
     * @since 2.8
     */
    public static function get_namespace_url($namespace)
    {
        // Get the namespaces array setting
        $namespaces = self::get_namespaces();
        $namespaces['names'] = array_map('strtolower', $namespaces['names']);

        // Search for the index of the namespace name given in the 'names' subarray
        $i = array_search(strtolower($namespace), $namespaces['names']);
        // Return null if the namespace was not found
        if ($i === false) {
            return null;
        }

        // Use the index to return the URL from the 'urls' subarray
        return (!isset($namespaces['urls'][$i])) ? null : $namespaces['urls'][$i];
    }

    /**
     * Makes sure that asking for a setting never returns "feeds_api".
     *
     * Most probably used for the `full_text_rss_service` setting.
     *
     * @since 3.7.4
     *
     * @param mixed $value The current value.
     * @param string $name The name of the setting being retrieved.
     * @return mixed The new value.
     */
    public function feedsapi_setting_fallback($value, $name)
    {
        if ($value === 'feeds_api') {
            $value = WPRSS_FTP_Settings::get_instance()->get_default($name);
        }

        return $value;
    }
} // End of Settings Class
