<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class Page extends AbstractTemplate {

	/**
	 * addToThisPageContext
	 *
	 * Adds data to the $context property that is, later, used to render
	 * a Twig template.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	protected function addToThisPageContext() {
	    $postId = $this->postId ?? null;
	    $page = null;
	    $backgroundImage = get_theme_file_uri('assets/images/section-title-bg-issues.jpg');

	    if ($postId) {
            $page = $this->transformPost(get_post($this->postId), true);
            $defaultImagePath = get_theme_file_uri('assets/images/section-title-bg-issues.jpg');
            $backgroundImage = get_field('page_header_background_image', $page->postId) ?: $defaultImagePath;
            if (is_array($backgroundImage)) {
                $backgroundImage = $backgroundImage['url'];
            }
        }

		$this->context = [
			'page' => $page,
            'backgroundImage' => $backgroundImage
		];
	}

	/**
	 * bodyClasses
	 *
	 * Returns an array of custom body classes for this template.
	 *
	 * @return array
	 */
	protected function bodyClasses(): array {
		return ['page', 'page-template-2col'];
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

		// TODO: this banner is in invision, not in the static-cut

		return false;
	}
}