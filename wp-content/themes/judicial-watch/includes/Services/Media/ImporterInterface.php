<?php

namespace Engage\JudicialWatch\Services\Media;

interface ImporterInterface {
	/**
	 * import
	 *
	 * Reaches out across the internet to pluck content from other sources
	 * and add it to this site.
	 *
	 * @return void
	 */
	public function import(): void;
}