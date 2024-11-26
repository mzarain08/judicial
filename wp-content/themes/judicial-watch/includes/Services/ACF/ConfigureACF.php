<?php

namespace Engage\JudicialWatch\Services\ACF;

use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Hooks\HookException;

class ConfigureACF extends AbstractHandler {
	/**
	 * initialize
	 *
	 * This method hooks this object and others into the WordPress action
	 * and filter API.
	 *
	 * @return void
	 * @throws HookException
	 */
	public function initialize() {
		$this->addAction("acf/init", "addOptionPages");
		(new MainMenu())->initialize();

		// Dont scroll page on submit
        add_filter('gform_confirmation_anchor', '__return_false');

		$this->loadSpecialReportsAjax();
		$this->loadPetitionsAjax();
	}

	/**
	 * addOptionPages
	 *
	 * Adds ACF option pages to the admin menu.
	 *
	 * @return void
	 */
	protected function addOptionPages() {
		acf_add_options_page([
			'page_title'  => 'JW Settings',
			'parent_slug' => 'options-general.php',
		]);
	}

    public function loadPetitionsAjax()
    {
        add_filter('acf/load_field/name=petition_gravity_form', function($field) {

            $formOptions = collect(\GFAPI::get_forms())
                ->mapWithKeys(function($formOptions) {
                    return [$formOptions['id'] => $formOptions['title']];
                })
                ->sort()
                ->toArray();

            $field['choices'] = $formOptions;

            return $field;
        });
    }

	public function loadSpecialReportsAjax()
    {
        add_filter('acf/load_field/name=special_report_gravity_form', function($field) {

            $formOptions = collect(\GFAPI::get_forms())
                ->reject(function($formOptions) {
                    if (!str_contains(strtolower($formOptions['title']), 'special report')) {
                        return true;
                    }
                })
                ->mapWithKeys(function($formOptions) {
                    return [$formOptions['id'] => $formOptions['title']];
                })
                ->sort()
                ->toArray();


            $field['choices'] = $formOptions;

            return $field;
        });
    }
}