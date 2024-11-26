<?php

namespace PrestoPlayer\Pro\Services\API;

use PrestoPlayer\Pro\Services\EmailExport;

class RestEmailCollectionController {

	protected $namespace = 'presto-player';
	protected $version   = 'v1';
	protected $base      = 'email';

	public function register() {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	public function registerRoutes() {
		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/export',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export' ),
				'permission_callback' => array( $this, 'export_permissions_check' ),
				'args'                => array(
					'step' => array(
						'description' => __( 'Your API key' ),
						'type'        => 'number',
						'required'    => true,
					),
				),
			)
		);
	}

	/**
	 * Must have export permissions
	 *
	 * @return void
	 */
	public function export_permissions_check() {
		return apply_filters( 'presto_player_export_permissions_check', current_user_can( 'export' ) );
	}

	/**
	 * Connect to the mailchimp api and store settings
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function export( $request ) {
		$step = $request['step'];

		$exporter   = new EmailExport( $step );
		$hasMore    = $exporter->processStep();
		$percentage = $exporter->getPercentageComplete();

		if ( $hasMore ) {
			$step++;
			return rest_ensure_response(
				array(
					'step'       => $step,
					'percentage' => $percentage,
				) 
			);
		}

		if ( true === $exporter->is_empty ) {
			return new \WP_Error( 'export_empty', __( 'There is nothing to export.', 'presto-player' ) );
		}

		$download_url = add_query_arg(
			array(
				'nonce'         => wp_create_nonce( 'presto_download_export' ),
				'presto_action' => 'download_export',
			),
			admin_url()
		);

		// send file
		return rest_ensure_response(
			array(
				'step' => 'done',
				'url'  => $download_url,
			) 
		);
	}
}
