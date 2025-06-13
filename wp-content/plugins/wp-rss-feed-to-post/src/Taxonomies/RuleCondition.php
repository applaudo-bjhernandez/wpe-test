<?php

namespace RebelCode\Wpra\FeedToPost\Taxonomies;

use SimplePie_Item;
use WPRSS_FTP_Meta;

class RuleCondition
{
    const ALL = 'all';
    const ANY = 'any';
    const TITLE = 'title';
    const CONTENT = 'content';

    /** @var string */
    public $compare;

    /** @var string[] */
    public $subjects;

    /** @var string[] */
    public $keywords;

    /** @var bool */
    public $wholeWords;

    /**
     * Constructor.
     *
     * @param string $compare
     * @param string[] $subject
     * @param string[] $keywords
     * @param bool $wholeWords
     */
    public function __construct($compare, $subject, array $keywords, $wholeWords)
    {
        $this->compare = $compare;
        $this->subjects = $subject;
        $this->keywords = $keywords;
        $this->wholeWords = $wholeWords;
    }

    public function isSatisfiedByItem(SimplePie_Item $item)
    {
        $numKeywords = count($this->keywords);
        $numSubjects = count($this->subjects);

        // Empty conditions are automatically satisfied, or "ignored"
        if ($numKeywords === 0 || $numSubjects === 0) {
            return true;
        }

        // The value to check against for exiting the loop early. For "ANY" comparisons, we check for true (keyword was
        // found) and exit with true. For "ALL" comparisons, we check for false (keyword not found) and return false.
        $earlyExitValue = $this->compare === static::ANY;
        // The value to return after the loop has finished. For "ANY" comparisons, we return false since we rely on
        // early exits of "true" values. For "ALL" comparisons, we return true since we expect the loop to finish.
        $afterLoopValue = $this->compare === static::ALL;

        foreach ($this->keywords as $keyword) {
            foreach ($this->subjects as $subject) {
                $strToCheck = $subject === static::TITLE
                    ? $item->get_title()
                    : $item->get_content();

                $containsKeyword = $this->wholeWords
                    ? preg_match("/\b" . preg_quote($keyword) . "\b/mi", $strToCheck) === 1
                    : stripos($strToCheck, $keyword) !== false;

                if ($containsKeyword === $earlyExitValue) {
                    return $earlyExitValue;
                }
            }
        }

        return $afterLoopValue;
    }

    public static function fromArray(array $data)
    {
        $subjects = [];
        if (!empty($data['filter_subject'])) {
            $subjects = is_array($data['filter_subject'])
                ? $data['filter_subject']
                : explode(',', $data['filter_subject']);
            $subjects = array_map('trim', $subjects);
            $subjects = array_filter($subjects);
        }

        $compareStr = isset($data['post_taxonomy_compare_method'])
            ? $data['post_taxonomy_compare_method']
            : WPRSS_FTP_Meta::get_instance()->getDefaultTaxonomyCompareMethod();

        $keywords = [];
        if (!empty($data['filter_keywords'])) {
            $keywords = explode(',', $data['filter_keywords']);
            $keywords = array_map('trim', $keywords);
            $keywords = array_filter($keywords);
        }

        $wholeWords = isset($data['whole_words'])
            ? filter_var($data['whole_words'], FILTER_VALIDATE_BOOLEAN)
            : false;

        return new self(
            static::compareFromStr($compareStr),
            static::subjectsFromArray($subjects),
            $keywords,
            $wholeWords
        );
    }

    public static function subjectsFromArray(array $subjects)
    {
        $result = [];

        foreach ($subjects as $subject) {
            $subject = strtolower($subject);

            if ($subject === static::TITLE) {
                $result[] = static::TITLE;
            } elseif ($subject === static::CONTENT) {
                $result[] = static::CONTENT;
            }
        }

        return $result;
    }

    public static function compareFromStr($compare)
    {
        $compare = strtolower($compare);

        return $compare === 'all'
            ? static::ALL
            : static::ANY;
    }
}
