<?php

namespace Engage\JudicialWatch\Containers;

use Engage\JudicialWatch\Containers\Container;
use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Services\Deployer\ContactInfoFormException;

/**
 * Class ContactInfoFormTemplate
 * @package Engage\JudicialWatch\Containers
 * @property-read $template
 * @property-read $values
 */
class ContactInfoFormTemplate extends Container {
	/**
	 * @var string
	 */
	protected $templateFile = "";

	/**
	 * @var int
	 */
	protected $values = 0;

	/**
	 * ContactInfoFormTemplate constructor.
	 *
	 * @param string $template
	 * @param int    $values
	 *
	 * @throws ContainerException
	 */
	public function __construct(string $template, int $values) {
		parent::__construct([
			"template" => $template,
			"values"   => $values,
		]);
	}


	/**
	 * setTemplateFile
	 *
	 * Sets the $templateFile property.
	 *
	 * @param string $templateFile
	 *
	 * @throws ContactInfoFormException
	 */
	public function setTemplateFile(string $templateFile): void {

		// the only thing to check for here is that $template is a file
		// in the right folder.

		$file = sprintf("%s/templates/contact-info-forms/%s", get_stylesheet_directory(), $templateFile);

		if (!is_file($file)) {
			throw new ContactInfoFormException("Unknown template file: $templateFile.", ContactInfoFormException::UNKNOWN_TEMPLATE);
		}

		$this->template = $templateFile;
	}

	/**
	 * setValues
	 *
	 * Sets the $values property.
	 *
	 * @param int $values
	 *
	 * @throws ContactInfoFormException
	 */
	public function setValues(int $values): void {
		if ($values < 0) {
			throw new ContactInfoFormException("Invalid value count: $values.", ContactInfoFormException::INVALID_VALUE_COUNT);
		}

		$this->values = $values;
	}


}