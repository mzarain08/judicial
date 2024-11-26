<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Singular\Podcast;

try {
    $podcast = new Podcast;
    $template = 'pages/singular/single-podcasts.twig';

    $podcast->show($template, JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);
}