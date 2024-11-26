<?php
require_once(dirname(__FILE__) . '/wp-blog-header.php');

// Get post
$post = get_post($_GET['id']);
if (!$post
    || 'documents' !== $post->post_type
    || 'publish' !== $post->post_status
) {
    exit;
}

// Get File
$file = get_field('attachment', $post->ID);
if (!$file || !data_get($file, 'url')) {
    exit;
}

// Get full path
$fullFilePath = get_attached_file(data_get($file, 'id'));

// Output
header("HTTP/1.1 200 OK");
header('Content-Type: application/octet-stream;r');
header('Content-Disposition: attachment; filename="'. data_get($file, 'filename').'"');
readfile($fullFilePath);
exit;