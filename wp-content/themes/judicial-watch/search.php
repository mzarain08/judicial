<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Pages\Search;

try {
    $template = new Search();
    $template->show("pages/search.twig", JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}