<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Singular\SpecialReport;

try {
    $specialReport = new SpecialReport;
    $specialReport->show('pages/singular/single-special-report.twig', JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);
}