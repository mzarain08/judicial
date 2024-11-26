<?php
use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Archives\Documents;

try {
    $term = get_queried_object();
    $docsTemplate = (new Documents);
    $docsTemplate = $docsTemplate->setPageContextByKey('title', $term->name . ' Tags');

    return $docsTemplate->show('pages/archives/documents.twig', JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}