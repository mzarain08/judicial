<?php

namespace PrestoPlayer\Pro\Services\Bunny\Storage;

class StorageAPI {

	/**
	 * The name of the storage zone we are working on
	 */
	public $storageZoneName = '';

	/**
	 * The API access key used for authentication
	 */
	public $apiAccessKey = '';

	/**
	 * The storage zone region code
	 */
	private $storageZoneRegion = 'de';

	/**
	 * Initializes a new instance of the BunnyCDNStorage class
	 */
	public function __construct( $storageZoneName, $apiAccessKey, $storageZoneRegion = 'de' ) {
		$this->storageZoneName   = $storageZoneName;
		$this->apiAccessKey      = $apiAccessKey;
		$this->storageZoneRegion = strtolower( $storageZoneRegion );
	}

	/*
		Returns the base URL with the endpoint based on the current storage zone region
	*/
	private function getBaseUrl() {
		if ( 'de' == $this->storageZoneRegion || '' == $this->storageZoneRegion ) {
			return 'https://storage.bunnycdn.com/';
		} else {
			return "https://{$this->storageZoneRegion}.storage.bunnycdn.com/";
		}
	}

	/**
	* Get the list of storage objects on the given path
	 */
	public function getStorageObjects( $path ) {
		$normalizedPath = $this->normalizePath( $path, true );
		return json_decode( $this->sendHttpRequest( $normalizedPath ) );
	}

	/**
	* Delete an object at the given path. If the object is a directory, the contents will also be deleted.
	 */
	public function deleteObject( $path ) {
		$normalizedPath = $this->normalizePath( $path );
		return $this->sendHttpRequest( $normalizedPath, 'DELETE' );
	}

	/**
	* Upload a local file to the storage
	 */
	public function uploadFile( $localPath, $path ) {
		// Open the local file
		$fileStream = fopen( $localPath, 'r' );
		if ( false == $fileStream ) {
			throw new BunnyStorageException( 'The local file could not be opened.' );
		}
		$dataLength     = filesize( $localPath );
		$normalizedPath = $this->normalizePath( $path );
		return $this->sendHttpRequest( $normalizedPath, 'PUT', $fileStream, $dataLength );
	}

	/**
	* Download the object to a local file
	 */
	public function downloadFile( $path, $localPath ) {
		// Open the local file
		$fileStream = fopen( $localPath, 'w+' );
		if ( false == $fileStream ) {
			throw new BunnyStorageException( 'The local file could not be opened for writing.' );
		}

		$dataLength     = filesize( $localPath );
		$normalizedPath = $this->normalizePath( $path );
		return $this->sendHttpRequest( $normalizedPath, 'GET', null, null, $fileStream );
	}

	private function sendHttpRequest( $url, $method = 'GET', $uploadFile = null, $uploadFileSize = null, $downloadFileHandler = null ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->getBaseUrl() . $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );
		curl_setopt( $ch, CURLOPT_FAILONERROR, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				"AccessKey: {$this->apiAccessKey}",
			)
		);
		if ( 'PUT' == $method && null != $uploadFile ) {
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_UPLOAD, 1 );
			curl_setopt( $ch, CURLOPT_INFILE, $uploadFile );
			curl_setopt( $ch, CURLOPT_INFILESIZE, $uploadFileSize );
		} elseif ( 'GET' != $method ) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		}


		if ( 'GET' == $method && null != $downloadFileHandler ) {
			curl_setopt( $ch, CURLOPT_FILE, $downloadFileHandler );
		}


		$output       = curl_exec( $ch );
		$curlError    = curl_errno( $ch );
		$responseCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $curlError ) {
			throw new BunnyStorageException( 'An unknown error has occured during the request. Status code: ' . $curlError );
		}

		if ( 404 == $responseCode ) {
			throw new BunnyStorageFileNotFoundException( $url );
		} elseif ( 401 == $responseCode ) {
			throw new BunnyStorageAuthenticationException( $this->storageZoneName, $this->apiAccessKey );
		} elseif ( $responseCode < 200 || $responseCode > 299 ) {
			throw new BunnyStorageException( 'An unknown error has occured during the request. Status code: ' . $responseCode );
		}

		return $output;
	}

	/**
	* Normalize a path string
	 */
	private function normalizePath( $path, $isDirectory = null ) {
		if ( ! $this->startsWith( $path, "/{$this->storageZoneName}/" ) && ! $this->startsWith( $path, "{$this->storageZoneName}/" ) ) {
			throw new BunnyStorageException( "Path validation failed. File path must begin with /{$this->storageZoneName}/" );
		}

		$path = str_replace( '\\', '/', $path );
		if ( null != $isDirectory ) {
			if ( $isDirectory ) {
				if ( ! $this->endsWith( $path, '/' ) ) {
					$path = $path . '/';
				}
			} else {
				if ( $this->endsWith( $path, '/' ) && '/' != $path ) {
					throw new BunnyStorageException( 'The requested path is invalid.' );
				}
			}
		}

		// Remove double slashes
		while ( strpos( $path, '//' ) !== false ) {
			$path = str_replace( '//', '/', $path );
		}

		// Remove the starting slash
		if ( substr( $path, 0, 1 ) === '/' ) {
			$path = substr( $path, 1 );
		}

		return $path;
	}

	private function startsWith( $haystack, $needle ) {
		$length = strlen( $needle );
		return ( substr( $haystack, 0, $length ) === $needle );
	}

	private function endsWith( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( 0 == $length ) {
			return true;
		}

		return ( substr( $haystack, -$length ) === $needle );
	}
}
