<?php

if ( ! defined('ABSPATH') ) {
    /** Set up WordPress environment */
    require_once( dirname( __FILE__ ) . '/wp-load.php' );
}
$querystr = "
    SELECT  *
    FROM $wpdb->posts
    WHERE $wpdb->posts.post_type = 'attachment' 
    AND ($wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'inherit')
    ORDER BY $wpdb->posts.post_date DESC LIMIT 1
 ";
$pageposts = $wpdb->get_row($querystr);

// print_r($pageposts); die;
 
$pageposts->ID;
$range1 = 10001;
$range2 = 10100;
// $end = $pageposts->ID;
$end = 20000;
$pagesize = 100;

while($range1 < $end){
	$output = shell_exec('wp elasticpress index --per-page="100" --nobulk --post-type="attachment" --include="'.$range1.', '.$range2.'"');
	echo "<pre>$output</pre>";
	echo "<pre>$range1--$range2</pre>";
	$range1 = $range2 + 1;
	$range2 += $pagesize;
}

?>