<?php

namespace Engage\JudicialWatch\Templates\Pages;

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\AbstractTemplate;
use Engage\JudicialWatch\Containers\JWCategory;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\Singular\Post;
use Illuminate\Support\Collection;
use WP_Term;
use WP_Post;

class PressRoom extends AbstractTemplate {
    public $queriedObject;

	/**
	 * addToThisPageContext
	 *
	 * Adds data to the $context property that is, later, used to render
	 * a Twig template.
	 *
	 * @return void
	 */
	protected function addToThisPageContext() {
        add_filter('query_vars', [$this, 'registerQueryVars']);

        $this->queriedObject = get_queried_object();
        $posts = collect($this->getPosts());

        $title = $this->getTheTitle();
        $post = new JWPost(get_post($this->postId), true);
        if (isset($post->title) && $post->title != '') {
            $title = $post->title;
        }
		
        $this->context = [
            'page' => [
                'isPosts'    => !$this->isTaxonomyArchive() && get_post_type() === 'post',
                'posts'      => $posts,
                'pagination' => $this->getPagination(),
                'title'      => $title,
                'content'    => $post->content,
            ],
            'latestVideos'    => $this->getLatestVideos(),
            'latestDocuments'    => $this->getLatestDocuments(),
            'latestDockets'    => $this->getLatestDockets()
        ];
	}

	public function getLatestVideos()
    {
        $latestVideos = collect(get_posts([
            'orderby'        => 'date',
            'numberposts' => 5,
            'post_type'   => 'videos',
            'post_status' => 'publish'
        ]));

        if ($latestVideos->count()) {
            $latestVideos->transform(function($latestVideo) {
                return $this->transformPost($latestVideo);
            });
        }

        return $latestVideos;
    }

    public function getLatestDocuments()
    {
        $latestDocuments = collect(get_posts([
            'orderby'        => 'date',
            'numberposts' => 5,
            'post_type'   => 'documents',
            'post_status' => 'publish'
        ]));

        if ($latestDocuments->count()) {
            $latestDocuments->transform(function($latestDocument) {
                return $this->transformPost($latestDocument);
            });
        }

        return $latestDocuments;
    }

    public function getLatestDockets()
    {
        $latestDockets = collect(get_posts([
            'orderby'        => 'date',
            'numberposts'   => 5,
            'post_type'   => 'cases',
            'post_status' => 'publish'
        ]));

        if ($latestDockets->count()) {
            $latestDockets->transform(function($latestDocket) {
                return $this->transformPost($latestDocket);
            });
        }

        return $latestDockets;
    }

    /**
     * getPosts
     *
     * Returns the current loop of posts as JWPost objects.
     *
     * @return array
     */
    protected function getPosts(): Collection {
        global $wp_query;
        $wp_query = new \WP_Query($this->getLoopArgs());
        $posts    = $wp_query->posts;

        return collect($posts)->transform(function(WP_Post $post) {
            // for each posts in this loop, we just send it through the
            // JWPost constructor.  this sets things up for us in the way
            // we need it for our template and we rely on the internal
            // WordPress query var handling which keeps things simple for
            // us.

            $jwPost = new JWPost($post, true);
            return $jwPost;
        });
    }

	/**
	 * getLoopArgs
	 *
	 * Returns an array of arguments passed to get_posts() to control
	 * the loop on this page.
	 *
	 * @return array
	 */
	protected function getLoopArgs(): array {
		$args = [
			'posts_per_page' => 20,
            'category_name'  => 'press-releases',
			'paged'          => $this->getCurrentPage(),
		];

		if ($this->isTaxonomyArchive()) {
            global $post;
			// because this is a taxonomy archive, the object returned by
			// qet_queried_object() will be a WP_Term.  we can use the
			// properties of that object to prepare a tax query that we
			// use within our loop args.

			/** @var WP_Term $object */

			if ($post && isset($post->post_type)) {
			    $args['post_type'] = $post->post_type;
            }

			$object = get_queried_object();

			$args['tax_query'] = [
				[
					'taxonomy' => $object->taxonomy,
					'terms'    => $object->term_id,
					'field'    => 'term_id',
				],
			];
		} else if (is_archive()) {
		    global $post;
            $args['post_type'] = $post->post_type;
        }

		return $args;
	}

	/**
	 * isTaxonomyArchive
	 *
	 * Returns true if this is the archive for any taxonomy, both custom
	 * and built-in categories and tags.
	 *
	 * @return bool
	 */
	protected function isTaxonomyArchive(): bool {
		return is_tax() || is_category() || is_tag();
	}

	protected function getNumberOfPosts() {
		$numPosts = get_option('posts_per_page');

		// the grid for this page is set up for three columns.  so, if
		// the posts_per_page option isn't a multiple of three, we want
		// to try and bump it up until we get there as follows.  this
		// will set it up so that each of the on-screen rows are full
		// until the final page.

		while ($numPosts % 3 !== 0) {
			$numPosts++;
		}

		return $numPosts;
	}

    /**
     * @return array
     */
    protected function getPagination(): array {
        $currentPage = $this->getCurrentPage();
        $maximumPage = $GLOBALS['wp_query']->max_num_pages;

        $pages = array_map(function (int $pageNum) use ($currentPage) {

            // this map creates our pagination links and marks the page
            // that's current.  these are the data we need to create
            // our pagination links on-screen.

            return $this->getPaginationLink($pageNum, $currentPage);
        }, $this->getPageNumbers($currentPage, $maximumPage));

        return [
            'previous' => $this->getPreviousPaginationLink($currentPage),
            'next'     => $this->getNextPaginationLink($currentPage, $maximumPage),
            'pages'    => $pages,
        ];
    }

