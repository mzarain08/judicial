<?php

namespace Engage\JudicialWatch\Templates\Singular;

use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class Document extends AbstractTemplate
{
    /**
     * bodyClasses
     *
     * Returns an array of custom body classes for this template.
     *
     * @return array
     */
    public function bodyClasses(): array
    {
        return ['page-single-document'];
    }

    /**
     * addToThisPageContext
     *
     * Adds data to the $context property that is, later, used to render
     * a Twig template.
     *
     * @return void
     */
    protected function addToThisPageContext()
    {

        $file = get_field('attachment', $this->postId);
        $post = new JWPost(get_post($this->postId), true);
        $pdfImage = get_post_meta($post->postId, '_document_cover_image');

        if (is_countable($pdfImage) && count($pdfImage)) {
            $post->thumbnails['pdf'] = $pdfImage[0];
        }

		$coverImage = get_field('document_image', $post->postId);
		
		if (is_countable($coverImage) && count($coverImage)) {
			$post->thumbnails['coverimage'] = $coverImage['url'];
		}

        $documentCategories = collect(get_the_terms($post->postId, 'document_categories'));
        if ($documentCategories->count()) {
            $documentTerm = $documentCategories->first();
            $post->source = [
                'id' => '',
                'name' => $documentTerm->name,
                'link' => get_term_link($documentTerm->term_id, 'document_categories')
            ];
        }

        $documentTags = collect(get_the_terms($post->postId, 'document_tags'));
        if ($documentTags->count() && $documentTags->first()) {
            $documentTerms = $documentTags->take(3);
            $post->tagged = $documentTerms->transform(function(\WP_Term $documentTerm) {
                return (object)[
                    'id' => '',
                    'name' => $documentTerm->name,
                    'link' => get_term_link($documentTerm->term_id, 'document_tags')
                ];
            });
        }

        $pageData = [
            'fileUpload' => $file,
            'post'       => $post,
            'relatedPosts' => $this->getRelatedPosts(),
        ];

        $this->context = $pageData;
    }

    public function getRelatedPosts()
    {
        $relatedPosts = collect(get_field('related_posts', $this->postId))
            ->reject(function($postObj) {
                if (!$postObj) {
                    return true;
                }
            })
            ->transform(function($postObj) {
                return $this->transformPost($postObj);
            });

        if ($relatedPosts->count() < 3) {
            $postsToLoad = 3 - $relatedPosts->count();

            $extraRelatedPosts = collect(get_posts([
                    'post_type'   => 'documents',
                    'numberposts' => $postsToLoad
                ]))
                ->transform(function($postObj) {
                    return $this->transformPost($postObj);
                });

            $relatedPosts = $relatedPosts->concat($extraRelatedPosts);
        }

        $relatedPosts->transform(function($postObj) {
            $pdfImage = get_post_meta($postObj->postId, '_document_cover_image');

            if (is_countable($pdfImage) && count($pdfImage)) {
                $postObj->thumbnails['medium_large']->src = $pdfImage[0];
            }

            return $postObj;
        });

        return $relatedPosts;
    }

    /**
     * withCorruptionBanner
     *
     * Returns true when the Fight Corruption! banner should be visible
     * on this template.
     *
     * @return bool
     */
    protected function withCorruptionBanner(): bool
    {
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
    protected function withDaywatchBanner(): bool
    {
        return false;
    }
}