<?php

namespace Engage\JudicialWatch\Containers;

class JWIssue extends Container {
	/**
	 * @var int
	 */
	protected $termId = 0;

	/**
	 * @var string
	 */
	protected $name = '';

	/**
	 * @var array
	 */
	protected $image = [];

	/**
	 * @var string
	 */
	protected $link = '';

	public function __construct(int $termId) {
		$term = get_term($termId, 'issues');
		$termImage = get_field('issue_image', "term_{$termId}");

		parent::__construct([
			'termId' => $termId,
			'name'   => $term->name,
			'image'  => $termImage ? $termImage : null,
			'link'   => get_term_link($term),
		]);
	}

	/**
	 * setTermId
	 *
	 * Sets the termId property.
	 *
	 * @param int $termId
	 */
	public function setTermId(int $termId): void {
		$this->termId = $termId;
	}

	/**
	 * setName
	 *
	 * Sets the name property
	 *
	 * @param string $name
	 */
	public function setName(string $name): void {
		$this->name = $name;
	}

	/**
	 * setImage
	 *
	 * Sets the image property
	 *
	 * @param array $image
	 */
	public function setImage(?array $image): void {
		$this->image = $image;
	}

	/**
	 * setLink
	 *
	 * Sets the link property
	 *
	 * @param string $link
	 */
	public function setLink(string $link): void {
		$this->link = $link;
	}
}