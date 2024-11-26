<?php
use GuzzleHttp\Client as GuzzleClient;

require_once '../../../../../wp-load.php';
require_once '../../../../vendor/autoload.php';
require_once '../../../../plugins/advanced-custom-fields-pro/acf.php';
require_once '../../../../../wp-admin/includes/image.php';

\set_time_limit(0);

class YoutubeCron
{
    public $channelId = 'UCGDaOZg2INC0Qg2Z203F1dA';

    public $apiKey = 'AIzaSyAMMYrdUVc3IY9Xvd7-b6D1ZOX1r3jdN0E';

    public function __construct()
    {
        date_default_timezone_set('America/New_York');
        $this->httpClient = new GuzzleClient([
            'http_errors' => false
        ]);
    }

    /**
     * Import
     *
     * The main method to import all videos. Call this to do an import.
     */
    public function import()
    {
        // Import videos that dont already exist
        $this->importVideos();
        // Download higher res thumbs where they havent already been downloaded
        $this->getHigherQualityThumbs();
        // Hide private videos from frontend
        $this->hidePrivateVideos();
    }

    /**
     * Import Videos
     *
     * This method imports all videos that do not exist.
     * All playlists are looped through for a valid playlist ID. If it exists, this method
     * will loop through all of that playlists videos via YouTube API and import them.
     */
    public function importVideos()
    {
        $playlists = collect(get_terms([
            'taxonomy'    => 'playlists',
            'post_type'   => 'videos',
            'hide_empty'  => false,
            'numberposts' => -1
        ]));

        $playlists->each(function(WP_Term $playlist) {
            $youtubePlaylistId = get_field('playlist_id', $playlist);

            if (!$youtubePlaylistId) {
                return;
            }

            $this->importVideosForPlaylistId($youtubePlaylistId, collect());
        });
    }

    /**
     * Import Videos for Playlist ID
     *
     * Given a valid Playlist ID, import the video as a custom post type.
     * Note this is a recursive function! Pass videos as the second parameter to continue to make
     * subsequent requests to the API.
     *
     * Subsequent requests are necessary because videos in playlists are paged by 50 per request.
     *
     * @param $playlistId string
     * @param $videos \Illuminate\Support\Collection
     * @param null $pageToken
     * @return mixed
     */
    public function importVideosForPlaylistId($playlistId, $videos, $pageToken = null)
    {
        $httpResponse = $this->httpClient->get(
            sprintf(
                'https://www.googleapis.com/youtube/v3/playlistItems?key=%s&playlistId=%s&part=id,snippet&maxResults=50&pageToken=%s',
                $this->apiKey,
                $playlistId,
                $pageToken
            )
        );

        $jsonResponse   = json_decode($httpResponse->getBody()->getContents());
        $responseVideos = collect($jsonResponse->items)
            ->filter(function($videoSnippet) {
                if ('Private video' === data_get($videoSnippet, 'snippet.title')) {
                    return false;
                }

                return true;
            });

        $videos = $videos->concat($responseVideos);
        if ($jsonResponse->nextPageToken) {
            return $this->importVideosForPlaylistId($playlistId, $videos, $jsonResponse->nextPageToken);
        }

        // End of pagination; import videos
        $videos->each(function($video) use ($playlistId) {
            $this->importVideoForPlaylistId($video, $playlistId);
        });

        return $videos;
    }

