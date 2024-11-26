<?php
use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Archives\Documents;

try {
    return (new Documents())
        ->show('pages/archives/documents.twig', JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}