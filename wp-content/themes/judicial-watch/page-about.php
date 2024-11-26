<?php

use Engage\JudicialWatch\JudicialWatch;

try {
    $template = new \Engage\JudicialWatch\Templates\Singular\AboutPage();

    // Hack to get dynmic sidebar into a Twig template
    ob_start();
    dynamic_sidebar('subpage-sidebar');
    $sidebar = ob_get_contents();
    ob_end_clean();
    $template->mergePageContext([
        'dynamicSidebar' => $sidebar
    ]);

    // Show
    $template->show("pages/singular/page-about.twig", JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}