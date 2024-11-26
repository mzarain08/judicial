<?php

namespace Engage\JudicialWatch\Templates;

use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\Singular\Video;
use Engage\JudicialWatch\Traits\RelatedPostsTrait;
use WP_Term;

class Homepage extends AbstractTemplate {
	use RelatedPostsTrait;

	/**
	 * addToThisPageContext
	 *
	 * Adds the necessary information for the display of our Homepage to
	 * the $context property.
	 */
	protected function addToThisPageContext() {
        // $cachedPageData = get_transient('jw_homepage_page_data');
		$cachedPageData =false;

        if (!$cachedPageData) {
            $cachedPageData = [
                'readNow' => [
                    'archives' => $this->getReadNowCategories(),
                    'posts'    => $this->getReadNowSliderPosts(),
                ],

                'watchNow'    => $this->getWatchNow(),
                'signupForm'  => get_field('homepage_signup_form', $this->postId),
                'petitions'   => $this->getPetitions(),
                'latestNews'  => $this->getLatestNews()
            ];

            set_transient('jw_homepage_page_data', $cachedPageData);
        }

		$this->context['page'] = $cachedPageData;

        return $this;
	}

	protected function getReadNowSliderPosts()
    {
        $selectedCategories = collect(get_field('read_now_slider_post_categories', $this->postId));
        //print_R($selectedCategories);print_R($this->postId);die();
        $tabs = collect();

        // Add Breaking News first, always
        $breakingNewsPosts = collect(array_map([$this, 'transformPost'], get_posts([
            'numberposts' => 9,
            'category__not_in' => get_cat_ID('In The News')
        ])));
        $tabs->put('Breaking News', $breakingNewsPosts);

        // Add any other selected cateogries
        if ($selectedCategories->count()) {
            $selectedCategories->each(function($selectedTaxonomy) use ($tabs) {
                $posts = array_map([$this, 'transformPost'], get_posts([
                    'numberposts' => 9,
                    'tax_query' => [
                        [
                            'taxonomy' => $selectedTaxonomy->taxonomy,
                            'field' => 'id',
                            'terms' => $selectedTaxonomy->term_id,
                            'include_children' => false
                        ]
                    ]
                ]));

                $tabs = $tabs->put($selectedTaxonomy->name, collect($posts));
            });
        }

        return $tabs;
    }

	/**
	 * getReadNowCategories
	 *
	 * Gets the categories selected by the editors for the Read Now band on
	 * the homepage.
	 *
	 * @return array
	 */
	protected function getReadNowCategories(): array
    {

		// there are three category choices on the homepage and then we add
		// a link to and an image for the Verdict newsletter.  we'll do the
		// categories first with a loop.

		$categories = [];
		for ($i = 1; $i <= 3; $i++) {
			$category = get_field("read_now_category_" . $i, $this->postId);

			if ($category instanceof WP_Term) {
				$category->image = get_field("category_image", "term_$category->term_id");
				$category->link = get_term_link($category);
			}

			$categories[] = $category;
		}

        $readNow = collect(get_field("read_now", $this->postId));

		$pageLink = data_get($readNow, 'the_verdict.the_verdict_link');
		if (!isset($pageLink['title'])) {
		    $pageLink['title'] = 'Untitled';
        }

		return [
			"categories" => $categories,
			"theVerdict" => [
				"image" => data_get($readNow, 'the_verdict.the_verdict_image'),
				"link"  => data_get($readNow, 'the_verdict.the_verdict_link'),
			],
		];
	}

	/**
	 * getLatest
	 *
	 * Identifies the most recent posts on the site and returns them as
	 * JWPost objects.
	 *
	 * @return array
	 */
	protected function getLatest(): array {

		// our parent object has a transformPost method that takes WP_Posts
		// and turns them into JWPosts instead.  we'll call get_posts to get
		// the most recent set of news, and then use array_map() to send
		// that list through said transform.

		$posts = array_map([$this, "transformPost"], get_posts([
			"numberposts" => get_option("posts_per_page"),
		]));

		return $posts;
	}

