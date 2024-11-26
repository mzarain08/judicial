<?php
use Engage\JudicialWatch\JudicialWatch;
use Engage\JudicialWatch\Templates\Archives\Posts;

try {
    return (new Posts())->show('pages/archives/posts.twig', JudicialWatch::isDebug());
} catch (Exception $exception) {
	if (JudicialWatch::isDebug()) {
		JudicialWatch::catcher($exception);
	}

	JudicialWatch::writeLog($exception);

}