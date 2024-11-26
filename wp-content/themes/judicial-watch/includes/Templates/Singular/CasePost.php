<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;
use Engage\JudicialWatch\Traits\RelatedPostsTrait;

class CasePost extends AbstractTemplate {
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
		$this->context = [
			'post'    => ($post = new JWPost(get_post($this->postId), true)),
			'related' => $this->getRelatedPosts($this->postId, ['field_5bb68204a35a1']),
            'featuredDocument' => $this->getFeaturedDoc($this->postId),
            'relatedDocuments' => $this->getRelatedDocuments($this->postId),
            'relatedCases' => $this->getRelatedCases($this->postId),
			'social'  => [
				'tweet' => urlencode(sprintf('Judicial Watch - %s - %s', $post->title, $post->link)),
				'title' => urlencode($post->title),
				'link'  => urlencode($post->link),
			],
		];
	}

	protected function getRelatedCases($postId)
    {
        $relatedPosts = get_field('related_cases', $postId);

        if (!$relatedPosts) {
            return null;
        }

        $posts = array_map(function($post) {
            $jwPost = $this->transformPost($post);

            // Categories
            $categories = wp_get_post_terms($jwPost->postId, 'document_categories');
            if (count($categories)) {
                $jwPost->setCategory([
                    'name' => $categories[0]->name,
                    'link' => get_term_link($categories[0])
                ]);
            }

            return $jwPost;
        }, $relatedPosts);

        return $posts;
    }

	protected function getRelatedDocuments($postId)
    {
        $relatedDocuments = get_field('related_documents', $postId);

        if (!$relatedDocuments) {
            return null;
        }

        $posts = array_map(function($post) {
            $jwPost = $this->transformPost($post);

            // Categories
            $categories = wp_get_post_terms($jwPost->postId, 'document_categories');
            if (count($categories)) {
                $jwPost->setCategory([
                    'name' => $categories[0]->name,
                    'link' => get_term_link($categories[0])
                ]);
            }

            // Tags
            $tags = wp_get_post_terms($jwPost->postId, 'document_tags');
            if (count($tags)) {
                $jwPost->tags = [];

                foreach ($tags as $tag) {
                    $jwPost->tags[] = [
                        'name' => $tag->name,
                        'link' => get_term_link($tag)
                    ];

                }
            }

            return $jwPost;
        }, $relatedDocuments);

        return $posts;
    }

	protected function getFeaturedDoc($postId)
    {
        $featuredDoc = get_field('case_document', $postId);

        if (!$featuredDoc instanceof \WP_Post) {
           return null;
        }

        $jwPost = $this->transformPost($featuredDoc);

        // Categories
        $categories = wp_get_post_terms($jwPost->postId, 'document_categories');
        if (count($categories)) {
            $jwPost->setCategory([
                'name' => $categories[0]->name,
                'link' => get_term_link($categories[0])
            ]);
        }

        // Tags
        $tags = wp_get_post_terms($jwPost->postId, 'document_tags');
        if (count($tags)) {
            $jwPost->tags = [];

            foreach ($tags as $tag) {
                $jwPost->tags[] = [
                    'name' => $tag->name,
                    'link' => get_term_link($tag)
                ];

            }
        }

        // Download
        $attachment = get_field('attachment', $jwPost->postId);
        if ($attachment) {
            $jwPost->attachment_url = $attachment['url'];
        }

        return $jwPost;
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