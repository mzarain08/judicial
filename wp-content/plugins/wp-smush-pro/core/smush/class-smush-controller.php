<?php

namespace Smush\Core\Smush;

use Smush\Core\Array_Utils;
use Smush\Core\Controller;
use Smush\Core\Media\Media_Item;
use Smush\Core\Stats\Global_Stats;
use Smush\Core\Stats\Media_Item_Optimization_Global_Stats_Persistable;
use Smush\Core\Webp\Webp_Converter;

class Smush_Controller extends Controller {
	const GLOBAL_STATS_OPTION_ID = 'wp-smush-optimization-global-stats';
	const SMUSH_OPTIMIZATION_ORDER = 30;

	private $global_stats;
	/**
	 * Static instance
	 *
	 * @var self
	 */
	private static $instance;
	/**
	 * @var Array_Utils
	 */
	private $array_utils;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->array_utils  = new Array_Utils();
		$this->global_stats = Global_Stats::get();

		$this->register_filter( 'wp_smush_optimizations', array(
			$this,
			'add_smush_optimization',
		), self::SMUSH_OPTIMIZATION_ORDER, 2 );
		$this->register_filter( 'wp_smush_global_optimization_stats', array( $this, 'add_png2jpg_global_stats' ) );
		$this->register_filter( 'wp_smush_optimization_global_stats_instance', array(
			$this,
			'create_global_stats_instance',
		), 10, 2 );
		$this->register_action( 'wp_smush_settings_updated', array(
			$this,
			'maybe_mark_global_stats_as_outdated',
		), 10, 2 );
		$this->register_action( 'wp_ajax_save_optimized_file', array( $this, 'ajax_save_optimized_file' ) );
		$this->register_action( 'wp_ajax_nopriv_save_optimized_file', array( $this, 'ajax_save_optimized_file' ) );

		// Bulk image sizes.
		$this->register_action( 'wp_smush_image_sizes_updated', array(
			$this,
			'mark_global_stats_as_outdated_on_image_sizes_change',
		), 10, 2 );
		$this->register_action( 'wp_smush_image_sizes_deleted', array( $this->global_stats, 'mark_as_outdated' ) );
		$this->register_action( 'wp_smush_image_sizes_added', array( $this->global_stats, 'mark_as_outdated' ) );
	}

	/**
	 * @param $optimizations array
	 * @param $media_item Media_Item
	 *
	 * @return array
	 */
	public function add_smush_optimization( $optimizations, $media_item ) {
		$optimization                              = new Smush_Optimization( $media_item );
		$optimizations[ $optimization->get_key() ] = $optimization;

		return $optimizations;
	}

	public function add_png2jpg_global_stats( $stats ) {
		$stats[ Smush_Optimization::KEY ] = new Media_Item_Optimization_Global_Stats_Persistable(
			self::GLOBAL_STATS_OPTION_ID,
			new Smush_Optimization_Global_Stats()
		);

		return $stats;
	}

	public function create_global_stats_instance( $original, $key ) {
		if ( $key === Smush_Optimization::KEY ) {
			return new Smush_Optimization_Global_Stats();
		}

		return $original;
	}

	public function maybe_mark_global_stats_as_outdated( $old_settings, $settings ) {
		$old_lossy_status     = ! empty( $old_settings['lossy'] ) ? (int) $old_settings['lossy'] : 0;
		$new_lossy_status     = ! empty( $settings['lossy'] ) ? (int) $settings['lossy'] : 0;
		$lossy_status_changed = $old_lossy_status !== $new_lossy_status;

		$old_exif_status     = ! empty( $old_settings['strip_exif'] );
		$new_exif_status     = ! empty( $settings['strip_exif'] );
		$exif_status_changed = $old_exif_status !== $new_exif_status;

		if ( $lossy_status_changed || $exif_status_changed ) {
			$this->global_stats->mark_as_outdated();
		}
	}

	public function ajax_save_optimized_file() {
		$request_id = (string) $this->array_utils->get_array_value( $_REQUEST, 'request_id' );
		$nonce      = (string) $this->array_utils->get_array_value( $_REQUEST, 'nonce' );
		$file_path  = urldecode( (string) $this->array_utils->get_array_value( $_REQUEST, 'file_path' ) );
		$file_url   = urldecode( (string) $this->array_utils->get_array_value( $_REQUEST, 'file_url' ) );
		$md5        = (string) $this->array_utils->get_array_value( $_REQUEST, 'file_md5' );
		$webp       = ! empty( $_REQUEST['webp'] );
		$processor  = $webp
			? new Webp_Converter()
			: new Smusher();

		$error       = null;
		$should_save = $processor->should_save_image_stream( $nonce, $file_path, $file_url, $request_id );
		if ( is_wp_error( $should_save ) ) {
			$error = $should_save;
		} else {
			$saved = $processor->save_smushed_image_stream( $nonce, "php://input", $file_path, $file_url, $md5 );
			if ( is_wp_error( $saved ) ) {
				$error = $saved;
			}
		}

		if ( $error ) {
			wp_send_json( array(
				'success'    => false,
				'error_code' => $error->get_error_code(),
				'message'    => $error->get_error_message(),
			) );
		} else {
			wp_send_json( array(
				'success' => true,
				'message' => 'File saved successfully',
			) );
		}
	}

	public function mark_global_stats_as_outdated_on_image_sizes_change( $old_image_sizes, $new_image_sizes ) {
		$image_sizes_updated = count( $old_image_sizes ) !== count( $new_image_sizes )
		                       || array_diff( $old_image_sizes, $new_image_sizes );

		if ( ! empty( $image_sizes_updated ) ) {
			$this->global_stats->mark_as_outdated();
		}
	}
}