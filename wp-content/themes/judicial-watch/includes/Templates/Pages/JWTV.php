<?php

namespace Engage\JudicialWatch\Templates\Pages;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\AbstractTemplate;

class JWTV extends AbstractTemplate {

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
        $featuredPost = $this->getFeaturedPost();

        // Page background image
        $defaultImagePath = get_theme_file_uri('assets/images/body-bg-jwtv.jpg');
        $backgroundImage  = data_get(get_field('jwtv_page_background_image', $this->postId), 'url');
        $youtube_videoId = get_field('video_id', $this->postId);

        if (!$backgroundImage) {
            $backgroundImage = data_get($featuredPost, 'thumbnails.large.src', $defaultImagePath);
        }
        $YouTube = get_field('video_id', $featuredPost->postId);
        $postGroups = $this->getPostsGroupedByTaxonomy();
        $fixedPostGroup = array_slice($postGroups, 0, 3);

        $postGroupsForShuffle = array_slice($postGroups, 3);
        shuffle($postGroupsForShuffle);

        $shuffledPostGroup = array_merge($fixedPostGroup,$postGroupsForShuffle);

        $this->context = [
            'page'            => $page,
            'groupedPosts'    => $postGroups,
            'shuffledPostGroup' => $shuffledPostGroup,
            'featuredBackgroundImage' => $backgroundImage,
            'featuredPost'    => $featuredPost,
            'isDefaultBgImage' => ($defaultImagePath === $backgroundImage),
            //'YouTubeID' => get_field('video_id', $featuredPost->postId)
            'youtube_videoId' => $youtube_videoId,


        ];

        data_set($this->context, 'social', [
            'tweet' => urlencode('Judicial Watch - JWTV'),
            'title' => urlencode('Judicial Watch - JWTV'),
            'link'  => urlencode(get_permalink($this->postId)),
        ]);
    }

    public function getPostsGroupedByTaxonomy()
    {
        $posts = [];

        $featuredPlaylists = collect(get_field('featured_jwtv_categories', $this->postId));

        foreach($featuredPlaylists as $taxonomyTerm) {
            $taxPosts = get_posts(array(
                'post_type' => 'videos',
                'numberposts' => 12,
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomyTerm->taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $taxonomyTerm->term_id,
                        'include_children' => true,


                    )
                )
            ));

            $jwTaxPosts = array_map(function ($post) {
                // Get the video_id ACF field
                $videoId = get_field('video_id', $post->ID);

                return (object) [
                    'post' => $this->transformPost($post),
                    'video_id' => $videoId // Include video_id in the returned object
                ];
            }, $taxPosts);
            data_set($posts, $taxonomyTerm->name, (object)[
                'posts' => $jwTaxPosts,
                'name'  => $taxonomyTerm->name,
                'link'  => get_term_link($taxonomyTerm->term_id, 'playlists')
            ]);
        }
        return $posts;
    }

    public function getFeaturedPost()
    {

        $featuredPost = get_field('featured_video');
        $featuredPostCaption = get_field('featured_video_caption');

        if (!$featuredPost) {
            $featuredPost = get_posts([
                'numberposts' => 1,
                'post_type'   => 'videos',
                'post_status' => 'publish'
            ]);
        }

        if (count($featuredPost)) {
            $featuredPost = $this->transformPost($featuredPost[0]);

            // Get terms
            $terms = get_the_terms($featuredPost->postId, 'playlists');
            if (is_countable($terms) && count($terms)) {
                $featuredPost->setCategory([
                    'name' => $terms[0]->name,
                    'link' => get_term_link($terms[0])
                ]);
            }

            // Get video id
            $videoId = get_field('video_id', $featuredPost->postId);
            if ($videoId) {
                $featuredPost->video_id = $videoId;
            }

        } else {
            $featuredPost = null;
        }

        if ($featuredPostCaption) {
            $featuredPost->caption = $featuredPostCaption;
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
        return ['page has-background-black page-jwtv-index page-template-jwtv'];
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