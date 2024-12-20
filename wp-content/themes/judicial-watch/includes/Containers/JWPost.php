<?php

namespace Engage\JudicialWatch\Containers;

use Engage\JudicialWatch\Containers\Container;
use Dashifen\Container\ContainerException;
use WP_Post;

/**
 * Class JWPost
 * @package Engage\JudicialWatch\Containers
 * @property $title
 * @property $link
 */
class JWPost extends Container {
	/**
	 * @var int
	 */
    public $postId = 0;

	/**
	 * @var array
	 */
    public $category = [];

	/**
	 * @var array
	 */
	public $thumbnails = [];

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $slug = '';

	/**
	 * @var string
	 */
    public $date = '';

	/**
	 * @var string
	 */
    public $icon = '';

	/**
	 * @var string
	 */
    public $type = '';



	/**
	 * @var string
	 */

    public $episodeId = '';

	/**
	 * @var string
	 */
    public $link = '';

	/**
	 * @var string
	 */
    public $videoid = '';

	/**
	 * @var string
	 */
    public $excerpt = '';

	/**
	 * @var string
	 */
    public $content = '';

	/**
	 * @var array
	 */
    public $source = [];

	/**
	 * JWPost constructor.
	 *
	 * @param WP_Post $post
	 *
	 * @param bool $withContent
	 *
	 * @throws ContainerException
	 */
	public function __construct(WP_Post $post, bool $withContent = false) {
		$args = [
			'postId'     => $post->ID,
			'slug'       => $post->post_name,
			'date'       => $post->post_date,
			'type'       => $post->post_type,
			'title'      => $post->post_title,
			'link'       => get_the_permalink($post->ID),
			'videoid'    => $this->videoid,
			'source'     => $this->getTheSource($post->ID),
			'category'   => $this->getTheCategory($post->ID),
			'thumbnails' => $this->getTheThumbnails($post->ID),
			'icon'       => $this->getTheIcon($post->post_type),
		];
		
		if ($withContent) {
			$args['content'] = apply_filters('the_content', $post->post_content);
		} else {
			$args['excerpt'] = @get_the_excerpt($post);
		}

		parent::__construct($args);
	}

	/**
	 * getTheCategory
	 *
	 * Given a WP_Post object, get the first category it's assigned to.
	 *
	 * @param int $postId
	 *
	 * @return array
	 */
	protected function getTheCategory(int $postId): array {
		$categories = get_the_category($postId);
		$category = ['link' => '', 'name' => '', 'id' => ''];

		if (sizeof($categories) > 0) {
			$theCategory = array_shift($categories);

			$category = [
				'link'  => get_term_link($theCategory),
				'name'  => $theCategory->name,
                'slug'  => $theCategory->slug,
                'id'    => $theCategory->term_taxonomy_id,
                'iconColor' => get_field('icon_color', $theCategory)
			];
		}

		return $category;
	}

	/**
	 * getTheThumbnails
	 *
	 * Returns an array with pertinent thumbnail information.
	 *
	 * @param int $postId
	 *
	 * @return array
	 * @throws ContainerException
	 */
	protected function getTheThumbnails(int $postId): array {
		$thumbnailId = get_post_thumbnail_id($postId);

		if (is_string($thumbnailId) || is_numeric($thumbnailId)) {
			foreach (get_intermediate_image_sizes() as $size) {
				$thumbnails[$size] = new JWImage($thumbnailId, $size);
			}
		}

		return $thumbnails ?? [];
	}

	/**
	 * getTheSource
	 *
	 * Gets the source for this post or Judicial Watch by default.
	 *
	 * @param int $postId
	 *
	 * @return array
	 */
	protected function getTheSource(int $postId): array {
		$link = get_field("article_source", $postId);
		$name = get_field("article_outlet", $postId);

		return [
			"name" => !empty($name) ? $name : "Judicial Watch",
			"link" => !empty($link) ? $link : "",
		];
	}

	/**
	 * getTheIcon
	 *
	 * Returns the FA icon name that we want to use based on the post type.
	 *
	 * @param string $postType
	 *
	 * @return string
	 */
	protected function getTheIcon(string $postType) {

		// there are three icons we're using for our posts at this time.
		// videos get the youtube icon, podcasts get a microphone, and
		// everything else gets the rolled-up newspaper looking deal.  we
		// could use a switch or a series of if-statements, but instead
		// we'll nested ternaries which are bad form, yes, but super fast.

		return $postType !== "video"
			? ($postType !== "podcast" ? "fas fa-newspaper" : "fas fa-microphone")
			: "fab fa-youtube";
	}

	/**
	 * setPostId
	 *
	 * Sets the postId property.
	 *
	 * @param int $postId
	 */
	public function setPostId(int $postId): void {
		$this->postId = $postId;
	}

	/**
	 * setCategory
	 *
	 * Sets the category property.
	 *
	 * @param array $category
	 *
	 * @return void
	 */
	public function setCategory(array $category) {
		$this->category = $category;
	}

	/**
	 * setThumbnails
	 *
	 * Sets the thumbnails property.
	 *
	 * @param array $thumbnails
	 *
	 * @return void
	 */
	public function setThumbnails(array $thumbnails) {
		$this->thumbnails = $thumbnails;
	}

	/**
	 * setTitle
	 *
	 * Sets the title property.
	 *
	 * @param string $title
	 *
	 * @return void
	 */
	public function setTitle(string $title) {
		$this->title = $title;
	}

	/**
	 * setDate
	 *
	 * Sets the date property in the format described in the site's settings.
	 *
	 * @param string $date
	 *
	 * @return void
	 */
	public function setDate(string $date) {
		date_default_timezone_set("America/New_York");
		$this->date = date(get_option("date_format"), strtotime($date));
	}

	/**
	 * setIcon
	 *
	 * Sets the icon property.
	 *
	 * @param string $icon
	 */
	public function setIcon(string $icon): void {
		$this->icon = $icon;
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

	/**
	 * setVideo
	 *
	 * Sets the Video property.
	 *
	 * @param string $videoid
	 */
	public function setVideoid(string $videoid): void {
        $this->videoid = get_field("video_id", $this->postId);
	}



	/**
	 * setExcerpt
	 *
	 * Sets the excerpt property.
	 *
	 * @param string $excerpt
	 *
	 * @return void
	 */
	public function setExcerpt(string $excerpt) {
		$this->excerpt = $excerpt;
	}

	/**
	 * @param string $content
	 */
	public function setContent(string $content): void {
		$this->content = $content;
	}

	/**
	 * setType
	 *
	 * Sets the type parameter
	 *
	 * @param string $type
	 */
	public function setType(string $type): void {
		$this->type = $type;

		if ($this->postId > 0) {
			if ($type === "video") {
				$this->videoId = get_field("video_id", $this->postId);
			} elseif ($type === "podcast") {
				$this->episodeId = get_field("buzzsprout_episode_id", $this->postId);
			}
		}
	}

    /**
     * setSource
     *
     * Sets the source property.
     *
     * @param array $source
     *
     * @return void
     */
    public function setSource(array $source) {
        $this->source = $source;
    }

    /**
     * setSlug
     *
     * Sets the slug property.
     *
     * @param string $slug
     *
     * @return void
     */
    public function setSlug(string $slug): void {
        $this->slug = $slug;
    }
}