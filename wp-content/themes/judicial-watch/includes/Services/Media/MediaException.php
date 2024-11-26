<?php

namespace Engage\JudicialWatch\Services\Media;

use Dashifen\Exception\Exception;

class MediaException extends Exception {
	const FETCH_FAILED = 1;
	const IMPORT_FAILED = 2;
	const TERM_CREATION_FAILED = 3;
	const DUPLICATE_ID = 4;
}