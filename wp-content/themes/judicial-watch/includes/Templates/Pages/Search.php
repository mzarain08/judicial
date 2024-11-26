<?php

namespace Engage\JudicialWatch\Templates\Pages;

use Engage\JudicialWatch\Containers\JWCategory;
use Engage\JudicialWatch\Containers\JWPost;
use Engage\JudicialWatch\Templates\Archives\Posts;
use WP_Term;

class Search extends Posts
{

    protected function addToThisPageContext()
    {
        global $wp;
		//print_r($wp);die;
		$wp_request = explode("/",$wp->request );
		$current_url = home_url( $wp_request[0]  );
        $this->context['page'] = [
            'searchText' => $_GET['s'],
            'isPosts' => !$this->isTaxonomyArchive() && get_post_type() === 'post',
            'filters' => $this->getSearchFilters(),
            'posts' => $this->getPosts(),
            'pagination' => $this->getPagination(),
            'title' => 'Search Page | Judicial Watch',
            'category' => data_get($_GET, 'category'),
            'current_cat_url' => $current_url."/"
        ];
    }

    protected function getPosts(): array
    {
        global $wp_query;

        $queryVars = ['s' => data_get($_GET,' s')];
        $termQuery = data_get($_GET, 'category');

        $wp_query->query['orderby'] = 'date';
        $wp_query->query['order'] = 'DESC';

        if ($termQuery) {
            $wp_query->query['tax_query'] = [
                [
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => $termQuery
                ]
            ];

        }

        return array_map(function (\WP_Post $post) {

            // for each posts in this loop, we just send it through the
            // JWPost constructor.  this sets things up for us in the way
            // we need it for our template and we rely on the internal
            // WordPress query var handling which keeps things simple for
            // us.


            return new JWPost($post);
        }, query_posts(wp_parse_args($wp_query->query)));
    }

    public function getSearchFilters()
    {
        global $wp;
        $baseUrl      = home_url($wp->request);
        $searchText   = data_get($_GET, 's');
        $queryTermId  = data_get($_GET, 'category');

        $categories = collect($this->getFilters())
            ->transform(function (JWCategory $category) use ($baseUrl, $searchText, $queryTermId) {
                $category->searchFilterUrl = sprintf(
                    '%s?s=%s&category=%s',
                    $baseUrl, $searchText, $category->termId
                );

                if ($category->termId === (int)$queryTermId) {
                    $category->setIsActiveFilter(true);
                }

                return $category;
            });

        return $categories;
    }
}