    /**
     * Get Latest News
     *
     * Gets the latest news, press releases, and the featured news post for the homepage.
     *
     * @return array
     */
	protected function getLatestNews() : array {

	    $homeConfig = collect(get_field('top_box', $this->postId));
	    $featuredPost = collect($homeConfig->get('featured_news_post_n'))->first();

	    if (!$featuredPost) {
	        $firstPost = collect(get_posts([
                    'numberposts' => 1,
                    'category' => get_cat_ID('In The News')
                ]))
                ->first();

	       $featuredPost = $this->transformPost($firstPost);
        } else {
	        $featuredPost = $this->transformPost($featuredPost);
        }

	    $breakingNewsPosts = collect($homeConfig->get('breaking_news'))
            ->filter(function($post) {
                if (empty($post)) {
                    return;
                }

                return true;
            })
        ->transform(function($post) {
            return $this->transformPost($post);
        });
	    if ($breakingNewsPosts->count() < 4) {
            $moreBreakingNewsPosts = collect(array_map([$this, 'transformPost'], get_posts([
                'numberposts' => 4 - $breakingNewsPosts->count(),
                'category__not_in' => get_cat_ID('In The News'),
                'exclude' => [$featuredPost->postId]

            ])));
            $breakingNewsPosts = $breakingNewsPosts->concat($moreBreakingNewsPosts);
        }

        $inTheNewsPosts = collect(array_map([$this, 'transformPost'], get_posts([
            'numberposts' => 4,
            'category'    => get_cat_ID('In The News'),
            'exclude' => [$featuredPost->postId]
        ])));

	    // Reformat titles
        $breakingNewsPosts->transform(function($post) {
			$post->title = wp_trim_words($post->title, 10);
			return $post;
		});

		$inTheNewsPosts->transform(function($post) {
			$post->title = wp_trim_words($post->title, 10);
			return $post;
		});

	    return [
	        'inTheNews'    => $inTheNewsPosts,
            'breakingNews' => $breakingNewsPosts,
            'featured'     => $featuredPost
        ];
    }

	/**
	 * getPetitions
	 *
	 * Returns an array of featured petitions
	 *
	 * @return array
	 */
	protected function getPetitions(): array {
		$posts = get_field('featured_petitions', $this->postId);

        if (!is_array($posts) || !count($posts)) {
            return [];
        }

		if (count($posts) % 2 === 1) {
			array_pop($posts);
		}

		$posts = array_map(function ($post) {
			return new JWPost($post);
		}, $posts);

		return $posts;
	}

	/**
	 * getWatchNow
	 *
	 * Returns an array of JWPosts to use as the videos in the homepage's
	 * watch now region.
	 *
	 * @return array
	 */
	protected function getWatchNow(): array {
		$videoIds = get_field('featured_videos', $this->postId);

		$videos = collect($videoIds)
            ->transform(function($videoId) {
                $video = $this->transformPost(get_post($videoId));

                if (empty($video->videoId) && isset($video->postId)) {
                    $video->videoId = get_field('video_id', $video->postId);
                }

                return $video;
            });

		$featuredVideo = $videos->shift();
		$videos = $videos->toArray();

		return [
			'featured' => $featuredVideo,
			'videos'   => $videos,
		];
	}

	/**
	 * title
	 *
	 * Returns the <title> for this template.
	 *
	 * @return string
	 */
	public function title(): string {
		return "Home";
	}

	/**
	 * bodyClasses
	 *
	 * Returns an array of custom body classes for this template.
	 *
	 * @return array
	 */
	public function bodyClasses(): array {
		return ["page"];
	}

	/**
	 * withCorruptionBanner
	 *
	 * Returns true when the Fight Corruption! banner should be visible
	 * on this template.
	 *
	 * @return bool
	 */
	public function withCorruptionBanner(): bool {
		return true;
	}

	/**
	 * withDaywatch
	 *
	 * Returns true when the Daywatch subscription banner should be
	 * visible on this template.
	 *
	 * @return bool
	 */
	public function withDaywatchBanner(): bool {
		return true;
	}
}
