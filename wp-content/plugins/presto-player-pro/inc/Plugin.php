<?php

namespace PrestoPlayer\Pro;

class Plugin {

	/**
	 * This is the core version required for this plugin to work.
	 *
	 * @var string
	 */
	protected $required_core_version = '1.1.0';

	/**
	 * Hols the services needed to update the plugin.
	 * 
	 * @var array
	 */
	protected $update_services = array();

	/**
	 * Maybe popular update services.
	 */
	public function __construct() {
		// check if we need licensing.
		if ( $this->requiresLicensing() && class_exists( '\PrestoPlayer\Pro\Services\License\License' ) ) {
			$this->update_services[] = \PrestoPlayer\Pro\Services\License\License::class;
		}
		// if we required licensing and have a license, add auto update service.
		if ( $this->requiresLicensing() ) {
			$this->update_services[] = \PrestoPlayer\Pro\Services\License\AutoUpdate::class;
		}
	}

	/**
	 * Bootstrap the plugin
	 *
	 * @return void
	 */
	public function bootstrap() {
		// register our components.
		add_filter( 'presto_player_pro_components', array( $this, 'registerProComponents' ) );
	}

	/**
	 * Do we have the required core version?
	 *
	 * @return boolean
	 */
	public function hasRequiredCoreVersion() {
		return method_exists( '\PrestoPlayer\Plugin', 'version' ) && version_compare( \PrestoPlayer\Plugin::version(), $this->required_core_version, '<' );
	}

	/**
	 * Do we have the required core version?
	 *
	 * @return boolean
	 */
	public function hasRequiredProVersion() {
		return method_exists( '\PrestoPlayer\Plugin', 'requiredProVersion' ) && version_compare( \PrestoPlayer\Plugin::requiredProVersion(), self::version(), '>' );
	}

	/**
	 * Register any pro components;
	 *
	 * @return void
	 */
	public function registerProComponents( $components ) {
		$required_core_version = $this->required_core_version;

		// if it requires licensing, and we don't have a license entered yet, return the update services.
		if ( $this->requiresLicensing() && ! $this->hasLicense() ) {
			return $this->update_services;
		}

		// if we don't have the required core version, 
		// show an admin notice and return the update services.
		if ( $this->hasRequiredCoreVersion() ) {
			add_action(
				'admin_notices',
				function () use ( $required_core_version ) {
					$class = 'notice notice-error';
					// Translators: verison number.
					$message = sprintf( __( 'Presto Player Pro requires the core plugin be at least %s. Please update the Presto Player plugin.', 'presto-player-pro' ), $required_core_version );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				}
			);
			return $this->update_services;
		}

		// if we don't have the required pro version, 
		// show an admin notice and return the update services.
		if ( $this->hasRequiredProVersion() ) {
			add_action(
				'admin_notices',
				function () use ( $required_core_version ) {
					$class = 'notice notice-error';
					// Translators: verison number.
					$message = sprintf( __( 'Presto Player requires the Presto Player Pro plugin be at least %s. Please update the Presto Player Pro plugin.', 'presto-player' ), $required_core_version );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				}
			);
			return $this->update_services;
		}

		// define that we have pro enabled.
		define( 'PRESTO_PLAYER_PRO_ENABLED', true );

		// load the pro components.
		$config = include_once 'config/app.php';
		return array_filter( array_merge( $components, $config['components'], $this->update_services ) );
	}

	/**
	 * Do we have a license stored?
	 *
	 * @return boolean
	 */
	public function hasLicense() {
		return class_exists( '\PrestoPlayer\Models\Setting' ) && ! empty( \PrestoPlayer\Models\Setting::get( 'license', 'key' ) );
	}

	/**
	 * Does this require licensing?
	 *
	 * @return void
	 */
	public function requiresLicensing() {
		return defined( 'PRESTO_TESTSUITE' ) ? false : class_exists( '\PrestoPlayer\Pro\Services\License\License' );
	}

	/**
	 * Get the version from plugin data
	 *
	 * @return string
	 */
	public static function version() {
		// Load version from plugin data.
		if ( ! \function_exists( 'get_plugin_data' ) ) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return \get_plugin_data( PRESTO_PLAYER_PRO_PLUGIN_FILE, false, false )['Version'];
	}
}
