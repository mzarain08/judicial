<?php

namespace Engage\JudicialWatch\Templates;

use Dashifen\Container\ContainerException;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Containers\MainMenuItem;
use Engage\JudicialWatch\Services\ACF\SitewideSettings;
use Engage\JudicialWatch\Templates\TemplateServices\HappeningNow;
use Engage\WordPress\Pages\TimberPage\AbstractTimberPage;
use Engage\WordPress\Pages\PageException;
use Timber\Timber;
use WP_Post;

abstract class AbstractTemplate extends AbstractTimberPage {
	/**
	 * @var int
	 */
	public $postId;

	/**
	 * AbstractTemplate constructor.
	 *
	 * @param bool $getTimberContext
	 */
	public function __construct(bool $getTimberContext = false) {
		$this->postId = is_singular() ? get_the_ID() : 0;

		parent::__construct($getTimberContext);
	}

	/**
	 * getContext
	 *
	 * Returns the context property of this object.
	 *
	 * @return array
	 */
	public function getContext(): array {
		return $this->context;
	}

	/**
	 * getContextValue
	 *
	 * Uses the $index parameter to drill down into the context property
	 * and returns a specific value within it.  Because these values are of
	 * mixed types, we unfortunately can't specify a return type hint for
	 * this method.
	 *
	 * @param string $index
	 *
	 * @return mixed|null
	 */
	public function getContextValue(string $index) {

		// to drill down into our context property, we start from the
		// assumption that we're returning the whole thing.  then, we
		// explode the $index we're given on spaces and filter out any
		// blanks.  these $indices we use in a loop to dive into our
		// context to find the value that was requested.

		$retValue = $this->context;
		$indices = array_filter(explode(" ", $index));

		foreach ($indices as $index) {

			// this is where we drill down.  we assume each $index can be
			// found in our $retValue.  each iteration then "moves" us
			// through the dimensions of our context property.  if we ever
			// find an $index that is not available, we return null to tell
			// the calling scope that it messed up its request.

			$retValue = $retValue[$index] ?? null;
			if (is_null($retValue)) {
				return null;
			}
		}

		return $retValue;
	}

	/**
	 * title
	 *
	 * Returns the <title> for this template.
	 *
	 * @return string
	 */
	protected function title(): string {

		// most of the time, we just want to return the title for this
		// post.  but for archives and the homepage, we'll want to over-
		// ride this behavior.  thus, we encapsulate it in this method
		// and children can mess with it when they need to.

		return get_the_title();
	}

	/**
	 * bodyClasses
	 *
	 * Returns an array of custom body classes for this template.
	 *
	 * @return array
	 */
	abstract protected function bodyClasses(): array;

	/**
	 * withCorruptionBanner
	 *
	 * Returns true when the Fight Corruption! banner should be visible
	 * on this template.
	 *
	 * @return bool
	 */
	abstract protected function withCorruptionBanner(): bool;

	/**
	 * withDaywatch
	 *
	 * Returns true when the Daywatch subscription banner should be
	 * visible on this template.
	 *
	 * @return bool
	 */
	abstract protected function withDaywatchBanner(): bool;


	/**
	 * show
	 *
	 * Puts the debug content after the actual content.
	 *
	 * @param string $template
	 * @param bool   $debug
	 *
	 * @throws PageException
	 */
	public function show(string $template, bool $debug = false) {
		if (empty($template)) {
			throw new PageException("Cannot render without template",
				PageException::CANNOT_RENDER_TEMPLATE);
		}

		Timber::render($template, $this->context);

		if ($debug) {
			$context = str_replace("<!--more-->", "", print_r($this->context, true));
			echo "<!-- #debug $context -->";
		}
	}

    /**
     * Merge Page Context
     *
     * Merges a keypair array to the current page context.
     *
     * @param array $keypairArray
     * @return $this
     */
    public function mergePageContext(array $keypairArray) {
        $this->context = array_merge_recursive($this->context, $keypairArray);

        return $this;
    }

    public function setPageContextByKey($key, $value)
    {
        if (isset($this->context['page'])) {
            $this->context['page'][$key] = $value;
        }

        return $this;
    }


