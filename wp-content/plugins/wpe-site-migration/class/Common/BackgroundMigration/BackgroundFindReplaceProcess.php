<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

use DeliciousBrains\WPMDB\Data\Stage;

class BackgroundFindReplaceProcess extends BackgroundMigrationProcess {
	/**
	 * @inheritdoc
	 */
	protected $action = 'find_replace';

	/**
	 * @inheritdoc
	 */
	protected function stage_processed( $progress, $stage, $item ) {
		$complete = parent::stage_processed( $progress, $stage, $item );

		// Pause at the end of a dry-run.
		if ( $complete && Stage::TABLES === $stage['stage'] && $this->preview() ) {
			$this->pause();
		}

		return $complete;
	}
}
