<?php

namespace RebelCode\Wpra\FeedToPost\Taxonomies;

use SimplePie_Item;
use WP_Post;
use WPRSS_FTP_Meta;
use WPRSS_FTP_Settings;

/**
 * Retrieves the rules from a feed source's meta data.
 *
 * @param int|string $feedId The ID of the feed source.
 *
 * @return Rule[]
 */
function getMetaRules($feedId)
{
    $meta = WPRSS_FTP_Meta::get_instance()->get($feedId, 'taxonomies');

    // If the source has the old meta saved, convert it into the new meta
    $rules = ($meta === '')
        ? WPRSS_FTP_Meta::convert_post_taxonomy_meta($feedId)
        : $meta;

    return array_map(['RebelCode\Wpra\FeedToPost\Taxonomies\Rule', 'createFromArray'], $rules);
}

/**
 * Retrieves the global rules from the settings.
 *
 * @return Rule[]
 */
function getGlobalRules()
{
    // Get the taxonomies from the settings
    $settings = WPRSS_FTP_Settings::get_instance();
    $value = $settings->get('taxonomies');

    // If the settings are saved in the old format, convert it into the new format
    $rules = ($value === '')
        ? WPRSS_FTP_Settings::convert_post_taxonomy_settings()
        : $value;

    if (!is_array($rules)) {
        $rules = [];
    }

    return array_map(['RebelCode\Wpra\FeedToPost\Taxonomies\Rule', 'createFromArray'], $rules);
}

/**
 * Retrieves the rules from a feed source's meta data merged with the global rules.
 *
 * @param int|string $feedId The ID of the feed source.
 *
 * @return Rule[]
 */
function getMergedRules($feedId)
{
    $meta = getMetaRules($feedId);
    $settings = getGlobalRules();

    return array_merge($settings, $meta);
}

/**
 * Retrieves the rules that apply for a particular feed item and post type.
 *
 * This method will eliminate any rules whose conditions are not satisfied.
 *
 * @param int|string $feedId The ID of the feed source.
 * @param string $postType The post type.
 *
 * @return Rule[]
 */
function getApplicableRules(SimplePie_Item $item, $postType, $feedId)
{
    $mergedRules = getMergedRules($feedId);

    $filtered = array_filter($mergedRules, function (Rule $rule) use ($item, $postType) {
        // Only keep rules:
        // - whose taxonomies apply to the post type
        // - that contain terms or will auto-create terms
        // - whose condition is satisfied
        return is_object_in_taxonomy($postType, $rule->taxonomy)
               && (!empty($rule->terms) || $rule->autoCreate)
               && $rule->condition->isSatisfiedByItem($item);
    });

    $numIgnored = count($mergedRules) - count($filtered);
    wpra_get_logger($feedId)->debug("Got {0} taxonomy rules, {1} of which don't apply", [
        count($mergedRules),
        $numIgnored,
    ]);

    return $filtered;
}

/**
 * Calculates what terms should be applied to a post, without actually applying them to the post.
 *
 * @param WP_Post $post The WordPress post.
 * @param SimplePie_Item $item The feed item.
 * @param int|string $feedId The ID of the feed source.
 *
 * @return array<string, Term[]> An associative array that maps taxonomy slugs to lists of {@link Term} objects.
 */
function dryRunPostTerms(WP_Post $post, SimplePie_Item $item, $feedId)
{
    $result = [];
    $rules = getApplicableRules($item, $post->post_type, $feedId);

    foreach ($rules as $rule) {
        /** Convert the rule's term slugs into {@link Term} objects */
        $terms = Term::fromSlugs($rule->taxonomy, $rule->terms);

        if ($rule->autoCreate) {
            $toCreate = getTermsFromItem($item, $rule->taxonomy, $feedId);
            $terms = array_merge($terms, $toCreate);
        }

        if (array_key_exists($rule->taxonomy, $result)) {
            $result[$rule->taxonomy] = array_merge($result[$rule->taxonomy], $terms);
        } else {
            $result[$rule->taxonomy] = $terms;
        }
    }

    return $result;
}

/**
 * Retrieves the list of terms that can be auto created for a specific feed item.
 *
 * @param SimplePie_Item $item The item.
 * @param string $taxonomy The taxonomy of the returned terms.
 * @param int|string $feedId The ID of the feed source. Only used to support a legacy filter.
 *
 * @return Term[] A list of term instances.
 */
function getTermsFromItem(SimplePie_Item $item, $taxonomy, $feedId)
{
    $categories = $item->get_categories();
    $categories = is_array($categories) ? array_filter($categories) : [];

    /**
     * Convert the SimplePie categories into arrays, which will later be converted into {@link Term} objects
     * using the {@link Term::fromArray()} static constructor.
     */
    $categories = array_map(function ($term) {
        return [
            'name' => $term->get_label(),
            'args' => [],
        ];
    }, $categories);

    // Apply WordPress filter to allow custom manipulation from user code
    $categories = apply_filters('wprss_auto_create_terms', $categories, $taxonomy, $feedId);
    $categories = array_filter($categories);

    $result = [];

    foreach ($categories as $category) {
        $result[] = Term::fromArray($taxonomy, $category);
    }

    return $result;
}

/**
 * Applies taxonomy terms to a post based on the global and feed's taxonomy rules.
 *
 * @param WP_Post $post The WordPress post.
 * @param SimplePie_Item $item The feed item.
 * @param int|string $feedId The ID of the feed source.
 */
function applyTerms(WP_Post $post, SimplePie_Item $item, $feedId)
{
    $toApply = dryRunPostTerms($post, $item, $feedId);

    foreach ($toApply as $taxonomy => $terms) {
        $slugs = [];
        foreach ($terms as $term) {
            // Get the WordPress term object, creating the term if needed
            $wpTerm = $term->getOrCreate();

            // Skip if failed to create the missing term
            if ($wpTerm === false) {
                continue;
            }

            $slugs[] = $wpTerm->slug;
        }

        wpra_get_logger($feedId)->debug("Applying {0} terms: {1}", [
            $taxonomy,
            implode(', ', $slugs),
        ]);

        // Set the terms to the post
        wp_set_object_terms($post->ID, $slugs, $taxonomy);

        // Clear the taxonomy's cache
        delete_option($taxonomy . "_children");
    }
}
