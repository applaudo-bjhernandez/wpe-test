<?php

namespace DeliciousBrains\WPMDB\Common\Replace;

use DeliciousBrains\WPMDB\Common\Util\Util;

/**
 * Class CaseSensitivePair
 *
 * @package DeliciousBrains\WPMDB\Common\Replace
 */
class CaseSensitivePair extends AbstractReplacePair {
	/**
	 * Apply the replace operation to the subject.
	 *
	 * @param string $subject
	 *
	 * @return string
	 */
	public function apply( $subject ) {
		return str_replace( $this->pattern, $this->replace, $subject );
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
		return strpos( $subject, $this->pattern ) !== false ||
			strpos( $subject, Util::json_encode_trim( $this->pattern ) ) !== false;
	}
}
