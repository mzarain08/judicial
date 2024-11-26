<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;
use Engage\JudicialWatch\Traits\RelatedPostsTrait;

class Video extends AbstractTemplate {
	use RelatedPostsTrait;

    public function getVideoBackgroundImage(JWPost $post)
    {
        // First priority is the background image field
        $backgroundImage = get_field('page_background_image', $post->postId);
        if ($backgroundImage && isset($backgroundImage['url'])) {
            return $backgroundImage['url'];
        }

        // Default to featured image if set
        $thumbnail = data_get($post, 'thumbnails.large.src');
        if ($thumbnail) {
            return $thumbnail;
        }

        // return no default if nothing set
        return null;
    }

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
	    $post = ($post = new JWPost(get_post($this->postId), true));
	    $relatedPosts = $this->getRelatedPosts($this->postId);
	    $youtubeId = get_field('video_id', $this->postId);

	    $post->category = $this->getCategory();

		$this->context = [
			'post'    => $post,
            'relatedPosts' => $relatedPosts,
            'youtubeId' => $youtubeId,
            'backgroundImage' => $this->getVideoBackgroundImage($post),
            'social'  => [
                'tweet' => urlencode(sprintf("Judicial Watch - %s - %s", $post->title, $post->link)),
                'title' => urlencode($post->title),
                'link'  => urlencode($post->link),
            ],
		];
	}

	public function getCategory()
    {
        $categories = collect(wp_get_post_terms($this->postId, 'playlists'));

        if ($categories->count()) {
            $featuredCategory = $categories->first();

            return [
                'name' => $featuredCategory->name,
                'link' => get_term_link($featuredCategory),
                'id' => $featuredCategory->term_id
            ];
        }

        return null;
    }

	protected function getRelatedPosts($postId)
    {
        $relatedPosts = get_field('related_videos', $postId);

        if (!$relatedPosts) {
            $relatedPosts = get_posts([
                'post_type' => 'videos',
                'numberposts' => 6
            ]);
        }

        $posts = array_map(function($post) {
            $jwPost = $this->transformPost($post);

            return $jwPost;
        }, $relatedPosts);

        return $posts;
    }

	/**
	 * bodyClasses
	 *
	 * Returns an array of custom body classes for this template.
	 *
	 * @return array
	 */
	protected function bodyClasses(): array {
		return ['page has-background-black page-jwtv-player page-template-jwtv'];
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