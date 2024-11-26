<?php

use Engage\JudicialWatch\JudicialWatch;

try {
    $template = (new \Engage\JudicialWatch\Templates\Archives\Videos());
    $template->mergePageContext([
        'meta' => [
            'bodyStyles' => sprintf('background-image:url(%s)', $template->getContextValue('backgroundImage'))
        ]
    ]);
    $template->show('pages/archives/videos.twig', JudicialWatch::isDebug());

} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}