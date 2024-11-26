<?php

namespace Smush\Core;

use Smush\Core\Modules\Background\Background_Utils;
use Smush\Core\Modules\Background\Mutex;

class Security_Utils {
	const EXPECTED_NONCES_OPTION = 'wp_smush_expected_public_nonces';
	/**
	 * @var Array_Utils
	 */
	private $array_utils;
	/**
	 * @var Background_Utils
	 */
	private $background_utils;

	public function __construct() {
		$this->array_utils      = new Array_Utils();
		$this->background_utils = new Background_Utils();
	}

	public function create_public_nonce( $action = - 1 ) {
		$nonce = wp_hash( wp_nonce_tick() . '|' . $action, 'nonce' );

		$this->add_expected_nonce( $nonce );

		return $nonce;
	}

	public function verify_public_nonce( $nonce, $action = - 1 ) {
		$nonce_valid    = hash_equals( wp_hash( wp_nonce_tick() . '|' . $action, 'nonce' ), $nonce );
		$nonce_expected = $this->is_nonce_expected( $nonce );

		return $nonce_valid && $nonce_expected;
	}

	private function add_expected_nonce( $nonce ) {
		$mutex = new Mutex( 'wp_smush_add_expected_nonce' );
		$mutex->execute( function () use ( $nonce ) {
			$expected_nonces                 = $this->clean_expected( $this->get_expected_nonces_option() );
			$time                            = time();
			$expected_nonces["$nonce-$time"] = array(
				'time'  => $time,
				'nonce' => $nonce,
			);
			$this->update_expected_nonces_option( $expected_nonces );
		} );
	}

	private function is_nonce_expected( $nonce ) {
		$expected_nonces = $this->get_expected_nonces_option();
		foreach ( $expected_nonces as $data ) {
			$now            = time();
			$time           = (int) $this->array_utils->get_array_value( $data, 'time' );
			$is_fresh       = ( $now - $time ) < $this->get_expected_nonce_expiry();
			$expected_nonce = $this->array_utils->get_array_value( $data, 'nonce' );
			if ( $is_fresh && $expected_nonce === $nonce ) {
				return true;
			}
		}

		return false;
	}

	private function clean_expected( $expected_nonces ) {
		$now = time();
		foreach ( $expected_nonces as $key => $data ) {
			$time = (int) $this->array_utils->get_array_value( $data, 'time' );
			if ( ( $now - $time ) > $this->get_expected_nonce_expiry() ) {
				unset( $expected_nonces[ $key ] );
			}
		}
		return $expected_nonces;
	}

	/**
	 * @return array
	 */
	private function get_expected_nonces_option() {
		$raw = $this->background_utils->get_option( self::EXPECTED_NONCES_OPTION, array() );
		return $this->array_utils->ensure_array( $raw );
	}

	/**
	 * @param array $expected_nonces
	 *
	 * @return void
	 */
	private function update_expected_nonces_option( $expected_nonces ) {
		update_option( self::EXPECTED_NONCES_OPTION, $expected_nonces, false );
	}

	/**
	 * @return float|int
	 */
	private function get_expected_nonce_expiry() {
		// 15 minutes
		return MINUTE_IN_SECONDS * 15;
	}
}