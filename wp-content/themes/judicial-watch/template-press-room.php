<?php
/**
 * Template Name: Press Room
 */

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Pages\PressRoom;

try {
    return (new PressRoom())->show('pages/pressroom.twig', JudicialWatch::isDebug());
} catch (Exception $exception) {
    if (JudicialWatch::isDebug()) {
        JudicialWatch::catcher($exception);
    }

    JudicialWatch::writeLog($exception);

}
