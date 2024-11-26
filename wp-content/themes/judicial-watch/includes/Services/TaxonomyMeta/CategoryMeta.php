<?php

/** @noinspection HtmlUnknownTarget */

namespace Engage\JudicialWatch\Services\TaxonomyMeta;

use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Hooks\HookException;

class CategoryMeta extends AbstractHandler {
	/**
	 * initialize
	 * 
	 * Hooks this object into the WordPress ecosystem of hooks and filters.
	 * 
	 * @return void
	 * @throws HookException
	 */
	public function initialize() {
		$this->addFilter("manage_edit-category_columns", "addCatColumns");
		$this->addAction("manage_category_custom_column", "fillCatColumns", 10, 3);
	}

	/**
	 * addCatColumns
	 *
	 * Adds the columns we want to include for categories to the default set.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	protected function addCatColumns(array $columns): array {
		unset($columns["description"]);

		$newColumns = [];
		foreach ($columns as $name => $heading) {
			$newColumns[$name] = $heading;

			if ($name === "name") {
				$newColumns["thumbnail"] = __("Thumbnail", "judicialwatch");
				$newColumns["filter-order"] = __("Filer Order", "judicialwatch");
			}
		}

		return $newColumns;
	}

	/**
	 * fillCatColumns
	 *
	 * Prints the information that should fill our custom columns to the
	 * screen.
	 *
	 * @param string $content
	 * @param string $column
	 * @param int    $termId
	 *
	 * @return void
	 */
	protected function fillCatColumns(string $content, string $column, int $termId): void {
		if ($column === "thumbnail") {
			$content = $this->getThumbnail($termId);
		} elseif ($column === "filter-order") {

			// if this is the filter order column, we need to see if this
			// category is actually a filter.  if so, then we select the
			// order.  this is to avoid showing an order number for any
			// categories that used to be filters but aren't right now.

			if (get_field("category_is_filter", "term_$termId")) {
				$content = get_field("category_filter_order", "term_$termId");
			}
		}

		echo $content;
	}

	/**
	 * getThumbnail
	 *
	 * Gets the image for our Thumbnail column.
	 *
	 * @param int $termId
	 *
	 * @return string
	 */
	protected function getThumbnail(int $termId): string {
		return ($thumbnail = get_field("category_image", "term_$termId"))
			? sprintf('<img src="%s" width="50" height="50">', $thumbnail["sizes"]["thumbnail"])
			: "";
	}
}