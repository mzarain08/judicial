<?php

namespace Engage\JudicialWatch\Services;

use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Hooks\HookException;

class Redirector extends AbstractHandler {
	/**
	 * initialize
	 *
	 * Uses addAction() and addFilter() to connect WordPress to the methods
	 * of this object's child which are intended to be protected.
	 *
	 * @return void
	 * @throws HookException
	 */
	public function initialize() {
		$this->addAction("init", "confirmRedirectionTable");
	}

	/**
	 * confirmRedirectionTable
	 *
	 * Checks to see if the redirection table is present and, if not,
	 * creates it.
	 *
	 * @return void
	 */
	protected function confirmRedirectionTable(): void {
		if (!$this->redirectionTableExists()) {
			$this->createRedirectionTable();
		}
	}

	/**
	 * redirectionTableExists
	 *
	 * Returns true if the redirection table exists.
	 *
	 * @return bool
	 */
	protected function redirectionTableExists(): bool {
		global $wpdb;

		// we try to select tables that have a name like ours.  if the
		// database returns our table back to us, then we're good to go.
		// notice that we use a LIKE operator here (because that's how
		// a SHOW TABLES query works)

		$sql = "SHOW TABLES LIKE '%s'";
		$table = $this->getRedirectionTableName();
		$statement = $wpdb->prepare($sql, $table);
		return $wpdb->get_var($statement) === $table;
	}

	/**
	 * getRedirectionTableName
	 *
	 * Returns the name of our redirection table.
	 *
	 * @return string
	 */
	protected function getRedirectionTableName(): string {
		global $wpdb;
		return $wpdb->prefix . "redirection";
	}

	/**
	 * createRedirectionTable
	 *
	 * Creates the redirection table.
	 *
	 * @return void
	 */
	protected function createRedirectionTable(): void {
		global $wpdb;
		require_once(ABSPATH . "wp-admin/includes/upgrade.php");
		$table = $this->getRedirectionTableName();

		$sql = <<< SQL
			CREATE TABLE $table (
				`post_id` BIGINT(20) UNSIGNED NOT NULL,
				`impermalink` VARCHAR(255) NOT NULL,
				`source_id` BIGINT(20) UNSIGNED NOT NULL,
				PRIMARY KEY (`post_id`, `impermalink`)
			)
SQL;

		dbDelta($sql . $wpdb->get_charset_collate());
	}
}