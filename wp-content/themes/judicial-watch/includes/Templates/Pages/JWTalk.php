<?php

namespace Engage\JudicialWatch\Templates\Pages;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class JWTalk extends AbstractTemplate {

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
        $page = $this->transformPost(get_post($this->postId), true);

        // Page background image
        $defaultImagePath = get_theme_file_uri('assets/images/section-title-bg-jwtalk.jpg');
        $backgroundImage  = get_field('page_header_background_image', $page->postId) ?: $defaultImagePath;
        if (is_array($backgroundImage)) {
            $backgroundImage = $backgroundImage['url'];
        }

        $this->context = [
            'page' => $page,
            'backgroundImage' => $backgroundImage,
            'featuredPost'    => $this->getFeaturedPost(),
            'posts'           => $this->getPosts(),
            'morePostsGroups' => $this->getMorePosts(),
        ];
    }

    public function getMorePosts()
    {
        $posts = collect(get_posts([
            'numberposts' => -1,
            'post_type'   => 'podcasts',
            'post_status' => 'publish'
        ]));

        if ($posts->count()) {
            // Drop first 4; those are already shown
            $posts->shift(4);

            $posts->transform(function($post) {
                return $this->transformPost($post);
            });

            $posts = $posts->chunk(4);
        }

        return $posts;
    }

    public function getPosts()
    {
        $posts = get_posts([
            'numberposts' => 4,
            'post_type'   => 'podcasts',
            'post_status' => 'publish',
        ]);

        if (count($posts)) {
            $posts = array_map(function (\WP_Post $post) {
                return new JWPost($post);
            }, $posts);
        }

        return $posts;
    }

    public function getFeaturedPost()
    {
        $featuredPost = get_posts([
            'numberposts' => 1,
            'post_type'   => 'podcasts',
            'post_status' => 'publish'
        ]);

        if (count($featuredPost)) {
            $featuredPost = $this->transformPost($featuredPost[0]);
            $featuredPost->buzzsproutLink = get_field('buzzsprout_link', $featuredPost->postId);
            $featuredPost->itunesLink = get_field('podcast_itunes_link', $featuredPost->postId);
        } else {
            $featuredPost = null;
        }

        return $featuredPost;
    }

    /**
     * bodyClasses
     *
     * Returns an array of custom body classes for this template.
     *
     * @return array
     */
    protected function bodyClasses(): array {
        return ['page', 'page-jwtalk'];
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