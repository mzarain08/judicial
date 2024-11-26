<?php

namespace Engage\JudicialWatch\Services\Media;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Engage\JudicialWatch\JudicialWatch;
use Latitude\QueryBuilder\Engine\MySqlEngine;
use Latitude\QueryBuilder\QueryFactory;
use function Latitude\QueryBuilder\field;
use stdClass;
use WP_Term;


class VideoImporter implements ImporterInterface  {
	/**
	 * @var array
	 */
	protected $importLog = [];

	/**
	 * @var string
	 */
	protected $channel = '';

	/**
	 * @var string
	 */
	protected $key = '';

	/**
	 * @var WP_Term
	 */
	protected $playlist;

	/**
	 * @var array
	 */
	protected $playlistVideos = [];

	/**
	 * Videos constructor.
	 */
	public function __construct() {
		$this->channel = get_field('youtube_channel_id', 'option');
		$this->key = get_field('youtube_server_api_key', 'option');

		// we store a log of the most recently imported video from our
		// prior import in the database.  this allows us to preemptively
		// quit our loops below before we do a bunch of unnecessary work.
		// we'll get that log now.

		$this->importLog = $this->getImportLog();
	}

	/**
	 * getLog
	 *
	 * Extracts the import log from our site options and returns it.
	 *
	 * @return array
	 */
	protected function getImportLog(): array {
		global $wpdb;

		// to quickly get a log of all videos that have been imported, we
		// can just query the postmeta table selecting all video IDs therein.
		// doing this once now is a lot faster than doing it for each video
		// one at a time later.

		$factory = new QueryFactory(new MySqlEngine());

		$query = $factory
			->select("meta_value")
			->addColumns("post_id")
			->from($wpdb->postmeta)
			->where(field("meta_key")->eq("video_id"))
			->compile();

		// the QueryFactory uses PDO style placeholders but the WPDB wants
		// %s for strings and not the more typical question mark.  we'll do
		// a string replacement here before passing this all through the
		// WPDB prepare() method and running our query.

		$sql = str_replace("?", "%s", $query->sql());
		$statement = $wpdb->prepare($sql, $query->params());
		$videos = $wpdb->get_results($statement);

		// we want to create a lookup table as our log.  the YouTube video
		// IDs will become our keys and the post IDs that are "linked" to
		// them are the values.  we'll walk our array and break it up based
		// on that structure.

		$log = [];
		array_walk($videos, function($video) use (&$log) {
			$log[$video->meta_value] = $video->post_id;
		});

		return $log;
	}

	/**
	 * import
	 *
	 * Uses the YouTube API to gather information about play lists and the
	 * videos they contain so we can import them as posts here.
	 *
	 * @return void
	 * @throws MediaException
	 */
	public function import(): void {
		set_time_limit(600);                                 // 10 minutes
		$playlists = $this->getPlaylists();
		foreach ($playlists as $this->playlist) {

			// for each of our playlists, we want to get the list of videos
			// that are within it.  then, we begin our import process.  the
			// list is used to skip the re-import of older videos, but we
			// may still have to add them to this playlist if JW folks are
			// re-organizing things on YouTube.

			$this->playlistVideos = $this->getPlaylistVideos();
			$this->importVideos();
		}
	}

	/**
	 * getPlaylists
	 *
	 * Returns an array of YouTube playlists for which we need to import videos.
	 *
	 * @return array
	 * @throws MediaException
	 */
	protected function getPlaylists(): array {
		$parameters = [
			"key"        => $this->key,
			"channelId"  => $this->channel,
			"part"       => "id,snippet,contentDetails",
			"maxResults" => 50,
			"pageToken"  => "",
		];

		do {
			$rawPlaylists = json_decode($this->fetchPlaylistJson($parameters));

			$selectedPlaylists = get_field('youtube_playlists_to_sync', 'options');
			$selectedPlaylists = collect($selectedPlaylists)
                ->transform(function($playlistArray) {
                    return collect($playlistArray)->toArray();
                })
                ->flatten();

			$rawPlaylistItems = collect($rawPlaylists->items)
                ->filter(function($playlistItem) use ($selectedPlaylists) {
                    return $selectedPlaylists->contains($playlistItem->id);
                });

			foreach ($rawPlaylistItems as $playlist) {
				try {

					// this method returns the WP_Term objects for the data
					// we received from YouTube.  we'll add them all to the
					// array that we return below.

					$playlists[] = $this->getPlaylistTerm($playlist);

				} catch (MediaException $e) {
					JudicialWatch::catcher($e);
				}
			}

			// as long as there's a next page token in our playlist
			// information we want to iterate again and fetch that page's
			// data, too.  we'll set that token into the $parameters, and
			// then our while-condition will loop again if it's not empty.

			$parameters["pageToken"] = $rawPlaylists->nextPageToken ?? "";
		} while (!empty($parameters["pageToken"]));

		return $playlists ?? [];
	}

