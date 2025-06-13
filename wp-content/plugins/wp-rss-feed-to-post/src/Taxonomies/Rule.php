<?php

namespace RebelCode\Wpra\FeedToPost\Taxonomies;

use WP_Post;
use WPRSS_FTP_Utils;

class Rule
{
    /** @var string */
    public $taxonomy;

    /** @var string[] */
    public $terms;

    /** @var bool */
    public $autoCreate;

    /** @var RuleCondition */
    public $condition;

    /**
     * Constructor.
     *
     * @param string $taxonomy
     * @param string[] $terms
     * @param bool $autoCreate
     * @param RuleCondition $condition
     */
    public function __construct($taxonomy, array $terms, $autoCreate, RuleCondition $condition)
    {
        $this->taxonomy = $taxonomy;
        $this->terms = $terms;
        $this->autoCreate = $autoCreate;
        $this->condition = $condition;
    }

    public static function createFromArray(array $data)
    {
        if (!isset($data['taxonomy'])) {
            return null;
        }

        $taxonomy = strtolower($data['taxonomy']);

        $terms = isset($data['terms']) ? $data['terms'] : [];
        $terms = is_array($terms) ? $terms : [];

        $autoCreate = WPRSS_FTP_Utils::multiboolean($data['auto']);

        $condition = RuleCondition::fromArray($data);

        return new static($taxonomy, $terms, $autoCreate, $condition);
    }
}
