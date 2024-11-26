<?php

namespace PrestoPlayer\Pro\Services\Bunny\Storage;

/**
 * An exception thrown by BunnyCDNStorage caused by authentication failure
 */
class BunnyStorageAuthenticationException extends BunnyStorageException {

	public function __construct( $storage_zone_name, $access_key, $code = 0, \Exception $previous = null ) {
		parent::__construct( "Authentication failed for storage zone '{$storage_zone_name}' with access key '{$access_key}'.", $code, $previous );
	}

	public function __toString() {
		return __CLASS__ . ": {$this->message}\n";
	}
}
