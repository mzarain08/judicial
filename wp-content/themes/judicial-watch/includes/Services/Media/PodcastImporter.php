<?php

/** @noinspection PhpUndefinedFieldInspection */

namespace Engage\JudicialWatch\Services\Media;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Engage\JudicialWatch\JudicialWatch;
use SimpleXMLElement;
use WP_Term;

class PodcastImporter implements ImporterInterface {
	const IMPORT_LOG = "jw-podcast-import-log";
	const PLAYER_JS = "https://www.buzzsprout.com/%d/%d.js?%s";

	/**
	 * @var string
	 */
	protected $feed = "https://feeds.buzzsprout.com/%d.rss";

	/**
	 * @var array
	 */
	protected $log = [];

	/**
	 * @var WP_Term
	 */
	protected $channel;

	/**
	 * Podcasts constructor.
	 *
	 * @param int $feedId
	 */
	public function __construct(int $feedId = -1) {
		$feedId = 177983;
		$this->feed = sprintf($this->feed, $feedId);

		// we store a record of the most recent podcast we've imported per
		// channel in the database.  this allows us quickly halt the loop that
		// inserts new podcasts when we encounter the marker that tells us

		$this->log = $this->getLog();
	}

	/**
	 * getLog
	 *
	 * The log if imported podcast episodes is stored in the database as an
	 * array keyed by channel ID numbers.  This method returns that log.
	 *
	 * @return array
	 */
	protected function getLog(): array {
		return get_option(self::IMPORT_LOG, []);
	}

	/**
	 * import
	 *
	 * Reads the RSS feed and adds any Podcasts we don't already have in our
	 * database to it.
	 *
	 * @return void
	 * @throws MediaException
	 */
	public function import(): void {
		$xml = $this->getXML();
		$xml = new SimpleXMLElement($xml);

		foreach ($xml->channel as $channel) {
            foreach ($channel->item as $episode) {
                if (!$this->isImported($episode)) {
                    $this->importEpisode($episode);
                }
            }
        }
	}

	/**
	 * getXML
	 *
	 * Uses WordPress functions to get the XML feed that describes the
	 * JW TalkNet podcasts.
	 *
	 * @return string
	 * @throws MediaException
	 */
	protected function getXML(): string {
		try {
			$client = new Client();

			// here we fetch our feed.  then, if we get one, we return it.
			// any problems and we just throw an exception that'll get caught
			// below.

			$response = $client->request("GET", $this->feed);

			if ($response->getStatusCode() !== 200) {
				throw new ClientException("Fetch failed.", $response);
			}

			return $response->getBody();
		} catch (GuzzleException $e) {

			// any GuzzleExceptions thrown in the try-block above we simply
			// "convert" into our own MediaExceptions to summarize the fact
			// that our fetch failed.

			throw new MediaException("Unable to fetch podcast feed.",
				MediaException::FETCH_FAILED);
		}
	}

	/**
	 * getChannelTerm
	 *
	 * Given the name of a channel, return (or create, then return) the
	 * WP_Term object to which it is linked.
	 *
	 * @param SimpleXMLElement $channel
	 *
	 * @return WP_Term
	 * @throws MediaException
	 */
	protected function getChannel(SimpleXMLElement $channel): WP_Term {
		$channelTitle = (string) $channel->title;
		$term = get_term_by("name", $channelTitle, "channel");

		if ($term === false) {

			// if term is false, then either the taxonomy doesn't exist
			// or the term doesn't.  since we make the taxonomy with CPT UI,
			// we can be pretty sure that's not the problem.  so, we'll try
			// to create a term for this channel.

			$termInfo = wp_insert_term($channelTitle, "channel", [
				"description" => (string) $channel->description,
			]);

			if (is_wp_error($termInfo)) {
				throw new MediaException("Channel creation failed: $channel->title.",
					MediaException::TERM_CREATION_FAILED);
			}

			// if it wasn't an error, $term is an array of term_id and
			// term_taxonomy_id.  we can use the first one to get our the
			// actual WP_Term object that we want to return.

			$term = get_term_by("id", $termInfo["term_id"], "channel");
		}

		return $term;
	}

