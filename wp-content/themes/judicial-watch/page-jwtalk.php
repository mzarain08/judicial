<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Pages\JWTalk;

try {
    return (new JWTalk())
        ->show('pages/singular/page-jwtalk.twig', JudicialWatch::isDebug());

} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}