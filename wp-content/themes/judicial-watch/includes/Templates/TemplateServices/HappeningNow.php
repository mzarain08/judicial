<?php

namespace Engage\JudicialWatch\Templates\TemplateServices;

use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\JudicialWatch;
use WP_Post;

class HappeningNow {
	/**
	 * getHappeningNow
	 *
	 * Returns the array of information we add to the template context for
	 * all pages on this site.
	 *
	 * @return array
	 */
	public function getHappeningNow(): array {
		if (is_singular("collection")) {

			// the collection post type doesn't use the same header as the
			// rest of the site.  therefore, we don't need any happening now
			// information.  we'll simply return a blank array here.
			return [];

		}

		$data = [
			"limit"      => 3,
			"hideBadge"  => isset($_COOKIE["hide-happening-now-badge"]),
			"recentPost" => JudicialWatch::getRecentPostTimestamp(),
			"timezone"   => get_option("timezone_string"),
			"offset"     => get_option("gmt_offset"),
			"posts"      => $this->getPosts(),
		];

		return $data;
	}

	/**
	 * getPosts
	 *
	 * This method returns an array of JWPosts objects that we use to load
	 * information into the Happening Now dialog on-screen.
	 *
	 * @return array
	 */
	private function getPosts(): array 
    {
		$posts = get_posts([
			'orderby'        => 'date',
			'posts_per_page' => 5,
			'post_type'      => [
			    'post', 'cases', 'documents'
            ],
		]);

		return array_map(function (WP_Post $post) {
			return new JWPost($post);
		}, $posts);
	}

	/**
	 * getTypes
	 *
	 * Returns an array of post types to use when filling the Happening Now
	 * dialog.
	 *
	 * @return array
	 */
	private function getTypes(): array {

		// to get our types, we get the list of our custom post types only.
		// this would leave out posts, so we add it explicitly.  the array
		// values call just makes sure that everything is as simple as
		// possible.

		$types = get_post_types(["public" => true, "_builtin" => false]);
		$types = array_values(array_merge($types, ["post"]));

		// now, we want to remove two types:  members and donate pages.  we
		// create an array for those types and flip it so that we can use a
		// single isset() call in our array_filter callback to remove these
		// two from the array.

		$removeThese = array_flip(["member", "donate"]);
		return array_filter($types, function ($type) use ($removeThese) {
			return !isset($removeThese[$type]);
		});
	}
}