<?php

namespace DeliciousBrains\WPMDB\Common\Replace;

/**
 * Class AbstractReplacePairInterface
 *
 * @package DeliciousBrains\WPMDB\Common\Replace
 */
abstract class AbstractReplacePair implements ReplacePairInterface {
	/**
	 * @var string
	 */
	protected $pattern;

	/**
	 * @var string
	 */
	protected $replace;

	/**
	 * AbstractReplacePairInterface constructor.
	 *
	 * @param string $pattern
	 * @param string $replace
	 */
	public function __construct( $pattern, $replace ) {
		$this->pattern = $pattern;
		$this->replace = $replace;
	}

	/**
	 * Apply the replace operation to the subject.
	 *
	 * @param string $subject
	 *
	 * @return string
	 */
	public function apply( $subject ) {
		return $subject;
	}

	/**
	 * Check if the subject has a match.
	 *
	 * @param string $subject
	 *
	 * @return bool
	 */
	public function has_match( $subject ) {
		return false;
	}
}
