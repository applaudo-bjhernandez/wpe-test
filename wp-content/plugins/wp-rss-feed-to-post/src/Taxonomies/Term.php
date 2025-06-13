<?php

namespace RebelCode\Wpra\FeedToPost\Taxonomies;

use WP_Term;
use WPRSS_FTP_Meta;

class Term
{
    /** @var string */
    public $taxonomy;

    /** @var string */
    public $slug;

    /** @var Term|null */
    public $parent;

    /**
     * Constructor.
     *
     * @param string $taxonomy
     * @param string $slug
     * @param Term|null $parent
     */
    public function __construct($taxonomy, $slug, Term $parent = null)
    {
        $this->taxonomy = $taxonomy;
        $this->slug = $slug;
        $this->parent = $parent;
    }

    /**
     * @return WP_Term|false
     */
    public function getOrCreate()
    {
        $info = term_exists($this->slug, $this->taxonomy);

        if (empty($info)) {
            $info = wp_insert_term($this->slug, $this->taxonomy, [
                'parent' => $this->parent
                    ? $this->parent->getOrCreate()->term_id
                    : 0,
            ]);
            $created = true;
        } else {
            $created = false;
        }

        if (is_wp_error($info)) {
            wpra_get_logger()->error('Failed to create term $0: $1', [$this->slug, $info->get_error_message()]);
            return false;
        }

        $term = get_term_by('id', $info['term_id'], $this->taxonomy);

        // WPML compatibility
        // Translate the term according to the `post_language` meta option
        global $sitepress;
        if (!$created && is_object($sitepress) && defined('ICL_LANGUAGE_CODE')) {
            // Get the post language from the feed source's meta data
            global $wpraF2pConversionCtx;
            $feedId = $wpraF2pConversionCtx['feed'];
            $postLang = WPRSS_FTP_Meta::get_instance()->get($feedId, 'post_language');

            // Translate the term using WPML
            $translatedId = $sitepress->get_object_id($term->term_id, $this->taxonomy, false, $postLang);

            if ($translatedId) {
                // Remove WPML term ID translation
                remove_filter('get_term', [$sitepress, 'get_term_adjust_id'], 1, 1);

                // Get the term for the given ID without WPML's ID translation
                $translatedTerm = get_term_by('id', $translatedId, $this->taxonomy);
                $term = $translatedTerm ? : $term;

                // Restore WPML term ID translation
                add_filter('get_term', [$sitepress, 'get_term_adjust_id'], 1, 1);
            }
        }

        return $term;
    }

    public static function fromArray($taxonomy, array $data)
    {
        $slug = $data['name'];

        $parent = isset($data['args']['parent']) ? $data['args']['parent'] : null;
        $parent = $parent ? new self($taxonomy, $parent) : null;

        return new self($taxonomy, $slug, $parent);
    }

    public static function fromSlugs($taxonomy, array $slugs)
    {
        $result = [];
        foreach ($slugs as $slug) {
            $result[] = new self($taxonomy, $slug);
        }

        return $result;
    }
}
