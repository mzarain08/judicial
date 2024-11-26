<?php

namespace Engage\JudicialWatch\Traits;

use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\JudicialWatch;
use stdClass;
use WP_Post;
use WP_Taxonomy;

/**
 * Trait RelatedPostsTrait
 *
 * Some of our Templates require related posts as a part of their content.
 * These areas consistently offer editors the ability to choose up to three
 * posts as related, and then this code will make sure that we pad their
 * choices up to three if they don't manually pick them all (or any).
 *
 * @package Engage\JudicialWatch\Traits
 */
trait RelatedPostsTrait {
	/**
	 * getRelatedPosts
	 *
	 * Given the current post's ID and a list of the ACF keys that we need
	 * to use to identify related content, this function returns the three
	 * posts we use on-screen with the help of the other methods in this
	 * trait.
	 *
	 * @param int   $postId
	 * @param array $acfKeys
	 *
	 * @return array
	 */
	protected function getRelatedPosts(int $postId, array $acfKeys): array {
		$posts = [];

		foreach ($acfKeys as $acfKey) {
			$acfObject = get_field_object($acfKey);
			if ($acfObject["type"] === "relationship") {

				// now that we know that this ACF is of type relationship,
				// we'll get the chosen posts and see if we need to pad those
				// choices to get our full set of three.

				$chosen = $this->getChosenPosts($postId, $acfKey);
				$choices = 3 - sizeof($chosen);
				if ($choices > 0) {

					// if we have more choices to make, that means the editors
					// didn't choose 3 posts as related to this one already.
					// we'll pad our list of chosen posts with some that are
					// automatically chosen here.  since we don't want to
					// re-select the current post or the already-chosen posts,
					// we'll merge those data together and pass it to our
					// padding method.

					$newChoices = $this->padChoices($acfObject, $choices, $chosen, $postId);
					$chosen = array_merge($chosen, $newChoices);
				}

				$posts[$acfObject["name"]] = $this->getJWPosts($chosen);
			}
		}

		if (sizeof($posts) === 1) {

			// if there's only one single set of related posts in our array,
			// then we can just return it without the multi-dimensionality
			// of the ACF object's name

			$posts = array_shift($posts);
		}

		return $posts;
	}

	/**
	 * getChosenPosts
	 *
	 * Given the post ID and the ACF key for our relationship field, we
	 * return the posts chosen by the authors or an empty array.
	 *
	 * @param int    $postId
	 * @param string $acfKey
	 *
	 * @return array
	 */
	protected function getChosenPosts(int $postId, string $acfKey): array {

		// the get_field() function doesn't return an empty array when the
		// author of this post didn't choose any related ones for it.  so,
		// if we get an array back for this field, we return it.  otherwise,
		// we return an empty array.

		$chosen = get_field($acfKey, $postId);
		return is_array($chosen) ? $chosen : [];
	}

	/**
	 * padChoices
	 *
	 * Given the posts chosen for this post by the editors, this adds new
	 * posts based on the number of choices we have to make and returns them
	 * all to the calling scope.
	 *
	 * @param array $acfObject
	 * @param int   $choices
	 * @param array $chosen
	 * @param int   $postId
	 *
	 * @return array
	 */
	protected function padChoices(array $acfObject, int $choices, array $chosen, int $postId): array {

		// to make our selections, we will be needing the information about
		// our custom field.  getting the object definition of it will tell
		// us if there are post type or taxonometric limitations for our
		// query.  then, we can use WP_Query to actually select the posts
		// we return.

		$queryArgs = [

			// we don't want to re-select the current post or ones that the
			// editors have already chosen.  to avoid this, we can merge
			// them together and pass them to our query as follows.

			"post__not_in"   => array_merge([$postId], $chosen),
			"post_type"      => $this->getAcfSetting($acfObject, "post_type"),
			"post_status"    => "publish",
			"posts_per_page" => $choices,
			"fields"         => "ids",
		];

		$taxCriteria = $this->getAcfSetting($acfObject, "taxonomy");
		$queryArgs["tax_query"] = sizeof($taxCriteria) !== 0
			? $this->getAcfTaxQuery($taxCriteria)
			: $this->getPostTaxQuery($postId);

		return get_posts($queryArgs);
	}

