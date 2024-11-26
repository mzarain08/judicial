<?php

namespace Dashifen\Exception;

use Throwable;
use ReflectionClass;
use ReflectionException;

class Exception extends \Exception {
	const UNKNOWN_ERROR = 0;

	/**
	 * Exception constructor
	 *
	 * Ensures that the $code parameter is this object's UNKNOWN_ERROR or
	 * on of its children's other constants.
	 *
	 * @param string         $message
	 * @param int            $code
	 * @param Throwable|null $previous
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = null) {
		parent::__construct($message, $this->setCode($code), $previous);
	}

	/**
	 * setCode
	 *
	 * Either returns $code unchanged, or sets it to $default if it is not
	 * found within this object's (or one of its children's) constants.
	 *
	 * @param int $code
	 * @param int $default
	 *
	 * @return int
	 */
	protected function setCode(int $code, int $default = self::UNKNOWN_ERROR): int {
		$returnValue = $default;

		try {
			$constants = (new ReflectionClass($this))->getConstants();

			// if our $code is in the array of this exception's constants, then
			// we'll return it.  when it's not, we reset it to the default.  the
			// goal is to provide a list of error codes that can be used in catch
			// blocks -- usually with switch statements -- to differentiate
			// between the same type of exception with differing messages.

			$returnValue = in_array($code, $constants) ? $code : $default;
		} catch (ReflectionException $exception) {

			// a ReflectionException is thrown when the class that we're
			// trying to reflect doesn't exist.  but, since we're reflecting
			// this class, we know it exists.  in order to avoid IDE related
			// messages about uncaught exceptions, we'll trigger the following
			// error, but we also know that we should never get here.

			trigger_error("Unable to reflect.", E_ERROR);
		}

		return $returnValue;
	}
}
