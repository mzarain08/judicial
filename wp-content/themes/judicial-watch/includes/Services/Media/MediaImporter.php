<?php

namespace Engage\JudicialWatch\Services\Media;

use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Hooks\HookException;
use Engage\JudicialWatch\JudicialWatch;

class MediaImporter extends AbstractHandler {
	/**
	 * initialize
	 *
	 * Prepares the cron jobs for the import of our podcasts and videos.
	 *
	 * @return void
	 * @throws HookException
	 */
	public function initialize() {

	}

	/**
	 * importPodcasts
	 *
	 * Imports podcasts from Buzzsprout.
	 *
	 * @return void
	 */
	protected function importPodcasts() {
		$this->import(new PodcastImporter());
	}

	/**
	 * import
	 *
	 * Given an ImporterInterface object, call it's import method and
	 * catch any MediaExceptions it throws.
	 *
	 * @param ImporterInterface $importer
	 *
	 * @return void
	 */
	protected function import(ImporterInterface $importer) {
		try {

			// the $importer can handle all of the work for us.  it'll
			// reach out across the Internet to pluck the information we
			// need about our media feed and creates the appropriate posts
			// in the database for new information.

			$importer->import();
		} catch (MediaException $e) {

			// if we catch a MediaException here, we just write the
			// information about the failure to our log.  we don't want
			// a visitor's experience to be hampered by some sort of
			// networking glitch.

			JudicialWatch::writeLog($e);
		}
	}

	/**
	 * importVideos
	 *
	 * Imports videos from YouTube.
	 *
	 * @return void
	 */
	protected function importVideos() {
		$this->import(new VideoImporter());
	}
}