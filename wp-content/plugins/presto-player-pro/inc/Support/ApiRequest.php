<?php

namespace PrestoPlayer\Pro\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A wrapper class for the http api to call an external api
 */
class ApiRequest {

	protected $api_key;
	protected $base_url = '';
	protected $key      = '';

	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	public function request( $endpoint, $args = array() ) {
		// make sure we send json
		if ( empty( $args['headers']['Content-Type'] ) ) {
			$args['headers']['Content-Type'] = 'application/json';
		}

		// parse args
		$args = wp_parse_args(
			$args,
			array(
				'timeout' => 10,
			)
		);

		// filter args and endpoint
		$args     = apply_filters( "presto_player_{$this->key}_request_args", $args, $endpoint );
		$endpoint = apply_filters( "presto_player_{$this->key}_request_endpoint", $endpoint, $args );

		// make url
		$url = trailingslashit( $this->base_url ) . untrailingslashit( $endpoint );

		// add query args
		if ( ! empty( $args['query'] ) ) {
			$url = add_query_arg( array_filter( $args['query'] ), $url );
			unset( $args['query'] );
		}

		// json encode body
		if ( ! empty( $args['body'] ) ) {
			if ( in_array( $args['headers']['Content-Type'], array( 'application/vnd.api+json', 'application/json' ) ) ) {
				$args['body'] = json_encode( $args['body'] );
			}
		}

		// make request
		$response      = wp_remote_request( esc_url_raw( $url ), $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// check for errors
		if ( ! in_array( $response_code, array( 200, 201 ) ) ) {
			$response_body_decoded = json_decode( $response_body );
			if ( is_array( $response_body_decoded ) && ! empty( $response_body_decoded['Message'] ) ) {
				return new \WP_Error( 'error', $response_body_decoded['Message'], array( 'status' => $response_code ) );
			}
			return new \WP_Error( 'error', esc_url( $url ) . ':  ' . $response_body, array( 'status' => $response_code ) );
		}

		// return response
		return apply_filters( "presto_player_{$this->key}_request_response", json_decode( $response_body ), $args, $endpoint );
	}

	public function get( $endpoint, $args = array() ) {
		$args['method'] = 'GET';
		return $this->request( $endpoint, $args );
	}

	public function post( $endpoint, $args = array() ) {
		$args['method'] = 'POST';
		return $this->request( $endpoint, $args );
	}

	public function put( $endpoint, $args = array() ) {
		$args['method'] = 'PUT';
		return $this->request( $endpoint, $args );
	}

	public function patch( $endpoint, $args = array() ) {
		$args['method'] = 'PATCH';
		return $this->request( $endpoint, $args );
	}

	public function delete( $endpoint, $args = array() ) {
		$args['method'] = 'DELETE';
		return $this->request( $endpoint, $args );
	}
}
