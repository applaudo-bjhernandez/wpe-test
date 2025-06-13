<?php

namespace DeliciousBrains\WPMDB\Common\Replace;

use DeliciousBrains\WPMDB\Common\Util\Util;
use Exception;

/**
 * Class RegexPair
 *
 * @package DeliciousBrains\WPMDB\Common\Replace
 */
class RegexPair extends AbstractReplacePair {

	/**
	 * Apply the replace operation to the subject.
	 *
	 * @param string $subject
	 *
	 * @return string
	 */
	public function apply( $subject ) {
		$replaced = preg_replace( $this->pattern, $this->replace, $subject );

		if ( null !== $replaced ) {
			return $replaced;
		}

		return $subject;
	}

	/**
	 * Check if the subject has a match.
	 *
	 * @param string $subject
	 *
	 * @return bool
	 */
	public function has_match( $subject )
	{
 		$has_match = preg_match( $this->pattern, $subject, $matches );
		if ($has_match !== 1) {
			// Converting the pattern to JSON encoding could potentially be destructive. If it fails, we'll just
			// return true to be on the safe side.
			try {
				$delimiter     = $this->pattern[0];
				$naked_pattern = substr( $this->pattern, 1, -1 );
				$json_pattern  = $delimiter . Util::json_encode_trim( $naked_pattern ) . $delimiter;
				$has_match     = preg_match( $json_pattern, $subject, $matches );
			} catch (Exception $e) {
				return true;
			}
		}

		return $has_match === 1;
	}
}
