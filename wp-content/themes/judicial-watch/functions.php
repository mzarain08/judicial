<?php

require_once WP_CONTENT_DIR . "/vendor/autoload.php";
require_once WP_CONTENT_DIR . "/plugins/timber-library/timber.php";

use Engage\JudicialWatch\JudicialWatch;
// use Timber\Timber;

require get_theme_file_path('includes/helpers.php');

Timber::$locations = get_stylesheet_directory() . "/templates/";

try {



    if (JudicialWatch::isDebug()) {

        // Dash's Nginx virtual machine has the former, not the latter.
        // for his development, we need this filter, but for the live
        // site, we probably don't.  thus, the if-condition.

        add_filter('wp_image_editors', function () {
            return ['WP_Image_Editor_GD', 'WP_Image_Editor_Imagick'];
        });
    }

    // so that our various templates can "talk" to each other through the
    // medium of the JudicialWatch theme object, we instantiate that object
    // using the name it maintains as the $theme static, public property.
    // then, through the magic of variable variables, it can be used both
    // here and elsewhere by referencing that property.

    /** @var JudicialWatch $theme */

    ${JudicialWatch::$theme} = new JudicialWatch();
    $theme = ${JudicialWatch::$theme};
    $theme->initialize();

} catch (Throwable $exception) {

    // if we're debugging at this time, we'll just re-throw the exception.
    // this'll have it appearing on-screen so that we can find and fix the
    // error.  otherwise, we'll write the problem to the log.

    if (JudicialWatch::isDebug()) {
        /** @noinspection PhpUnhandledExceptionInspection */

        throw $exception;
    }

    JudicialWatch::writeLog($exception);
}
//code csk for hidden
function wpb_custom_post_status()
{
    register_post_status('rejected', array(
        'label' => _x('Hidden', 'post'),
        'public' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>'),
    ));
}
add_action('init', 'wpb_custom_post_status');

// Using jQuery to add it to post status dropdown
add_action('admin_footer-post.php', 'wpb_append_post_status_list');
function wpb_append_post_status_list()
{
    global $post;
    $complete = '';
    $label = '';
    if ($post->post_type == 'post') {
        if ($post->post_status == 'rejected') {
            $complete = ' selected="selected"';
            $label = '<span id="post-status-display"> Hidden</span>';
        }
        echo '
<script>
jQuery(document).ready(function($){
$("select#post_status").append("<option value=\"rejected\" ' . $complete . '>Hidden</option>");
$(".misc-pub-section label").append("' . $label . '");
});
</script>
';
    }
}
//For petition post
function wpb_custom_post_status_new()
{
    register_post_status('rejected', array(
        'label' => _x('Hidden', 'petitions'),
        'public' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>'),
    ));
}
add_action('init', 'wpb_custom_post_status_new');

// Using jQuery to add it to post status dropdown
add_action('admin_footer-post.php', 'wpb_append_post_status_list_new');
function wpb_append_post_status_list_new()
{
    global $post;
    $complete = '';
    $label = '';
    if ($post->post_type == 'petitions') {
        if ($post->post_status == 'rejected') {
            $complete = ' selected="selected"';
            $label = '<span id="post-status-display"> Hidden</span>';
        }
        echo '
<script>
jQuery(document).ready(function($){
$("select#post_status").append("<option value=\"rejected\" ' . $complete . '>Hidden</option>");
$(".misc-pub-section label").append("' . $label . '");
});
</script>
';
    }
}

//For donation_pages post
function wpb_custom_post_status_donation()
{
    register_post_status('rejected', array(
        'label' => _x('Hidden', 'donation_pages'),
        'public' => true,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>'),
    ));
}
add_action('init', 'wpb_custom_post_status_donation');

// Using jQuery to add it to post status dropdown
add_action('admin_footer-post.php', 'wpb_append_post_status_list_donation');
function wpb_append_post_status_list_donation()
{
    global $post;
    $complete = '';
    $label = '';
    if ($post->post_type == 'donation_pages') {
        if ($post->post_status == 'rejected') {
            $complete = ' selected="selected"';
            $label = '<span id="post-status-display"> Hidden</span>';
        }
        echo '
<script>
jQuery(document).ready(function($){
$("select#post_status").append("<option value=\"rejected\" ' . $complete . '>Hidden</option>");
$(".misc-pub-section label").append("' . $label . '");
});
</script>
';
    }
}



function custom_post_order($query)
{
/*
Set post types.
get_post_types => set "name" if you want to apply for all custom post types
*/
$post_types = get_post_types('', 'names');

$post_type = $query->get('post_type');

if (in_array($post_type, $post_types)) {
if ($query->get('orderby') == '') {
$query->set('orderby', 'post_date');
}
/* Post Order: ASC or DESC */
if ($query->get('order') == '') {
$query->set('order', 'DESC');

}
 // fix display number of post in search result
if (isset($query->query['posts_per_page'])) {
$query->set('posts_per_page', $query->query['posts_per_page']);
} 

}
}
if (is_admin()) {
add_action('pre_get_posts', 'custom_post_order');
}

