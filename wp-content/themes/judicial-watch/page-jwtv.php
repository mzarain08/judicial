<?php

use Engage\JudicialWatch\JudicialWatch;

try {
    $template = (new \Engage\JudicialWatch\Templates\Pages\JWTV());

    $template->mergePageContext([
        'meta' => [
            'bodyStyles' => sprintf('background-image:url(%s)', $template->getContextValue('backgroundImage'))
        ]
    ]);

    return $template->show('pages/singular/page-jwtv.twig', JudicialWatch::isDebug());

} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);
}