	/**
	 * getAcfSetting
	 *
	 * Returns an ACF setting or an empty array.
	 *
	 * @param array  $acf
	 * @param string $index
	 *
	 * @return array
	 */
	protected function getAcfSetting(array $acf, string $index): array {
		return is_array($acf[$index]) ? $acf[$index] : [];
	}

	/**
	 * getAcfTaxQuery
	 *
	 * Returns a tax query parameter for WP_Query::parse_query() based on
	 * limitations set by our ACF object.
	 *
	 * @param array $taxCriteria
	 *
	 * @return array
	 */
	protected function getAcfTaxQuery(array $taxCriteria): array {
		$taxQuery = [];

		foreach ($taxCriteria as $criterion) {

			// acf stores it's tax query criteria in the form of
			// taxonomy:term.  we'll split that up and it starts
			// to give us what we need for our query.

			list($taxonomy, $term) = explode(":", $criterion);

			$taxQuery[] = [
				"terms"    => $term,
				"taxonomy" => $taxonomy,
				"field"    => "name",
			];
		}

		return $taxQuery;
	}

	/**
	 * getPostTaxQuery
	 *
	 * Given a post ID, we want to return other posts that exist within
	 * the same hierarchical taxonomies as it.
	 *
	 * @param int $postId
	 *
	 * @return array
	 */
	protected function getPostTaxQuery(int $postId): array {
		$taxonomies = $this->getTaxonomies(get_post_type($postId));

		$taxQuery = [];
		foreach ($taxonomies as $taxonomy) {

			// for each of the possible hierarchical taxonomies for this
			// post's type, we'll see if it is described by any of their
			// terms.  if so, we add a tax query parameter to be sure that
			// the posts we select as related are also described by those
			// terms.

			$terms = wp_get_post_terms($postId, $taxonomy, ["fields" => "names"]);

			if (sizeof($terms) > 0) {
				$taxQuery[] = [
					"terms"    => $terms,
					"taxonomy" => $taxonomy,
					"field"    => "name",
				];
			}
		}

		return $taxQuery;
	}

	/**
	 * getTaxonomies
	 *
	 * Give a post type, gets the hierarchical taxonomies by which it can
	 * be described.
	 *
	 * @param string $postType
	 *
	 * @return array
	 */
	protected function getTaxonomies(string $postType): array {

		// to identify which taxonomies are hierarchical for this post type,
		// we can use the get_object_taxonomies() WordPress function and then
		// filter its results.  the callback for our filter takes a taxonomy
		// object, searches for ones with a set hierarchical flag, and keeps
		// those discarding the rest.

		$taxonomies = get_object_taxonomies($postType, "objects");
		$taxonomies = array_filter($taxonomies, function (WP_Taxonomy $taxonomy) {
			return $taxonomy->hierarchical === 1;
		});

		// now that we have our list of hierarchical taxonomies for this post
		// type, we can get rid of the full object data; all we need are their
		// names.  luckily, their names are the keys of the returned array we
		// grabbed above, so we can grab only those as follows:

		return array_keys($taxonomies);
	}

	/**
	 * getJWPosts
	 *
	 * Given a list of post WP_Posts, return an array of JWPost objects.
	 *
	 * @param array $postIds
	 *
	 * @return array
	 */
	protected function getJWPosts(array $postIds): array {

		// what we have at this time is an array of post IDs, but we need
		// actual WP_Post objects to construct or JWPosts.  since we know
		// that we'll only have a limited number of posts, we're just going
		// to iterate over the list and use get_post() to get what we need.

		return array_map(function (int $postId) {
		    $post = get_post($postId);
		    if (!$post) {
		        return;
            }

			return new JWPost(get_post($postId), true);
		}, $postIds);
	}
}