<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState\Migrations;

use DeliciousBrains\WPMDB\Common\MigrationState\ApplicationStateAbstract;

class RemoteSiteState extends ApplicationStateAbstract {
	protected $state_identifier = "remote_site";

	/**
	 * Get the initial state of the current migration state branch.
	 *
	 * @return array
	 */
	public function get_initial_state() {
		return [
			'url'          => '',
			'site_details' => [ 'login_url' => '' ],
		];
	}

	public function set( $property, $value, $safe = true ) {
		parent::set( $property, $value, false );
	}
}