    /**
     * getCurrentPage
     *
     * Returns the current page number.
     *
     * @return int
     */
    protected function getCurrentPage(): int {
        $currentPage = (int) get_query_var('paged', 1);

        // WordPress uses a current page of zero to be the same as a
        // current page of one.  but, our loops below want the page to
        // be between one and our maximum number of pages inclusive.
        // so, if we're at page zero, we just return one; otherwise,
        // we return $currentPage.

        return $currentPage === 0 ? 1 : $currentPage;
    }

	/**
	 * getPaginationLink
	 *
	 * Given a page number and the current page, returns an array that will
	 * be used by our template to construct the pagination links.
	 *
	 * @param int $pageNum
	 * @param int $currentPage
	 *
	 * @return array
	 */
	protected function getPaginationLink(int $pageNum, int $currentPage): array {
		return [
			'pageNumber' => $pageNum,
			'isCurrent'  => $currentPage === $pageNum,
			'link'       => get_pagenum_link($pageNum),
		];
	}

	/**
	 * getPageNumbers
	 *
	 * Returns an array of five numbers used for our pagination.
	 *
	 * @param int $currentPage
	 *
	 * @param int $maximumPage
	 *
	 * @return array
	 */
	protected function getPageNumbers(int $currentPage, int $maximumPage): array {

		// our design calls for five pagination links.  that's enough
		// 'room' for our current number in the middle and two on either
		// side of it.  if our current page is in the first half of our
		// pages, we start at the bottom and work up; otherwise we need
		// to work backwards to be sure we get the right number of pages
		// when we're at the beginning.

		$pages = $currentPage < ($maximumPage / 2)
			? $this->getPageNumbersFromStart($currentPage, $maximumPage)
			: $this->getPageNumbersFromEnd($currentPage, $maximumPage);

		sort($pages);
		return $pages;
	}

	/**
	 * getPageNumbersFromStart
	 *
	 * Returns a list of page numbers, starting from before our current page,
	 * that can be used for pagination.
	 *
	 * @param int $currentPage
	 * @param int $maximumPage
	 *
	 * @return array
	 */
	protected function getPageNumbersFromStart(int $currentPage, int $maximumPage): array {
		$numbers = [];

		// we start at $currentPage - 2 and we loop until we've added five
		// numbers to our array or we hit the maximum page.  $i represents
		// a page to show, so if it's less than 1 we skip it.  we start
		// from $currentPage - 2 because we want to show 5 numbers which,
		// in a perfect world, is the current page and two on either side.

		for ($i = $currentPage - 2; sizeof($numbers) !== 5 && $i <= $maximumPage; $i++) {
			if ($i >= 1) {
				$numbers[] = $i;
			}
		}

		return $numbers;
	}

	/**
	 * getPageNumbersFromEnd
	 *
	 * Returns a list of page numbers, starting from the maximum page number,
	 * and working backwards.
	 *
	 * @param int $currentPage
	 * @param int $maximumPage
	 *
	 * @return array
	 */
	protected function getPageNumbersFromEnd(int $currentPage, int $maximumPage): array {
		$numbers = [];

		// this is very similar to the prior method.  but, we start at the
		// and go toward one.  otherwise, we might not end up with enough
		// numbers working from the bottom up.  for example, imagine that
		// we're on page 8 of 9.  the prior method would return 6, 7, 8, 9
		// but we actually want 5, 6, 7, 8, 9.  this method makes sure to
		// do that.

		for ($i = $currentPage + 2; sizeof($numbers) !== 5 && $i >= 1; $i--) {
			if ($i <= $maximumPage) {
				$numbers[] = $i;
			}
		}

		return $numbers;
	}

	/**
	 * getPreviousPaginationLink
	 *
	 * Returns the pagination link array for the prior page based on the
	 * current one.
	 *
	 * @param int $currentPage
	 *
	 * @return array
	 */
	protected function getPreviousPaginationLink(int $currentPage): array {

		// the previous page is one less than the current one.  unless, of
		// course, that would be less than one.  this ternary statement makes
		// sure we don't go beneath the lower bound of our pagination, and
		// then we call the method above to get our pagination link array.

		$pageNum = $currentPage === 1 ? 1 : ($currentPage - 1);
		return $this->getPaginationLink($pageNum, $currentPage);
	}

	/**
	 * getNextPaginationLink
	 *
	 * Returns the pagination link array for the next page based on the current
	 * one and the number for the final one.
	 *
	 * @param int $currentPage
	 * @param int $maximumPage
	 *
	 * @return array
	 */
	protected function getNextPaginationLink(int $currentPage, int $maximumPage): array {

		// the next page is one past the current page, unless that would
		// exceed the maximum page number.  our ternary statement makes sure
		// that we don't going outside the upper bound of our pagination,
		// and then we use the method above to return the pagination link
		// array.

		$pageNum = $currentPage === $maximumPage ? $maximumPage : ($currentPage + 1);
		return $this->getPaginationLink($pageNum, $currentPage);
	}

	/**
	 * getTheTitle
	 *
	 * Returns a page title that will override the meta.title value for
	 * this page.  the meta title ends up being the first post in our loop,
	 * and we want something that makes it clear that this is an archive.
	 *
	 * @return string
	 */
	protected function getTheTitle(): string {
		$title = 'Press Room';
		if ($this->isTaxonomyArchive()) {

			// because this is some form of taxonomy archive, we know the
			// object returned by the get_queried_object() will be a WP_Term.
			// thus, we'll use it's name property as our page's title.

			/** @var WP_Term $object */

			$object = get_queried_object();
			$title = JWCategory::transformCategoryName($object->name);
		} else if (is_archive()) {
		    $labels = \get_post_type_labels(get_queried_object());

		    if ($labels && isset($labels->archives)) {
		        return $labels->archives;
            }
        }

		return $title;
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
		return false;
	}
}
