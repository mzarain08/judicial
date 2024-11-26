<?php

namespace Engage\JudicialWatch\Templates\Archives;

use Engage\JudicialWatch\Templates\AbstractTemplate;
use Engage\JudicialWatch\Containers\JWPost;

class Petitions extends AbstractTemplate
{
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
        $this->context["page"] = [
            'petitions' => $this->getPetitions(),
            'title' => 'Petitions'
        ];
    }

    /**
     * getPetitions
     *
     * Returns an array of featured petitions
     *
     * @return array
     */
    protected function getPetitions(): array
    {
        $posts = get_posts([
            'post_type'   => 'petitions',
            'numberposts' => -1
        ]);

        if (!is_array($posts) || !count($posts)) {
            return [];
        }

        $posts = array_map(function ($post) {
            $jwPost = new JWPost($post);
            $jwPost->content = get_field('description', $jwPost->postId);

            return $jwPost;
        }, $posts);

        return $posts;
    }

    /**
     * bodyClasses
     *
     * Returns an array of custom body classes for this template.
     *
     * @return array
     */
    protected function bodyClasses(): array
    {
        return ['single', 'single-post', 'post-template-default'];
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