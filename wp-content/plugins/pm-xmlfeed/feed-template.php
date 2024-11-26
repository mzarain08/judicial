<?php
/**
 * Custom XML feed generator template.
 * Modify it to fit your needs.
 */

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

?>

<content>
    <article-list>
	<?php while( have_posts()) : the_post(); ?>
        <?php
        $author = $wpdb->get_row("SELECT display_name FROM $wpdb->users WHERE ID = '" . $post->post_author . "'");
        $time    = strtotime($post->post_date);
        $date    = str_replace(date('P', $time), '', date('c', $time));
        $content = $post->post_content;
        $content = apply_filters('the_content', $content);

        $categories = get_categories(array(
            'orderby' => 'name',
            'parent'  => 0
        ));

        ?>

        <item>
            <title><![CDATA[<?php echo get_the_title(); ?>]]></title>
            <date><![CDATA[<?php echo $date; ?>]]></date>
            <?php if (has_post_thumbnail()) : ?>
                <image><![CDATA[<?php the_post_thumbnail_url(); ?>]]></image>
                <thumbnail><![CDATA[<?php the_post_thumbnail_url('thumbnail'); ?>]]></thumbnail>
            <?php endif; ?>
            <snippet><![CDATA[<?php the_excerpt_rss(); ?>]]></snippet>
            <custom_fields>
                <body><![CDATA[<?php echo trim($content); ?>]]></body>
                <tracking-code><![CDATA[]]></tracking-code>
                <embed><![CDATA[]]></embed>
            </custom_fields>
            <url><![CDATA[<?php the_permalink(); ?>]]></url>
            <publisher_domain><![CDATA[<?php echo home_url(); ?>]]></publisher_domain>
            <publisher><![CDATA[<?php bloginfo('name'); ?>]]></publisher>
            <publisher_author><![CDATA[<?php echo isset($author->display_name) ? $author->display_name : ''; ?>]]></publisher_author>
            <publisher_url><![CDATA[<?php the_permalink(); ?>]]></publisher_url>
            <?php if (!empty($categories)) { ?>
                <topics>
                    <topic>
                        <type><![CDATA[Category Tags]]></type>
                        <?php foreach ($categories as $category) { ?>
                            <name><![CDATA[<?php echo $category->name; ?>]]></name>
                        <?php } ?>
                    </topic>
                </topics>
            <?php } ?>
        </item>
	<?php endwhile; ?>
    </article-list>
</content>
