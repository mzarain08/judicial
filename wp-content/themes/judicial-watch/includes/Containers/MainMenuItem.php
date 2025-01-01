<?php

namespace Engage\JudicialWatch\Containers;

use Engage\JudicialWatch\Containers\Container;
use Dashifen\Container\ContainerException;
use WP_Query;
use WP_Post;

/**
 * Class MainMenuItem
 * @package Engage\JudicialWatch\Containers
 * @property $id
 * @property $htmlId
 * @property $url
 * @property $title
 * @property $template
 * @property $meta
 */
class MainMenuItem extends Container
{
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var string
     */
    public $htmlId = "";

    /**
     * @var string
     */
    public $url = "";

    /**
     * @var string
     */
    public $title = "";

    public $submenu = [];

    /**
     * @var string
     */
    public $template = "";

    /**
     * @var array
     */
    public $meta = [];

    public $submenuExists = false;

    /**
     * MainMenuItem constructor.
     *
     * @param WP_Post $item
     *
     * @throws ContainerException
     */
    public function __construct(WP_Post $item)
    {
        error_log($item->ID);

        $destinationId = get_post_meta($item->ID, "_menu_item_object_id", true);
        $title = $item->title;
        $submenu = $this->getSubmenuConfig($item);
        $permalink = get_the_permalink($item->ID);
        $url = $permalink ?? null;

        if ($submenu->layout && $submenu->layout !== 'none') {
            $this->submenuExists = true;
        } else {
            if ('custom' === $item->type) {
                $url = $item->url;
            } else {
                $url = get_the_permalink($destinationId);
            }
        }

        parent::__construct([
            'id' => $destinationId,
            'submenu' => $submenu,
            'title' => $title,
            'url' => $url
        ]);
    }

    public function getSubmenuConfig(WP_Post $wpPost)
    {
        error_log($wpPost->ID);
        $submenu = new \stdClass();

        // `submenu_tabs` or `submenu_links`
        $submenu->layout = get_field('submenu_layout', $wpPost->ID);

        if ('submenu_links' === $submenu->layout) {
            $submenu->posts = collect(get_field('submenu_layout_links', $wpPost->ID));

            $submenu->posts->transform(function ($linkArray) {
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

                    // Image override
                    if (data_get($linkConfig, 'image.url')) {
                        $post->thumbnails = [
                            'medium_large' => [
                                'src' => data_get($linkConfig, 'image.url')
                            ]
                        ];
                    }

                    return $post;
                }

                // No post was found, it may be an external link
                if (!str_contains($linkPermalink, 'WP_HOME')) {
                    return (object)[
                        'url'   => $linkPermalink,
                        'title' => data_get($linkConfig, 'link.title'),
                        'thumbnails' => [
                            'medium_large' => [
                                'src' => data_get($linkConfig, 'image.url')
                            ]
                        ],
                        'target' => data_get($linkConfig, 'link.target')
                    ];
                }
            });
        }
        else if ('submenu_tabs' === $submenu->layout) {
            $submenu->tabs = collect(get_field('submenu_layout_tabs', $wpPost->ID));

            $submenu->tabs->transform(function ($tabConfig) use ($wpPost) {
                $tabConfig = collect($tabConfig);
                $tabLayoutType = $tabConfig->get('submenu_layout_tabs_type');

                /**
                 * Tab Layout: Show Posts from Selected Category
                 */
                if ('submenu_layout_tabs_categories' === $tabLayoutType) {
                    $tabTermUrl = null;
                    $categoryPosts = collect($tabConfig->get('submenu_layout_tabs_categories'))
                        ->mapWithKeys(function ($term) use (&$tabTermUrl) {
                            $taxonomy = get_taxonomy($term->taxonomy);

                            $posts = collect(get_posts([
                                    'numberposts' => 4,
                                    'post_type' => $taxonomy->object_type,
                                    'tax_query' => [
                                        [
                                            'taxonomy' => $term->taxonomy,
                                            'field' => 'id',
                                            'terms' => $term->term_id,
                                            'include_children' => false
                                        ]
                                    ]
                                ]))
                                ->transform(function ($taxPost) {
                                    return new JWPost($taxPost);
                                });

                            $tabTermUrl = get_term_link($term);
                            // Playlists dont have a concept of categories in the frontend
                            /* if ('playlists' === $taxonomy->name) {
                                $tabTermUrl = '/jwtv';
                            } */

                            return [
                                'title' => $term->name,
                                'posts' => $posts,
                                'categoryUrl' => $tabTermUrl
                            ];
                        });

                    if ($categoryPosts->get('posts') && $categoryPosts->get('posts')->count()) {
                        return (object)[
                            'title' => $categoryPosts->get('title'),
                            'posts' => $categoryPosts->get('posts'),
                            'categoryUrl' => $tabTermUrl
                        ];
                    }
                }

                /**
                 * Tab Layout: Select Specific Posts
                 */
                if ('submenu_layout_tabs_posts' === $tabLayoutType) {
                    $curatedTitle = $tabConfig->get('tab_title');
                    $curatedPosts = collect($tabConfig->get('submenu_layout_tabs_curated'))->flatten();
                    if (!$curatedTitle) {
                        return;
                    }

                    $curatedPosts = collect($curatedPosts)
                        ->transform(function($curatedPost) {
                            return new JWPost($curatedPost);
                        });

                    return (object)[
                        'title' => $curatedTitle,
                        'posts' => $curatedPosts
                    ];
                }

                /**
                 * Tab Layout: Show Static Link
                 */
                if ('submenu_layout_tabs_link' === $tabLayoutType) {
                    $tabLinkConfig = collect($tabConfig->get('submenu_layout_tabs_links'));

                    // URL is always required
                    if (!$tabLinkConfig->get('url')) {
                        return;
                    }

                    // If both title and url were set, return that
                    if ($tabLinkConfig->get('title')) {
                        return (object)[
                            'title' => $tabLinkConfig->get('title'),
                            'url' => $tabLinkConfig->get('url')
                        ];
                    }

                    // If we can find the post, use its title
                    $postId = url_to_postid($tabLinkConfig->get('url'));

                    // Not an internal link, is external so return the url
                    if ($postId) {
                        $tabLinkPost = get_post($postId);
                        if ($tabLinkPost) {
                            return (object)[
                                'title' => $tabLinkPost->post_title,
                                'url' => $tabLinkConfig->get('url')
                            ];
                        }
                    }
                }
            });
        }

