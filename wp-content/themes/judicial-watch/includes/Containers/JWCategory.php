<?php

namespace Engage\JudicialWatch\Containers;

use Engage\JudicialWatch\Containers\Container;
use Dashifen\Container\ContainerException;
use WP_Term;

/**
 * Class JWCategory
 * @package Engage\JudicialWatch\Containers
 * @property $termId
 * @property $isFilter
 * @property $filterOrder
 * @property $name
 * @property $link
 */
class JWCategory extends Container {
	/**
	 * @var int
	 */
	protected $termId = 0;

	/**
	 * @var bool
	 */
	protected $isFilter = false;

	/**
	 * @var bool
	 */
	public $isActiveFilter = false;

	/**
	 * @var int
	 */
	protected $filterOrder = 0;

	/**
	 * @var string
	 */
	protected $name = "";

	/**
	 * @var string
	 */
	protected $link = "";

	protected $slug = '';

	public $iconColor;

	/**
	 * JWCategory constructor.
	 *
	 * @param WP_Term $category
	 *
	 * @throws ContainerException
	 */
	public function __construct(WP_Term $category) {
		if ($category->taxonomy !== 'category') {
			throw new ContainerException('JWCategories require a category; received ' . $category->taxonomy . '.');
		}

		parent::__construct([
			'termId'         => $category->term_id,
			'link'           => get_term_link($category),
			'isFilter'       => $this->isFilter($category),
			'isActiveFilter' => $this->isActiveFilter($category),
			'filterOrder'    => (int) get_field('category_filter_order', 'term_' . $category->term_id),
			'name'           => self::transformCategoryName($category->name),
            'slug'           => isset($category->slug) ? $category->slug : null,
            'iconColor'      => get_field('icon_color', $category->term_id)
		]);
	}

	protected function setSlug($slug)
    {
        $this->slug = $slug;
    }

	/**
	 * isFilter
	 *
	 * Returns true if this category is a filter on the archive page.
	 *
	 * @param WP_Term $category
	 *
	 * @return bool
	 */
	protected function isFilter(WP_Term $category): bool {
		return (bool) get_field("category_is_filter", "term_" . $category->term_id);
	}

	protected function isActiveFilter(WP_Term $category) {
		if (is_category() && $this->isFilter($category)) {

			// because this is a category archive, the object returned by
			// get_queried_object() will be a WP_Term.  if it's term ID
			// matches the $category that was passed here, then this must
			// be the active filter.

			$object = get_queried_object();
			return $object->term_id === $category->term_id;
		}

		return false;
	}

	public static function transformCategoryName(string $name) {

		// one category is technically named Micah Morrison's Investigative
		// Bulletin, but that name is really long, so we'll shorten it when
		// we're using this contain to display information.  it's full name
		// is used elsewhere, though (e.g. in the post cards).

		return $name === "Micah Morrison's Investigative Bulletin"
			? "Investigative Bulletin"
			: $name;
	}

	/**
	 * setTermId
	 *
	 * Sets the term ID parameter.
	 *
	 * @param int $termId
	 */
	public function setTermId(int $termId): void {
		$this->termId = $termId;
	}

	/**
	 * setIsFilter
	 *
	 * Sets the isFilter property.
	 *
	 * @param bool $isFilter
	 */
	public function setIsFilter(bool $isFilter): void {
		$this->isFilter = $isFilter;
	}

	/**
	 * @param bool $isActiveFilter
	 */
	public function setIsActiveFilter(bool $isActiveFilter): void {
		$this->isActiveFilter = $isActiveFilter;
	}

	/**
	 * setFilterOrder
	 *
	 * Sets the filterOrder property.
	 *
	 * @param int $filterOrder
	 */
	public function setFilterOrder(int $filterOrder): void {
		$this->filterOrder = $filterOrder < 0 ? 0 : $filterOrder;
	}

	/**
	 * setName
	 *
	 * Sets the name property.
	 *
	 * @param string $name
	 */
	public function setName(string $name): void {
		$this->name = $name;
	}

	/**
	 * setLink
	 *
	 * Sets the link property.
	 *
	 * @param string $link
	 */
	public function setLink(string $link): void {
		$this->link = $link;
	}


}