	protected function addToAllPagesContext()
    {
        $baseContextSettings = get_transient('jw_abstract_all_pages_context');

        if (!$baseContextSettings) {
            $baseContextSettings = [
                'defaultImage' => get_theme_file_uri('assets/images/logo-361x85.png'),
                'mainMenu'     => $this->getMainMenu(),
                'mainMenuMoreLinks' => $this->getMainMenuMoreLinks(),
                'happeningNow' => (new HappeningNow())->getHappeningNow(),
                'newsletterFormHtml' => $this->getNewsletterFormHtml(),
                'footer'       => [
                    'menuItems'      => $this->getFooterMenu()
                ],
                'sitewideSettings' => (new SitewideSettings)->getSettings()
            ];

            set_transient('jw_abstract_all_pages_context', $baseContextSettings);
        }

        $this->context = array_merge($this->context, $baseContextSettings);
        $this->context = array_merge($this->context, [
            'meta' => [
                'bodyClasses' => $this->getBodyClasses($this->bodyClasses()),
                'fullHeader' => is_front_page(),
                'title' => $this->title(),
                'year' => date('Y'),
            ],
            'footer' => [
                'menuItems' => $this->getFooterMenu(),
                'withCorruption' => $this->withCorruptionBanner(),
                'withDaywatch' => $this->withDaywatchBanner(),
            ]
        ]);

		parent::addToAllPagesContext();
	}
	public function getNewsletterFormHtml()
    {
        $html = gravity_form(
            // ID
            6,
            // Show title
            false,
            // Show description
            false,
            // Display inactive
            false,
            // Field values
            null,
            // Is Ajax
            true,
            // Tab index
            50,
            // Echo
            false
        );

        return $html;
    }

    protected function getFooterMenu() {
        $menuItems = wp_get_nav_menu_items('footer');

        return $menuItems;
    }

    protected function getMainMenuMoreLinks() {
        $menuLocations = get_nav_menu_locations();
        $menuTerm = get_term($menuLocations["main-menu"], "nav_menu");
        $menuItems = collect(get_field('main_menu_more_links', $menuTerm));

        if (!$menuItems->count()) {
            return [];
        }

        $menuItems->transform(function($linkArray) {
            $linkConfig = collect($linkArray);


            // URL is always required
            if (!data_get($linkConfig, 'link.url')) {
                return;
            }

            $linkPermalink = str_replace(_config_wp_home(), '', data_get($linkConfig, 'link.url'));
            $postId = url_to_postid($linkPermalink);

            if ($postId) {
                $post = new JWPost(get_post($postId), true);
                if ($linkConfig->get('link.title')) {
                    $post->title = $linkConfig->get('link.title');
                }

                if (data_get($linkConfig, 'link.title') && data_get($linkConfig, 'link.url')) {
                    $post->title = $linkArray['link']['title'];
                    $post->url = $linkArray['link']['url'];
                } else {
                    $post->url = get_the_permalink($post->postId);
                }

                $post->target = data_get($linkConfig, 'link.target');

                return $post;
            }

            // No post was found, it may be an external link
            if (!str_contains($linkPermalink, 'WP_HOME')) {
                return (object)[
                    'url'   => $linkPermalink,
                    'title' => data_get($linkConfig, 'link.title'),
                    'target' => data_get($linkConfig, 'link.target')
                ];
            }
        });

        return $menuItems;
    }

	/**
	 * getMainMenu
	 *
	 * Creates and returns an array of information needed to construct
	 * the JW main menu.
	 *
	 * @return array
	 */
	protected function getMainMenu() {
		if (is_singular("collection")) {

			// our collection post type doesn't use the main menu for the
			// site.  so, we can save time here and in data transfer by just
			// not loading it into our context.

			return [];
		}

		// the menu is made up of the top-level items in the menu in the
		// main-menu location and the ACF values that are attached to them.

		$locations = get_nav_menu_locations();
		$menuTerm  = get_term($locations["main-menu"], "nav_menu");
		$menuItems = wp_get_nav_menu_items($menuTerm->term_id);

		// we've got WP_Posts but we want MainMenuItems which encapsulate
		// both the WordPress data and the ACF's for those data all in one.
		// luckily the latter is constructed from the former.  so, we can
		// use array_map() to return what we need.

		$menuItems = array_map(function (WP_Post $post) {
			return new MainMenuItem($post);
		}, $menuItems);

		return $menuItems;
	}

	/**
	 * getBodyClasses
	 *
	 * Returns a DOM-ready string of classes for the body tag.
	 *
	 * @param array $classes
	 *
	 * @return string
	 */
	protected function getBodyClasses(array $classes = []) {

		// we don't want to use body_class() here because that auto-displays
		// the classes.  but, get_body_class() returns the array of classes
		// which we can't display.  so, we join() them with spaces just like
		// the DOM wants.

		return join(" ", get_body_class($classes));
	}

	/**
	 * transformPost
	 *
	 * Given a WP_Post, returns a JWPost for our use in the theme.
	 *
	 * @param WP_Post $post
	 *
	 * @return JWPost
	 * @throws ContainerException
	 */
	protected function transformPost(?WP_Post $post, $withContent = true) {
	    if (!$post) {
	        return null;
        }

		return new JWPost($post, $withContent);
	}
}