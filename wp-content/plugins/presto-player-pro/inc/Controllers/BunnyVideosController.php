<?php

namespace PrestoPlayer\Pro\Controllers;

use PrestoPlayer\Pro\Models\Bunny\Video;
use PrestoPlayer\Pro\Support\AbstractBunnyStreamController;

class BunnyVideosController extends AbstractBunnyStreamController {

	protected $endpoint = 'videos';
	protected $model    = Video::class;

	/**
	 * Upload a video to stream
	 *
	 * @return void
	 */
	public function upload( $path, $args = array() ) {
		if ( ! $path ) {
			return new \WP_Error( 'file_missing', 'You must have a file to upload' );
		}
		if ( empty( $args['guid'] ) ) {
			return new \WP_Error( 'guid_missing', 'You must select a video guid.' );
		}

		$curl   = curl_init();
		$stream = fopen( $path, 'r' );

		$headers = array(
			"AccessKey: {$this->api_key}",
			'Content-Type: application/octet-stream', // or whatever you want
		);

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => "https://video.bunnycdn.com/library/$this->library_id/videos/{$args['guid']}",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_PUT            => true,
				CURLOPT_HTTPHEADER     => $headers,
				CURLOPT_INFILE         => $stream,
				CURLOPT_INFILESIZE     => filesize( $path ),
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			)
		);

		$response     = curl_exec( $curl );
		$curl_error   = curl_errno( $curl );
		$responseCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );
		fclose( $stream );
		@unlink( $path );

		if ( $curl_error ) {
			return new \WP_Error( "error_$curl_error", 'An unknown error has occured during the request.' );
		}

		if ( 404 == $responseCode ) {
			return new \WP_Error( 'not_found', 'The url was not found.' );
		} elseif ( 401 == $responseCode ) {
			return new \WP_Error( 'not_authenticated', 'The API key was incorrect.' );
		} elseif ( $responseCode < 200 || $responseCode > 299 ) {
			return new \WP_Error( 'error', 'An unknown error has occured during the request. Status code: ' . $responseCode );
		}


		return $response;
	}

	public function create( $args ) {
		if ( empty( $args['title'] ) ) {
			return new \WP_Error( 'missing_title', 'You must provide a title' );
		}
		return parent::create( $args );
	}
}
