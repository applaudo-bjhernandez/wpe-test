<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState\Migrations;

use DeliciousBrains\WPMDB\Common\MigrationState\ApplicationStateAbstract;

class LocalSiteState extends ApplicationStateAbstract {
	protected $state_identifier = 'local_site';

	/**
	 * Get the initial state of the current migration state branch.
	 *
	 * @return array
	 */
	public function get_initial_state() {
		return [
			'site_details' => [ 'migrate_url' => '' ],
			'this_url'     => '',
		];
	}

	public function set( $property, $value, $safe = true ) {
		parent::set( $property, $value, false );
	}
}
