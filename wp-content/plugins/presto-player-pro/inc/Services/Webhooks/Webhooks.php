<?php

namespace PrestoPlayer\Pro\Services\Webhooks;

use PrestoPlayer\Models\Webhook;
use PrestoPlayer\Pro\Support\EmailProvider;

/**
 * Webhooks service.
 */
class Webhooks extends EmailProvider {

	/**
	 * The email provider key.
	 *
	 * @var string
	 */
	protected $key = 'webhooks';

	/**
	 * Register the service hooks.
	 *
	 * @return void
	 */
	public function register() {
		// required presto version
		if ( version_compare( \PrestoPlayer\Plugin::version(), '1.9.15', '<' ) ) {
			return;
		}

		add_action( 'presto_player/pro/forms/save', array( $this, 'maybeHandleSubmit' ), 10, 2 );
	}

	/**
	 * Handle the action.
	 *
	 * @param array  $data The data from the form.
	 * @param  object $preset The preset.
	 * @return void
	 */
	public function handle( $data, $preset ) {
		$webhook_id = $preset->email_collection['provider_list'];
		if ( empty( $webhook_id ) ) {
			return;
		}

		$webhook = ( new Webhook() )->get( (int) $webhook_id );

		// bail and log error.
		if ( is_wp_error( $webhook ) ) {
			return;
		}

		$url = $webhook->url;
		if ( empty( $url ) ) {
			return;
		}

		// make request for each webhook.
		$this->makeRequest( $webhook, $data );
	}

	/**
	 * Make the request.
	 *
	 * @param \SureCart\Models\Webhook $webhook Webhook model.  
	 * @param array                    $data Submission data.
	 * @return void
	 */
	public function makeRequest( $webhook, $data ) {
		$key = $webhook->email_name ?? 'email';

		$headers = array( 'Content-Type' => 'application/json' );
		foreach ( $webhook->headers ?? array() as $header ) {
			if ( isset( $header['name'] ) ) {
				$headers[ esc_html( $header['name'] ) ] = esc_html( $header['value'] );
			}
		}

		wp_remote_request(
			$webhook->url,
			array(
				'headers'  => $headers,
				'method'   => $webhook->method ?? 'POST',
				'blocking' => false,
				'body'     => json_encode(
					array(
						"$key" => $data['email'],
					)
				),
			)
		);
	}
}