    /**
     * Import Video for Playlist ID
     *
     * Imports a single video for a given Playlist ID.
     * In this scenario, $video is a response object from the YouTube API.
     *
     * @param $video object The API video object
     * @param $playlistId string
     */
    public function importVideoForPlaylistId($video, $playlistId)
    {
        /**
         * First, check if video was already imported
         */
        $youtubeId = $video->snippet->resourceId->videoId;
        $existingPost = new WP_Query([
            'post_type'  => 'videos',
            'meta_key'   => 'video_id',
            'meta_value' => $youtubeId
        ]);
        if ($existingPost->post_count) {
            return;
        }

        /**
         * Import
         */
        $postTitle   = html_entity_decode($video->snippet->title);
        $postContent = $video->snippet->description;

        // Convert post content URLs to links
        $urlRegex    = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
        $postContent = preg_replace($urlRegex, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $postContent);

        // Date
        $timestamp = ($temp = strtotime($video->snippet->publishedAt)) !== false ? $temp : time();
        $postDate  = date('Y-m-d H:i:s', $timestamp);

        // Create post
        $postId = wp_insert_post([
            'post_title'   => $postTitle,
            'post_content' => $postContent,
            'post_date'    => $postDate,
            'post_status'  => 'publish',
            'post_type'    => 'videos',
            'post_author'  => 0,
        ], true);

        // Playlist
        $playlist = collect(get_terms('playlists', [
            'hide_empty' => false,
            'meta_key'   => 'playlist_id',
            'meta_value' => $playlistId
        ]))->first();

        if ($playlist) {
            wp_set_post_terms($postId, [$playlist->term_id], 'playlists');
        }

        // Import thumbnail
        $maxres = data_get($video, 'snippet.thumbnails.maxres.url') ?? data_get($video, 'snippet.thumbnails.standard.url');
        $medres = data_get($video, 'snippet.thumbnails.high.url') ?? data_get($video, 'snippet.thumbnails.medium.url');
        $lowres = data_get($video, 'snippet.thumbnails.default.url');
        $thumbnail = $maxres ?? $medres ?? $lowres;

        if (!empty($thumbnail)) {
            $remoteImageDownloader = new \Engage\JudicialWatch\Services\Media\RemoteImageDownloader($thumbnail);
            $attachmentId = $remoteImageDownloader->download();

            if ($attachmentId) {
                set_post_thumbnail($postId, $attachmentId);
            }
        }

        // Update meta
        update_field('video_id', $video->snippet->resourceId->videoId, $postId);
    }

    /**
     * Hide Private Videos
     *
     * This is a helper method to hide private videos.
     * This is not used in the import process, as they are ignored automatically.
     * This should only be used to fix data if necessary.
     */
    public function hidePrivateVideos()
    {
        $videos = collect(get_posts([
            'post_type'   => 'videos',
            'numberposts' => -1
        ]));

        $videos->each(function(WP_Post $video) {
            if ('Private video' === $video->post_title) {
                wp_update_post([
                    'ID'          => $video->ID,
                    'post_status' =>  'draft'
                ]);
            }
        });
    }

    /**
     * Get Higher Quality Thumbs
     *
     * The API does not return high quality thumbnails.
     * As a result, we call the ytimg.com domain directly with an HTTP request - there is no official API for this.
     */
    public function getHigherQualityThumbs()
    {
        $videos = collect(get_posts([
            'post_type'   => 'videos',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key'     => 'has_maxres_thumb',
                    'compare' => 'NOT EXISTS',
                ]
            ]
        ]));

        $videos->each(function(WP_Post $video) {
            $videoId = get_field('video_id', $video);
            if (!$videoId) {
                return;
            }

            $maxresRequest = $this->httpClient->get(sprintf('http://i3.ytimg.com/vi/%s/maxresdefault.jpg', $videoId));
            if (200 === $maxresRequest->getStatusCode()) {
                $remoteImageDownloader = new \Engage\JudicialWatch\Services\Media\RemoteImageDownloader(
                    sprintf('http://i3.ytimg.com/vi/%s/maxresdefault.jpg', $videoId)
                );
                $attachmentId = $remoteImageDownloader->download();
                if ($attachmentId) {
                    set_post_thumbnail($video->ID, $attachmentId);
                }
                // Add meta field to identify a maxres was downloaded
                add_post_meta($video->ID, 'has_maxres_thumb', 1);
                return;
            }

            $highresRequest = $this->httpClient->get(sprintf('http://i3.ytimg.com/vi/%s/hqdefault.jpg', $videoId));
            if (200 === $highresRequest->getStatusCode()) {
                $remoteImageDownloader = new \Engage\JudicialWatch\Services\Media\RemoteImageDownloader(
                    sprintf('http://i3.ytimg.com/vi/%s/hqdefault.jpg', $videoId)
                );
                $attachmentId = $remoteImageDownloader->download();
                if ($attachmentId) {
                    set_post_thumbnail($video->ID, $attachmentId);
                }
                // Add meta field to identify a maxres was downloaded
                add_post_meta($video->ID, 'has_maxres_thumb', 1);
            }
        });
    }
}

(new YoutubeCron)->import();