<?php

namespace Smush\Core;

use WP_Smush;
use WPMUDEV_Analytics;

class Product_Analytics {
	const PROJECT_TOKEN = '5d545622e3a040aca63f2089b0e6cae7';
	const EVENT_COUNT_KEY = 'wp_smush_event_count_%s';
	/**
	 * @var WPMUDEV_Analytics
	 */
	private $analytics;
	/**
	 * @var Server_Utils
	 */
	private $server_utils;

	/**
	 * Static instance
	 *
	 * @var self
	 */
	private static $instance;
	/**
	 * @var Format_Utils
	 */
	private $format_utils;
	/**
	 * @var Settings|null
	 */
	private $settings;
	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	/**
	 * Static instance getter
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->server_utils = new Server_Utils();
		$this->format_utils = new Format_Utils();
		$this->array_utils  = new Array_Utils();
		$this->settings     = Settings::get_instance();
	}

	/**
	 * @return WPMUDEV_Analytics
	 */
	private function get_analytics() {
		if ( is_null( $this->analytics ) ) {
			$this->analytics = $this->prepare_analytics_instance();
		}

		return $this->analytics;
	}

	private function prepare_analytics_instance() {
		if ( ! class_exists( 'WPMUDEV_Analytics' ) ) {
			require_once WP_SMUSH_DIR . 'core/external/wpmudev-analytics/autoload.php';
		}

		$mixpanel = new WPMUDEV_Analytics( 'smush', 'Smush', 55, $this->get_token() );
		$mixpanel->identify( $this->get_unique_id() );
		$mixpanel->registerAll( $this->get_super_properties() );

		return $mixpanel;
	}

	public function get_unique_id() {
		$site_url         = home_url();
		$has_valid_domain = $this->has_valid_domain( $site_url );
		if ( ! $has_valid_domain ) {
			$site_url         = site_url();
			$has_valid_domain = $this->has_valid_domain( $site_url );
		}
		return $has_valid_domain ? $this->normalize_url( $site_url ) : '';
	}

	private function get_token() {
		if ( empty( $this->get_unique_id() ) ) {
			return '';
		}
		return self::PROJECT_TOKEN;
	}

	private function has_valid_domain( $url ) {
		$pattern  = '/^(https?:\/\/)?([a-z0-9-]+\.)*[a-z0-9-]+(\.[a-z]{2,})/i';
		$is_valid = preg_match( $pattern, $url );
		if ( $is_valid ) {
			return true;
		}

		return preg_match( '/^(https?:\/\/)?localhost/i', $url );
	}

	private function normalize_url( $url ) {
		$url = str_replace( array( 'http://', 'https://', 'www.' ), '', $url );

		return untrailingslashit( $url );
	}

	private function get_super_properties() {
		global $wp_version;

		$super_properties = array(
			'active_theme'       => get_stylesheet(),
			'locale'             => get_locale(),
			'mysql_version'      => $this->server_utils->get_mysql_version(),
			'php_version'        => phpversion(),
			'plugin'             => 'Smush',
			'plugin_type'        => WP_Smush::is_pro() ? 'pro' : 'free',
			'plugin_version'     => WP_SMUSH_VERSION,
			'server_type'        => $this->server_utils->get_server_type(),
			'memory_limit'       => $this->format_utils->convert_to_megabytes( $this->server_utils->get_memory_limit() ),
			'max_execution_time' => $this->server_utils->get_max_execution_time(),
			'wp_type'            => is_multisite() ? 'multisite' : 'single',
			'wp_version'         => $wp_version,
			'device'             => $this->server_utils->get_device_type(),
			'user_agent'         => $this->server_utils->get_user_agent(),
		);

		return array_merge( $super_properties, $this->get_date_time_properties() );
	}

	private function get_date_time_properties() {
		$properties  = array();
		$event_times = get_site_option( 'wp_smush_event_times', array() );
		$time_events = array(
			'Installation Date' => 'plugin_installed',
			'Activation Date'   => 'plugin_activated',
			'Last Updated'      => 'plugin_upgraded',
		);

		foreach ( $time_events as $event_name => $event_key ) {
			if ( ! empty( $event_times[ $event_key ] ) ) {
				$properties[ $event_name ] = date( 'c', $event_times[ $event_key ] );
			}
		}

		return $properties;
	}

	public function maybe_track( $event, $properties = array(), $limit_per_day = 0 ) {
		if ( $this->should_track( $event, $properties, $limit_per_day ) ) {
			$this->track( $event, $properties );
		}
	}

	private function should_track( $event, $properties, $limit_per_day ) {
		if ( ! $this->settings->get( 'usage' ) ) {
			return false;
		}

		if ( $limit_per_day < 1 ) {
			return true;
		}

		return ! $this->is_event_limit_reached( $event, $properties, $limit_per_day );
	}

	private function is_event_limit_reached( $event, $properties, $limit_per_day ) {
		$event_count_key = $this->get_event_count_key( $event, $properties );
		$event_count     = (int) get_transient( $event_count_key, 0 );

		if ( $event_count >= $limit_per_day ) {
			return true;
		}

		set_transient( $event_count_key, $event_count + 1, DAY_IN_SECONDS );

		return false;
	}

	private function get_event_count_key( $event, $properties ) {
		if ( method_exists( $this, "get_event_count_key_$event" ) ) {
			return call_user_func( array( $this, "get_event_count_key_$event" ), $event, $properties );
		} else {
			return sprintf( self::EVENT_COUNT_KEY, $event );
		}
	}

	public function track( $event, $properties = array() ) {
		$debug_mode = defined( 'WP_SMUSH_MIXPANEL_DEBUG' ) && WP_SMUSH_MIXPANEL_DEBUG;
		if ( $debug_mode ) {
			Helper::logger()->track()->info( sprintf( 'Track Event %1$s: %2$s', $event, print_r( $properties, true ) ) );
		} else {
			$this->get_analytics()->track( $event, $properties );
		}
	}

	public function maybe_track_error( $type, $code, $message ) {
		$limit_per_day = 1;
		$this->maybe_track(
			'smush_error_encountered',
			array(
				'Error Type'    => $type,
				'Error Code'    => $code,
				'Error Message' => $message,
			),
			$limit_per_day
		);
	}

	protected function get_event_count_key_smush_error_encountered( $event, $properties ) {
		$event_key  = $event;
		$error_type = $this->array_utils->get_array_value( $properties, 'Error Type' );
		$error_code = $this->array_utils->get_array_value( $properties, 'Error Code' );
		if ( ! empty( $error_type ) && ! empty( $error_code ) ) {
			$event_key = $error_type . '_' . $error_code;
		}

		return sprintf( self::EVENT_COUNT_KEY, sanitize_key( $event_key ) );
	}
}