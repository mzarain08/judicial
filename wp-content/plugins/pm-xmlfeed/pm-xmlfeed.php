<?php
	/*
	Plugin Name: Custom XML Feed - PM

	Description: It is custom feed with article list support.
	Version: 1.0
	Author: WP
	License: WP

	*/

    function do_feed_articlelist() {
        load_template( ABSPATH . '/wp-content/plugins/pm-xmlfeed/feed-template.php' );
    }
    add_action( 'do_feed_articlelist', 'do_feed_articlelist', 10, 1 );
