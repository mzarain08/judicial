<?php

use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Homepage;

try {
	$template = new Homepage();
	$template->show('pages/homepage.twig', JudicialWatch::isDebug());
} catch (Exception $exception) {
	if (JudicialWatch::isDebug()) {
		JudicialWatch::catcher($exception);
	}

	JudicialWatch::writeLog($exception);

}