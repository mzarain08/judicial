<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Singular\Petition;

try {
    $petition = new Petition;

    // Choice of single or double column layout
    $layoutType = get_field('layout_design_type', $petition->postId);
    $template = 'pages/singular/petitions/simple-layout.twig';

    if ('featured_graphic' === $layoutType) {
        $template = 'pages/singular/petitions/featured-graphic-layout.twig';
    } else if ('basic_simple' === $layoutType) {
        $template = 'pages/singular/petitions/simple-layout.twig';
    } else if ('basic_sidebar' === $layoutType) {
        $template = 'pages/singular/petitions/simple-sidebar-layout.twig';
    }

    $petition->show($template, JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);
}