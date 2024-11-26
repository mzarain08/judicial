<?php
use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Archives\Cases;

try {
    return (new Cases)->show('pages/archives/cases.twig', JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}