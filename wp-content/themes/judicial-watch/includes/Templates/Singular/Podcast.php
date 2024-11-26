<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;
use Engage\JudicialWatch\Traits\RelatedPostsTrait;

class Podcast extends AbstractTemplate {


	/**
	 * addToThisPageContext
	 *
	 * Adds data to the $context property that is, later, used to render
	 * a Twig template.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	protected function addToThisPageContext() {;
	    $post = new JWPost(get_post($this->postId), true);

		$this->context = [
			'post' => $post,
            'buzzsproutLink' => get_field('buzzsprout_link', $post->postId),
            'itunesLink' => get_field('podcast_itunes_link', $post->postId),
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
		return [];
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