<?php
/**
 * Created by PhpStorm.
 * User: dashifen
 * Date: 9/25/2018
 * Time: 3:11 PM
 */

namespace Engage\JudicialWatch\Containers;

use Engage\JudicialWatch\Containers\Container;
use Dashifen\Container\ContainerException;

class JWImage extends Container {
	/**
	 * @var string
	 */
	public $src = "";

	/**
	 * @var int
	 */
	public $width = 0;

	/**
	 * @var int
	 */
	public $height = 0;

	/**
	 * @var string
	 */
	public $alt = "";

	/**
	 * @var string
	 */
	public $caption = "";

	/**
	 * JWImage constructor.
	 *
	 * @param int    $imageId
	 * @param string $size
	 *
	 * @throws ContainerException
	 */
	public function __construct($imageId, string $size = 'full') {
        $imageMeta = wp_get_attachment_metadata($imageId);

        if (empty($imageMeta) || !isset($imageMeta['sizes'])) {
            return;
        }

        $imageSource = wp_get_attachment_image_src($imageId, $size);
        if (!$imageSource || count($imageSource) !== 4 || !$imageSource[0]) {
            return;
        }

		parent::__construct([
			'src'     => $imageSource[0],
			'width'   => $imageSource[1],
			'height'  => $imageSource[2],
			'alt'     => get_post_meta($imageId, '_wp_attachment_image_alt', true),
			'caption' => @get_the_excerpt($imageId),
		]);
	}

	/**
	 * setSrc
	 *
	 * Sets the src property.
	 *
	 * @param string $src
	 *
	 * @return void
	 */
	protected function setSrc(string $src) {
		$this->src = $src;
	}

	/**
	 * setWidth
	 *
	 * Sets the width property.
	 *
	 * @param int $width
	 *
	 * @return void
	 */
	protected function setWidth(int $width) {
		$this->width = $width;
	}

	/**
	 * setHeight
	 *
	 * Sets the height property.
	 *
	 * @param int $height
	 *
	 * @return void
	 */
	protected function setHeight(int $height) {
		$this->height = $height;
	}

	/**
	 * setAlt
	 *
	 * Sets the alt property.
	 *
	 * @param string $alt
	 *
	 * @return void
	 */
	protected function setAlt(string $alt) {
		$this->alt = $alt;
	}

	/**
	 * setCaption
	 *
	 * Sets the caption property
	 *
	 * @param string $caption
	 *
	 * @return void
	 */
	protected function setCaption(string $caption) {
		$this->caption = $caption;
	}
}