	/**
	 * fetchPlaylistJson
	 *
	 * Uses the Guzzle client to grab JSON from YouTube.
	 *
	 * @param array $parameters
	 *
	 * @return string
	 * @throws MediaException
	 */
	protected function fetchPlaylistJson(array $parameters): string {
		return $this->fetchJson("https://www.googleapis.com/youtube/v3/playlists?" . http_build_query($parameters));
	}

	/**
	 * fetchJson
	 *
	 * Given a url, gets JSON from YouTube.
	 *
	 * @param string $url
	 *
	 * @return string
	 * @throws MediaException
	 */
	protected function fetchJson(string $url): string {
		try {
			$client = new Client();
			$response = $client->request("get", $url);

			if ($response->getStatusCode() !== 200) {
				throw new ClientException("Fetch failed.", $response);
			}

			return $response->getBody();
		} catch (GuzzleException $e) {

			// if we can't fetch, we want to "convert" the GuzzleException
			// to one of our own MediaExceptions.

			$message = "Unable to fetch video feed.";
			throw new MediaException($message, MediaException::FETCH_FAILED);
		}
	}

	/**
	 * getPlaylistTerm
	 *
	 * Given an object describing a YouTube playlist, fetch (or create and
	 * then fetch) the WP_Term object and return it.
	 *
	 * @param stdClass $playlist
	 *
	 * @return WP_Term
	 * @throws MediaException
	 */
	protected function getPlaylistTerm(stdClass $playlist): WP_Term {
		$term = get_terms([
			'taxonomy'   => 'playlists',
			'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'playlist_id',
                    'value' => $playlist->id,
                    'compare' => '='
                ]
            ]
		]);

		if (!$term || sizeof($term) === 0) {

			// if we couldn't select any terms that match these data, then
			// we'll insert a new one.

			$term = $this->insertPlaylistTerm($playlist);
		} elseif (sizeof($term) === 1) {

			// if we select only one term, we can use it as the term that
			// is described by this playlist.

			$term = array_shift($term);
		} else {

			// if we got more than one term, we have a problem.  there must
			// be a one-to-one relationship between playlist IDs and terms,
			// so all we can do here is report about the issue.

			$message = "Duplicate playlist ids: %s.";
			$message = sprintf($message, $playlist->id);
			throw new MediaException($message, MediaException::DUPLICATE_ID);
		}

		return $term;
	}

	/**
	 * insertPlaylistTerm
	 *
	 * Given a YouTube playlist object, creates a term in the playlist
	 * taxonomy and returns it.
	 *
	 * @param stdClass $playlist
	 *
	 * @return WP_Term
	 * @throws MediaException
	 */
	protected function insertPlaylistTerm(stdClass $playlist): WP_Term {

		// there's at least two playlists with duplicate names already on the
		// JW channel.  so, before we insert, we'll first transform this one's
		// name so that we don't collide with an old one.

		$playlistTitle = $this->transformPlaylistTitle($playlist->snippet->title);

		$termIds = wp_insert_term($playlistTitle, 'playlists', [
			'description' => $playlist->snippet->description,
		]);

		if (is_wp_error($termIds)) {
			$message = 'Unable to create playlist: %s - %s.';
			$message = sprintf($message, $playlist->snippet->title, $termIds->get_error_message());
			throw new MediaException($message, MediaException::TERM_CREATION_FAILED);
		}

		// the above created our term, but we need to attach the metadata from
		// YouTube to it before we're done.  these are the playlist's YouTube
		// ID and its publication date.  we do so as follows, using ACF's
		// update_field() which requires the strange term_# format to identify
		// this as term meta and not post.

		$term = get_term_by('id', $termIds['term_id'], 'playlists');
		update_field('playlist_publication_date', $this->transformDate($playlist->snippet->publishedAt), 'term_' . $term->term_id);
		update_field('playlist_id', $playlist->id, 'term_' . $term->term_id);
		return $term;
	}

	/**
	 * transformPlaylistTitle
	 *
	 * Given a title for a playlist, we want to be sure that it doesn't
	 * collide with any others and return a new name if it does.
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	protected function transformPlaylistTitle(string $title): string {

		// to make sure we have unique names, we want to get any terms that
		// have a name like our $title.  we'll get the terms in our playlist
		// taxonomy and then filter the list based on those that are like our
		// $title.

		$terms = get_terms(['taxonomy' => 'playlists', 'hide_empty' => false]);
		$terms = array_filter($terms, function (WP_Term $term) use ($title) {
			return strpos($term->name, $title) === 0;
		});

		// now, if we have a count of terms that also began with our $title,
		// then we want to add one to that count and append it to that title.
		// this ensures that the first duplicate gets #2, the second one
		// becomes #3, and so on.  but, if no duplicates could be found, we
		// just return our $title unaltered.

		return ($termCount = sizeof($terms)) !== 0
			? sprintf('%s %d', $title, $termCount + 1)
			: $title;
	}

	/**
	 * transformDate
	 *
	 * Transforms a date in YouTube's preferred format into the one preferred
	 * by MySQL.
	 *
	 * @param string $date
	 * @param string $format
	 *
	 * @return string
	 */
	protected function transformDate(string $date, string $format = 'Y-m-d H:i:s'): string {

		// if we can convert YouTube's date into a timestamp, then we use
		// it to return our date in the specified format.  otherwise, we'll
		// just use the current date.

		date_default_timezone_set('America/New_York');
		$timestamp = ($temp = strtotime($date)) !== false ? $temp : time();
		return date($format, $timestamp);
	}

	/**
	 * getPlaylistVideos
	 *
	 * Returns an array indexed by post ID values for the current playlist.
	 *
	 * @return array
	 */
	protected function getPlaylistVideos(): array {
		$posts = get_posts([
			'fields'         => 'ids',
			'post_type'      => 'video',
			'posts_per_page' => -1,
			'tax_query'      => [
				[
					'field'    => 'id',
					'taxonomy' => 'playlist',
					'terms'    => $this->playlist->term_id,
				],
			],
		]);

		// now, to return an array that is keyed by post IDs and the
		// values are always true, we can use a combination of array
		// functions to build a new array.  array_combine() takes two
		// array parameters for keys and values.  we already have our
		// keys ($posts), so we can use array_fill() to create a
		// values array of the same size.

		return array_combine($posts, array_fill(0, sizeof($posts), true));
	}

	/**
	 * importVideos
	 *
	 * Given a playlist, get the videos on it from YouTube.
	 *
	 * @return void
	 * @throws MediaException
	 */
	protected function importVideos() {
		$parameters = [
			'key'        => $this->key,
			'part'       => 'id,snippet',
			'playlistId' => get_field('playlist_id', 'term_' . $this->playlist->term_id),
			'maxResults' => 50,
			'pageToken'  => '',
		];

		// the import of videos on a playlist is harder here than for our
		// podcasts.  because a video can be on multiple playlists, we can't
		// simply stop importing them when we encounter one that we've
		// previously grabbed.  instead, we have to go through the full
		// playlist to see what might have changed.  we don't re-import posts
		// that we already have, but we do apply new terms to those posts
		// when we have to.

		do {
			$rawVideos = json_decode($this->fetchVideoJson($parameters));

			foreach ($rawVideos->items as $video) {
				try {
				    dd($video);

					// first thing:  we have to create and/or fetch the
					// post ID for the video in our database.  once we're
					// armed with that, if our version of this playlist
					// doesn't include this video, then we'll add it by
					// linking our playlist term to the post.

					$videoId = $this->isImported($video)
						? $this->getVideoPostId($video)
						: $this->importVideo($video);

					if (!$this->isVideoOnPlaylist($videoId)) {
						$this->addVideoToPlaylist($videoId);
					}
				} catch (MediaException $e) {
					JudicialWatch::catcher($e);
				}
			}

			// just like we did when we imported playlists above, we loop for
			// as long as there are next pages to read.  the null coalescing
			// operator will protect against single-page pulls where the page
			// token wouldn't exist and final pages where it's also missing.

			$parameters["pageToken"] = $rawVideos->nextPageToken ?? "";
		} while (!empty($parameters["pageToken"]));
	}

	/**
	 * fetchVideoJson
	 *
	 * Hits YouTube's API and returns the JSON string representing videos
	 * on a playlist.
	 *
	 * @param array $parameters
	 *
	 * @return string
	 * @throws MediaException
	 */
	protected function fetchVideoJson(array $parameters): string {
		return $this->fetchJson("https://www.googleapis.com/youtube/v3/playlistItems?" . http_build_query($parameters));
	}

	/**
	 * isImported
	 *
	 * Returns true if this video is in our database.
	 *
	 * @param stdClass $video
	 *
	 * @return bool
	 */
	protected function isImported(stdClass $video): bool {

		// our log stores a look-up table of the videos that we've imported
		// in at it's "imported" index.  this is an array with keys matching
		// the YouTube playlist ID for each video and value are simply true.
		// this allows us to use isset() rather than in_array()
		// which is much faster.

		$videoId = $video->snippet->resourceId->videoId;
		$postId = $this->importLog[$videoId] ?? 0;
		$imported = $postId !== 0;

		// we imported a bunch of videos without their thumbnails.  it's also
		// possible, but unlikely, that a video could be imported before their
		// image is ready or that we have trouble grabbing an image for some
		// reason.  thus, if this video has been imported but it doesn't have
		// a thumbnail, we'll try to give it one.

		if ($imported && !has_post_thumbnail($postId)) {
			$this->importThumbnail($postId, $video);
		}

		// always return false for testing
        return false;

		return $imported;
	}

	/**
	 * getVideoPostId
	 *
	 * Given a video's description from YouTube, return the post ID for it.
	 *
	 * @param stdClass $video
	 *
	 * @return int
	 */
	protected function getVideoPostId(stdClass $video): int {

		// our importLog is a map of YouTube video IDs to WordPress post
		// IDs.  we have the former, so with our log, we can get the latter.
		// by doing this, we avoid the need to make a bunch of queries just
		// to get post IDs.

		return $this->importLog[$video->snippet->resourceId->videoId];
	}

	/**
	 * importVideo
	 *
	 * Given the YouTube object describing a video, save that information
	 * in the WordPress database.
	 *
	 * @param stdClass $video
	 *
	 * @return int
	 * @throws MediaException
	 */
	protected function importVideo(stdClass $video): int {

		// to insert our post, we will be updating both the post and it's
		// metadata here.  first we insert a new, published video post using
		// the information from YouTube as our guide.

        $postTitle = html_entity_decode($video->snippet->title);
        $postContent = $video->snippet->description;

        // Convert post content URLs to links
        $urlRegex = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
        $postContent = preg_replace($urlRegex, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $postContent);

		$postArgs = [
			'post_title'   => $postTitle,
			'post_content' => $postContent,
			'post_date'    => $this->transformDate($video->snippet->publishedAt),
			'post_status'  => 'publish',
			'post_type'    => 'videos',
			'post_author'  => 0,
		];

		$postId = wp_insert_post($postArgs, true);

		if (is_wp_error($postId)) {
			$message = 'Unable to import: %s - %s.';
			$message = sprintf($message, $video->snippet->title, $postId->get_error_message());
			throw new MediaException($message, MediaException::IMPORT_FAILED);
		}

		$this->importThumbnail($postId, $video);

		// now, we update our log to include this video and we also make sure
		// that the video's YouTube ID is in the post's metadata.  once these
		// steps are complete, we're done and can return our ID for use in the
		// calling scope.

		update_field('video_id', $video->snippet->resourceId->videoId, $postId);
		$this->updateLog($video);

		return $postId;
	}

	/**
	 * importThumbnail
	 *
	 * Given a video's information, extract the high-res version of its
	 * thumbnail and store it as this post's featured image.
	 *
	 * @param int      $postId
	 * @param stdClass $video
	 */
	protected function importThumbnail(int $postId, stdClass $video) {
		$maxres = data_get($video, 'snippet.thumbnails.maxres.url');
        $thumbnail = $maxres ?? '';

		if (!empty($thumbnail)) {
			$remoteImageDownloader = new RemoteImageDownloader($thumbnail);
			$attachmentId = $remoteImageDownloader->download();

			if ($attachmentId) {
				set_post_thumbnail($postId, $attachmentId);
			}
		}
	}

	/**
	 * isVideoOnPlaylist
	 *
	 * Given a video post ID, see if it's linked to the current playlist
	 * term.
	 *
	 * @param int $videoId
	 *
	 * @return bool
	 */
	protected function isVideoOnPlaylist(int $videoId): bool {

		// our playlistVideos property can tell us if this video's post
		// ID is currently attached to the playlist property.  like our
		// log of imported videos, we use a look-up table structure so
		// that we can use isset() here instead of in_array().

		return isset($this->playlistVideos[$videoId]);
	}

	/**
	 * addVideoToPlaylist
	 *
	 * Adds the playlist to the current playlist term.
	 *
	 * @param int $videoId
	 *
	 * @return void
	 */
	protected function addVideoToPlaylist(int $videoId) {

		// if this video was not previously attached to this playlist,
		// we'll add it here.  luckily, there's a WordPress function to do
		// that for us.  then, we add this to our playlistVideos property.
		// we don't think this is strictly necessary, but it can't hurt.
		// the true flag we send to wp_set_post_terms() makes it append,
		// rather than replace, terms.

		wp_set_post_terms($videoId, $this->playlist->term_id, "playlists", true);
		$this->playlistVideos[$videoId] = true;
	}

	/**
	 * updateLog
	 *
	 * Given YouTube's description of a video, update our log to reflect
	 * that it has been imported.
	 *
	 * @param stdClass $video
	 *
	 * @return void
	 */
	protected function updateLog(stdClass $video) {

		// to update our log, we add this video's ID to the array.  we
		// use the ID as a key so that we can use isset() to see if a
		// video is in our database rather than having to use in_array()
		// which takes longer.

		$this->importLog[$video->snippet->resourceId->videoId] = true;
	}
}