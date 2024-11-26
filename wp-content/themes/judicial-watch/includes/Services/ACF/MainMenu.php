<?php
/**
 * Created by PhpStorm.
 * User: dashifen
 * Date: 9/25/2018
 * Time: 1:20 PM
 */

namespace Engage\JudicialWatch\Services\ACF;

use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Hooks\HookException;
use ReflectionException;
use WP_Post_Type;

class MainMenu extends AbstractHandler {
	/**
	 * initialize
	 *
	 * Adds filters related to the ACF group for our main menu.
	 *
	 * @throws HookException
	 */
	public function initialize() {
		$this->addFilter("acf/load_field/key=field_5baa6d8b59331", "addLatestPostsChoices");
	}

	/**
	 * addLatestPostsChoices
	 *
	 * Gets a list of the post types that we can "attach" to main menu
	 * items.
	 *
	 * @param $field
	 *
	 * @return array
	 */
	protected function addLatestPostsChoices(array $field): array {
		$fields = [];
		$values = [];

		// the following line of code gets the post types out of the database.
		// then, we walk that array to create a list of the post type names
		// and they're on-screen label.  these are then combined to form the
		// choices for this ACF checkbox field.

		$postTypes = get_post_types(["public" => true], "objects");
		array_walk($postTypes, function(WP_Post_Type $postType) use (&$fields, &$values) {
			if (!in_array($postType->name, ["page","attachment"])) {
				$values[] = $postType->labels->name;
				$fields[] = $postType->name;
			}
		});

		$field["choices"] = array_combine($fields, $values);
		return $field;
	}
}