	/**
	 * isImported
	 *
	 * Returns true if the this episode has already been imported for the
	 * current playlist.
	 *
	 * @param SimpleXMLElement $episode
	 *
	 * @return bool
	 */
	public function isImported(SimpleXMLElement $episode) : bool
    {
        /** @var $externalEpisodeGuid SimpleXMLElement */
        $externalEpisodeGuid = $this->getEpisodeId($episode);
        $wpPosts = collect(get_posts([
            'post_type'   => 'podcasts',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => 'buzzsprout_episode_id',
                    'value' => $externalEpisodeGuid,
                ]
            ]
        ]));

        return (bool)$wpPosts->count();
	}

	/**
	 * importEpisode
	 *
	 * Given an episode, import it into the database as a podcast post.
	 *
	 * @param SimpleXMLElement $episode
	 *
	 * @return void
	 * @throws MediaException
	 */
	protected function importEpisode(SimpleXMLElement $episode) {

		// in our the episode XML object, we have a title, description,
		// link, publication date, and some details about the episode file
		// itself.  this method uses that information to insert a new
		// podcast post into the database and then update some meta
		// fields about it.

		$postArgs = [
			"post_title"   => (string) $episode->title,
			"post_content" => strip_tags((string) $episode->description, '<p><a>'),
			"post_date"    => $this->transformDate((string) $episode->pubDate),
			"post_status"  => "publish",
			"post_type"    => "podcasts",
			"post_author"  => 0,
		];

		$postId = wp_insert_post($postArgs, true);

		if (is_wp_error($postId)) {
			throw new MediaException("Could not import: " . (string) $episode->title,
				MediaException::IMPORT_FAILED);
		}

		$this->importThumbnail($postId, $episode);

		// if we're still here, then we didn't throw an exception above.  that
		// means we've got a post in the database, and we can attach some
		// metadata to it.  to do this, we use ACF's update_field() function.
		// then, we add this podcast to the current channel.

		update_field("buzzsprout_link", (string) $episode->link, $postId);
		update_field("buzzsprout_episode_id", $this->getEpisodeId($episode), $postId);
		update_field("podcast_filename", $this->getEpisodeFilename($episode), $postId);
		wp_set_post_terms($postId, $this->channel->term_id, "channel");
	}

	/**
	 * transformDate
	 *
	 * Given a date in the way that Buzzsprout likes to give them to us,
	 * we return a date in the way that MySQL likes for its database.
	 *
	 * @param string $date
	 * @param string $format
	 *
	 * @return string
	 */
	protected function transformDate(string $date, string $format = "Y-m-d H:i:s"): string {

		// if we can convert YouTube's date into a timestamp, then we use
		// it to return our date in the specified format.  otherwise, we'll
		// just use the current date.

		date_default_timezone_set("America/New_York");
		$timestamp = ($temp = strtotime($date)) !== false ? $temp : time();
		return date($format, $timestamp);
	}

	/**
	 * importThumbnail
	 *
	 * Adds this episode's thumbnail to our post as its featured image.
	 *
	 * @param int              $postId
	 * @param SimpleXMLElement $episode
	 *
	 * @return void
	 */
	protected function importThumbnail(int $postId, SimpleXMLElement $episode): void {
		$thumbnail = $this->getThumbnail($episode);

		if (!empty($thumbnail)) {
			$remoteImageDownloader = new RemoteImageDownloader($thumbnail);
			$attachmentId = $remoteImageDownloader->download();

			if ($attachmentId) {
				set_post_thumbnail($postId, $attachmentId);
			}
		}
	}

	/**
	 * getThumbnail
	 *
	 * Grabs the thumbnail for an episode from a podcast's page since,
	 * unfortunately, they're not included in the XML.
	 *
	 * @param SimpleXMLElement $episode
	 *
	 * @return string
	 */
	protected function getThumbnail(SimpleXMLElement $episode): string {
		$thumbnail = "";

		try {
			$episodeFile = $this->getEpisodeFilename($episode);
			$episodeLink = str_replace(".mp3", "", $episodeFile);
			$episodeHtml = $this->getHtml($episodeLink);

			// the above work gets us the HTML for this episode's page on
			// buzzsprout.  we need to extract the og:image content from
			// those data.  best way:  regular expression.  the following
			// pattern matches the og:image meta property and the first
			// and only matching group is that property's content.

			$pattern = '/meta property="og:image" content="([^"]+)"/';
			if (preg_match($pattern, $episodeHtml, $matches)) {
				$thumbnail = $matches[1];
			}
		} catch (MediaException $e) {

			// we don't actually have to do anything here.  if we couldn't
			// fetch our HTML data, then we just discard the exception and
			// return the empty string value of $thumbnail set above.

		}

		return $thumbnail;
	}

	/**
	 * getEpisodeFilename
	 *
	 * Extracts the filename for this episode's mp3.
	 *
	 * @param SimpleXMLElement $episode
	 *
	 * @return string
	 */
	protected function getEpisodeFilename(SimpleXMLElement $episode): string {

		// in the episode XML, the link to the mp3 for this episode is in the
		// attributes of the enclosure property.  we can get those attributes
		// as follows

		$attributes = $episode->enclosure->attributes();
		return (string) $attributes["url"];
	}

	/**
	 * getHtml
	 *
	 * Given a URL, returns the HTML at it.
	 *
	 * @param string $url
	 *
	 * @return string
	 * @throws MediaException
	 */
	protected function getHtml(string $url): string {

		// this method is functionally very similar to getXml above.  see
		// it for comments as needed.

		try {
			$client = new Client();
			$response = $client->request("GET", $url);

			if ($response->getStatusCode() !== 200) {
				throw new ClientException("Fetch failed.", $response);
			}

			return $response->getBody();
		} catch (GuzzleException $e) {
			throw new MediaException("Unable to fetch podcast page.",
				MediaException::FETCH_FAILED);
		}
	}

	/**
	 * getEpisodeId
	 *
	 * Returns the episode ID which we extract from the episode's GUID.
	 *
	 * @param SimpleXMLElement $episode
	 *
	 * @return string
	 */
	protected function getEpisodeId(SimpleXMLElement $episode): string {

		// the GUID for an episode is Buzzsprout-# where the # is the ID we
		// want to grab.  to do so, we'll replace every non-numeric character
		// in this string and return the remaining digits.

		return preg_replace("/\D+/", "", (string) $episode->guid);
	}

	/**
	 * updateLog
	 *
	 * Given an episode, updates the log with it's GUID.
	 *
	 * @param SimpleXMLElement $episode
	 *
	 * @return void
	 */
	protected function updateLog(SimpleXMLElement $episode) {

		// our log is a multi-dimensional array indexed by playlist channel
		// ID and then the GUID for each episode.  the values are simply true.
		// this allows us to find imported episodes in O(1) instead of using
		// array_search() or in_array().

		$episodeGuid = (string) $episode->guid;
		//$this->log[$this->channel->term_id][$episodeGuid] = true;
	}

	/**
	 * saveLog
	 *
	 * Saves the log back in the database for use the next time that we
	 * run our import.
	 *
	 * @return void
	 */
	protected function saveLog() {
		$this->log["last-run"] = date("Y-m-d H:i:a");
		update_option(self::IMPORT_LOG, $this->log, false);
	}
}