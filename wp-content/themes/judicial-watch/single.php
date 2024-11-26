<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Singular\Post;

try {
	$template = new Post();
	
    // Hack to get dynmic sidebar into a Twig template
    ob_start();
    dynamic_sidebar('read_now');
    $sidebar = ob_get_contents();
    ob_end_clean();
    $template->mergePageContext([
        'dynamicSidebar' => $sidebar
    ]);	
	$template->show("pages/singular/post.twig", JudicialWatch::isDebug());
} catch (Exception $exception) {
	if (JudicialWatch::isDebug()) {
		JudicialWatch::catcher($exception);
	}

	JudicialWatch::writeLog($exception);

}