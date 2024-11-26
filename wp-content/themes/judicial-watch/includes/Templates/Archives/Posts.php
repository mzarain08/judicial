<?php

namespace Engage\JudicialWatch\Templates\Archives;

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\AbstractTemplate;
use Engage\JudicialWatch\Containers\JWCategory;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\Singular\Post;
use WP_Term;
use WP_Post;

class Posts extends AbstractTemplate {
	/**
	 * addToThisPageContext
	 *
	 * Adds data to the $context property that is, later, used to render
	 * a Twig template.
	 *
	 * @return void
	 */
	protected function addToThisPageContext() {

	    $title = $this->getTheTitle();
	    $permalink = get_permalink($this->postId);
		global $wp;
		//print_r($wp);die;
		$wp_request = explode("/",$wp->request );
		$current_url = home_url( $wp_request[0]  ); 
		$this->context['page'] = [
			'isPosts'    => !$this->isTaxonomyArchive() && get_post_type() === 'post',
			'filters'    => $this->getFilters(),
			'posts'      => $this->getPosts(),
			'pagination' => $this->getPagination(),
			'title'      => $this->getTheTitle(),
			'current_cat_url' => $current_url."/"
		];

		data_set($this->context, 'social', [
            'tweet' => urlencode(sprintf("Judicial Watch - %s - %s", $title, $permalink)),
            'title' => urlencode($title),
            'link'  => urlencode($permalink),
        ]);
	}

	/**
	 * getFilters
	 *
	 * Returns an array of information needed to handle the filtering of
	 *
	 *
	 * @return array
	 */
	public function getFilters(): array {
		$categories = collect(get_terms([
               'taxonomy' => 'category',
				'hide_empty' => true,
				'exclude' => array( 40923 ),
                'number'   => 6 
            ]))
            ->transform(function(WP_Term $category) {
                if ('Uncategorized' === $category->name) {
				   // $category->name = 'All';
                }
                return $category;
            })
            ->toArray();

		// if we're still here, then we need to order the terms selected
		// by the query before our if-block based on the value within the
		// category_filter_order term meta.  first, we transform our term
		// objects into JWCategory ones.  those we can quickly sort using
		// the SPACESHIP OPERATOR!  finally, we return the sorted list.

		$transformed = array_map(function (WP_Term $term) {
			return new JWCategory($term);
		}, $categories);

		/*usort($transformed, function (JWCategory $a, JWCategory $b) {

			// if our filter orders don't match, use them to sort.  but,
			// if they do match, then sort by the name of this category.

			return $a->filterOrder !== $b->filterOrder
				? $a->filterOrder <=> $b->filterOrder
				: $a->name <=> $b->name;
		});*/

		// the last thing we need to do is add in the page for posts.
		// since the JWCategory object takes a WP_Term as the argument
		// for its constructor and we won't have one of those for this
		// link, we'll need to construct an array with the same indices
		// as that object has properties as follows.  for Twig, this is
		// good enough.  we'll unshift it onto the from of the the
		// $transformed array of filters and then return that array to
		// the calling scope.

        $requestUri = $_SERVER['REQUEST_URI'];

        $matchedCategory = collect($transformed)
            ->filter(function($term) use ($requestUri) {
                if (str_contains($requestUri, sprintf('/category/%s/', $term->slug))) {
                    return true;
                }
            })
            ->transform(function($term) {
                $term->isActiveFilter = true;

                return $term;
            });

		return $transformed;
	}

	/**
	 * getPosts
	 *
	 * Returns the current loop of posts as JWPost objects.
	 *
	 * @return array
	 */
	protected function getPosts(): array {
		return array_map(function (WP_Post $post) {

			// for each posts in this loop, we just send it through the
			// JWPost constructor.  this sets things up for us in the way
			// we need it for our template and we rely on the internal
			// WordPress query var handling which keeps things simple for
			// us.

			return new JWPost($post);
		}, get_posts($this->getLoopArgs()));
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
			'posts_per_page' => 9,
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
		$title = 'News';
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
		return ['page', 'category'];
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