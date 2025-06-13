<?php

namespace DeliciousBrains\WPMDB\Common\Replace;

/**
 * Interface ReplacePairInterface
 *
 * @package DeliciousBrains\WPMDB\Common\Replace
 */
interface ReplacePairInterface {
	/**
	 * Apply the search/replace on $subject
	 *
	 * @param string $subject
	 *
	 * @return string
	 */
	public function apply( $subject );

	/**
	 * Check if $subject has a match for the search.
	 *
	 * @param string $subject
	 *
	 * @return bool
	 */
	public function has_match( $subject );
}
