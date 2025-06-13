<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

interface ApplicationStateInterface {
	/**
	 * Get the initial state.
	 *
	 * @return array
	 */
	public function get_initial_state();

	/**
	 * Load the state from the repository.
	 *
	 * @param string $migration_id
	 *
	 * @return $this
	 */
	public function load_state( $migration_id );

	/**
	 * Set a property in the state.
	 *
	 * @param string $property Property to set.
	 * @param mixed  $value    Value to set.
	 *
	 * @return void
	 */
	public function set( $property, $value );

	/**
	 * Get a property from the state.
	 *
	 * @param string $property Property to get.
	 *
	 * @return mixed
	 */
	public function get( $property );

	/**
	 * Increment an integer property.
	 *
	 * @param string $property Property to increment.
	 * @param int    $value    Value to increment by.
	 *
	 * @return void
	 */
	public function inc( $property, $value );

	/**
	 * Decrement an integer property.
	 *
	 * @param string $property Property to decrement.
	 * @param int    $value    Value to increment by.
	 *
	 * @return void
	 */
	public function dec( $property, $value );

	/**
	 * Get the state array.
	 *
	 * @return array
	 */
	public function get_state();

	/**
	 * Bulk update the state.
	 *
	 * @param array $properties Properties to update, key => value.
	 *
	 * @return void
	 */
	public function update_state( $properties = [] );
}