        return $submenu;
    }

    public function setSubmenu($value)
    {
        $this->submenu = $value;
    }

    /**
     * setTemplate
     *
     * Sets the template property.
     *
     * @param string $template
     *
     * @return void
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    /**
     * setId
     *
     * Sets the id property.
     *
     * @param int $id
     *
     * @return void
     */
    protected function setId(int $id): void
    {
        $this->htmlId = "menu-item-$id";
        $this->id = $id;
    }

    /**
     * setUrl
     *
     * Sets the url property.
     *
     * @param string $url
     *
     * @return void
     */
    protected function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * setName
     *
     * Sets the name property.
     *
     * @param string $title
     *
     * @return void
     */
    protected function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * setMeta
     *
     * Sets the meta property.
     *
     * @param array $meta
     *
     * @return void
     */
    protected function setMeta(array $meta): void
    {
        if (is_countable($meta["postTypes"]) && sizeof($meta["postTypes"]) > 0) {

            // as long as we have post types in our item's meta, then we
            // want to get the most recent 6 posts in the specified types.
            // there's one exception:  the read-now page.  for it, we do
            // a little more work when we find it.

            $queryArgs = [
                "post_type" => $meta["postTypes"],
                "posts_per_page" => 6,
            ];

            if ($this->template === "template-read-now.php") {
                $queryArgs = $this->addReadNowTaxQuery($queryArgs);
            }


            $query = new WP_Query($queryArgs);
            $posts = $query->get_posts();

            // we want to use our JWPost Container for these menu items.
            // we can pass the WP_Post objects we get back from our query
            // through the JWPost constructor as follows.

            $meta["posts"] = array_map(function (WP_Post $post) {
                return new JWPost($post);
            }, $posts);
        }

        $this->meta = $meta;
    }

    /**
     * addReadNowTaxQuery
     *
     * The read-now page uses a tax query to limit the posts displayed in the
     * menu to only those that match the "Read Now" settings for the homepage.
     *
     * @param array $queryArgs
     *
     * @return array
     */
    protected function addReadNowTaxQuery(array $queryArgs): array
    {
        $homepageId = get_option("page_on_front");

        $readNowTermIds = [
            get_field("read_now_category_1", $homepageId),
            get_field("read_now_category_2", $homepageId),
            get_field("read_now_category_3", $homepageId),
        ];

        // we'll filter out non-numeric term IDs from the ones we
        // selected above.  this should help us avoid blanks if someone
        // undoes the work of others.  if, after our filter, we still
        // have terms to work with, we add them to a tax query in our
        // $queryArgs.

        $readNowTermIds = array_filter($readNowTermIds, "is_numeric");

        if (sizeof($readNowTermIds) > 0) {
            $queryArgs["tax_query"] = [
                [
                    "taxonomy" => "category",
                    "terms" => $readNowTermIds,
                    "field" => "term_id",
                ],
            ];
        }

        return $queryArgs;
    }
}