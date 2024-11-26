<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;
use Engage\JudicialWatch\Traits\RelatedPostsTrait;

class Post extends AbstractTemplate {
	use RelatedPostsTrait;

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

        $post = new JWPost(get_post($this->postId), true);
        $taxonomies = collect(wp_get_post_terms($this->postId, 'category'));
        $hasIssueTaxonomy = $taxonomies->isNotEmpty();
        $issueTaxonomyName = null;

        if ($hasIssueTaxonomy && $taxonomies->first()) {
            $issueTaxonomyName = $taxonomies->first()->name;
        }

        $videoLink   = get_field('video_link', $this->postId);
		$podcastLink = get_field('podcast_link', $this->postId);
		
		$iframeDetected = preg_match('/<iframe[^>]*src\s*=\s*[\'"]?https?:\/\/[^\s"\/]*youtube.com(?:\/[^\s"]*)?[\'"]?[^>]*>.*?<\/iframe>/', $post->content);

		$this->context = [

			// usually, when we're using out JWPost container, we don't need
			// the content of the post, just the metadata and excerpt.  but,
			// since the purpose of this template is to show the post itself,
			// we pass a Boolean true as the optional second argument to the
			// JWPost constructor.  that loads up the content so we can display
			// it on-screen.

            'hasIssueTaxonomy' => $hasIssueTaxonomy,
            'issueTaxonomyName' => $issueTaxonomyName,
            'videoLink' => $videoLink,
            'podcastLink' => $podcastLink,

			"post"    => $post,
			"iframeDetected" => $iframeDetected,
			"related" => $this->getRelatedPosts($this->postId, ["field_5bb68204a35a1"]),
			"social"  => [
				"tweet" => urlencode(sprintf("Judicial Watch - %s - %s", $post->title, $post->link)),
				"title" => urlencode($post->title),
				"link"  => urlencode($post->link),
			],
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