if (!function_exists('extend_admin_search')) {
    add_action('admin_init', 'extend_admin_search');

    /**
     * hook the posts search if we're on the admin page for our type
     */
    function extend_admin_search() {
        global $typenow;
        if ($typenow === 'documents') {
            add_filter('posts_search', 'posts_search_custom_post_type', 10, 2);
        }
    }

    /**
     * add query condition for custom meta
     * @param string $search the search string so far
     * @param WP_Query $query
     * @return string
     */
    function posts_search_custom_post_type($search, $query) {
        global $wpdb;
        //$search['tag'] = $query->query['s'];
        if ($query->is_main_query() && !empty($query->query['s'])) {
            $sql    = "
            or exists (
                select * from {$wpdb->postmeta} where post_id={$wpdb->posts}.ID
                and meta_key in ('tag')
                and meta_value like %s
            )
        ";
            $like   = '%' . $wpdb->esc_like($query->query['s']) . '%';
           $search = preg_replace("#\({$wpdb->posts}.tag LIKE [^)]+\)\K#",
               $wpdb->prepare($sql, $like), $search);
        }
        //echo '<pre>';
        if(isset($query->query['document_tags'])){
            $qv = explode('+',$query->query['document_tags']);
            $params = array();
            foreach($qv as $qw){
                if(ctype_digit($qw)){
                    $params[] = "#" . $qw;
                }else{
                    $params[] = $qw;
                }
            	
            }
            $query->query_vars['document_tags'] = implode('-', $params);
            $query->query['document_tags'] = implode('-', $params);
        }
        //echo $wpdb->last_query;die;
        //print_r($query);die;
        return $search;
    }
}
function rudr_posts_taxonomy_filter() {
    global $typenow; // this variable stores the current custom post type
    if( $typenow == 'documents' ){ // choose one or more post types to apply taxonomy filter for them if( in_array( $typenow  array('post','games') )
        $taxonomy_names = array('document_tags');
        foreach ($taxonomy_names as $single_taxonomy) {
            $current_taxonomy = isset( $_GET[$single_taxonomy] ) ? $_GET[$single_taxonomy] : '';
            $taxonomy_object = get_taxonomy( $single_taxonomy );
            $taxonomy_name = strtolower( $taxonomy_object->labels->name );
            $taxonomy_terms = get_terms( $single_taxonomy );
            if(count($taxonomy_terms) > 0) {
                echo "<input type='text' name='$single_taxonomy' placeholder='Document Tags' id='$single_taxonomy' class='postform' value='$current_taxonomy' />";
            }
        }
    }
}
 
add_action( 'restrict_manage_posts', 'rudr_posts_taxonomy_filter' );

function kc_add_taxonomy_filters() {
global $typenow;

// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
$my_taxonomies = array(  'post_tag' );
switch($typenow){

    case 'post':

        foreach ($my_taxonomies as $tax_slug) {
            $current_taxonomy = $_GET['tag'];
            $tax_obj = get_taxonomy($tax_slug);
            $tax_name = $tax_obj->labels->name;
            $terms = get_terms($tax_slug);
            if(count($terms) > 0) {
                echo "<select name='tag' id='$tax_slug' class='postform alignleft actions'>";
                echo "<option value=''>Show All $tax_name</option>";
                foreach ($terms as $term) {
                    echo '<option value="', $term->slug,'" ',selected( @$_GET['tag'] == $term->slug , $current = true, $echo = false ) , '>' , $term->name ,' (' , $term->count ,')</option>';
                }
                echo "</select>";
            }

        }
    // case 'documents':
    //     $my_taxonomies = array(  'document_tags' );
    //     foreach ($my_taxonomies as $tax_slug) {
    //         $current_taxonomy = $_GET['document_tags'];
    //         $tax_obj = get_taxonomy($tax_slug);
    //         $tax_name = $tax_obj->labels->name;
    //         $terms = get_terms($tax_slug);
    //         if(count($terms) > 0) {
    //             echo "<select name='document_tags' id='$tax_slug' class='postform alignleft actions'>";
    //             echo "<option value=''>Show All $tax_name</option>";
    //             foreach ($terms as $term) {
    //                 echo '<option value="', $term->slug,'" ',selected( @$_GET['document_tags'] == $term->slug , $current = true, $echo = false ) , '>' , $term->name ,' (' , $term->count ,')</option>';
    //             }
    //             echo "</select>";
    //         }

    //     }


    break;
}
}
add_action( 'restrict_manage_posts', 'kc_add_taxonomy_filters', 100 );

add_filter( 'posts_where', 'extend_wp_query_where', 10, 2 );
function extend_wp_query_where( $where, $wp_query ) {
    if ( $extend_where = $wp_query->get( 'extend_where' ) ) {
        $where .= " AND " . $extend_where;
    }
    return $where;
}
// Disable the Gravity Forms Widget in WordPress Dashboard
function ad_remove_dashboard_widgets() {

	remove_meta_box('rg_forms_dashboard','dashboard','normal');
}
add_action('wp_dashboard_setup', 'ad_remove_dashboard_widgets' );

function custom_widgets_init() {

    register_sidebar( array(
        'name'          => 'Read Now Sidebar',
        'id'            => 'read_now',
        'before_widget' => '<div>',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="rounded">',
        'after_title'   => '</h2>',
    ) );

}
add_action( 'widgets_init', 'custom_widgets_init' );


function custom_admin_css() {
    $screen = get_current_screen();
    if ($screen->base === 'plugins') {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('the-list');
            if (tbody) {
                const rows = tbody.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].innerHTML.includes('Timber')) {
                        if (rows[i + 1]) {
                            rows[i + 1].style.display = 'none';
                        }
                        break;
                    }
                }
            }
        });
        </script>";
    }
}
add_action('admin_head', 'custom_admin_css');

