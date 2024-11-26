<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class Petition extends AbstractTemplate {
	/**
	 * addToThisPageContext
	 *
	 * Adds data to the $context property that is, later, used to render
	 * a Twig template.
	 *
	 * @return void
	 */
	protected function addToThisPageContext() {
		$this->context['form'] = gravity_form(
		    get_field('petition_gravity_form', $this->postId), false, false, false, null, false, 1, false
        );

		$this->context['page'] = get_fields($this->postId);
        $backgroundImageUrl = get_field('layout_page_background_image', $this->postId);

        if ($backgroundImageUrl) {
            $this->context['page']['page_background_image'] = $backgroundImageUrl;
        }

        $this->context['page']['background_color'] = get_field('layout_page_background_color');

        if ('white' === get_field('layout_text_color', $this->postId)) {
            $this->context['page']['text_color'] = '#ffffff';
        } else {
            $this->context['page']['text_color'] = '#000000';
        }
	}

	/**
	 * getPetition
	 *
	 * Returns the necessary information from the database to load a
	 * petition on-screen.
	 *
	 * @return array
	 */
	protected function getPetition(): array {
		return [
			'type'   => 'petition',
			'layout' => get_field('layout', $this->postId),
			'media'  => get_field('media', $this->postId),
			'form'   => get_field('contact_information_form_id', $this->postId),
		];
	}

	/**
	 * getSurvey
	 *
	 * Returns the necessary information from the database to load a
	 * survey on-screen.
	 *
	 * @return array
	 */
	protected function getSurvey(): array {
		return [
			'type' => 'survey',

			// using the spread operator, we can use the array constant within
			// our theme object to return only the gravity form and not its
			// surrounding metadata.  the spear operator takes each member of
			// the array it precedes and 'submits' them as arguments to the
			// function called, in this case:  gravity_form().

			'form' => gravity_form(get_field('survey_form', $this->postId),
				...JudicialWatch::GF_RETURN_ONLY_FORM),
		];
	}

	/**
	 * bodyClasses
	 *
	 * Returns an array of custom body classes for this template.
	 *
	 * @return array
	 */
	public function bodyClasses(): array {

	    $classes = collect(['p-t-0']);

	    $typeField = get_field('type', $this->postId);

	    if ('petition' === $typeField) {
	        $classes->push('page-template-petition');

	        $layoutType = data_get(get_field('layout', $this->postId), 'type');

	        if ('row' === $layoutType) {
	            $classes->push('page-template-petition-2col');
            }
        } else {
	        $classes->push('page-template-survey');
        }



		return $classes->toArray();
	}

	/**
	 * withCorruptionBanner
	 *
	 * Returns true when the Fight Corruption! banner should be visible
	 * on this template.
	 *
	 * @return bool
	 */
	protected function withCorruptionBanner(): bool {
		return false;
	}

	/**
	 * withDaywatch
	 *
	 * Returns true when the Daywatch subscription banner should be
	 * visible on this template.
	 *
	 * @return bool
	 */
	protected function withDaywatchBanner(): bool {
		return false;
	}
}