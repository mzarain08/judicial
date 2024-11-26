<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Singular\Document;

try {
    $template = new Document();
    $template->show("pages/singular/single-documents.twig", JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}