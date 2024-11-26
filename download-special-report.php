<?php
require_once(dirname(__FILE__) . '/wp-blog-header.php');

// Get post
$post = get_post($_GET['id']);
if (!$post
    || 'special_reports' !== $post->post_type
    || 'publish' !== $post->post_status
) {
    exit;
}

// Get File
$file = get_field('report', $post->ID);
if (!$file || !data_get($file, 'id')) {
    exit;
}

// Get full path
$fullFilePath = wp_get_upload_dir()['basedir'] . '/' . wp_get_attachment_metadata($file['id'])['file'];


// Output
header("HTTP/1.1 200 OK");
header('Content-Disposition: attachment; filename="'. data_get($file, 'filename').'"');
readfile($fullFilePath);
exit;