<?php

namespace Engage\JudicialWatch\Services\ACF;

use Engage\JudicialWatch\Services\Media\VideoImporter;
use Engage\WordPress\Handlers\AbstractHandler;
use Engage\WordPress\Hooks\HookException;
use GuzzleHttp\Client as GuzzleClient;

class SitewideSettings extends AbstractHandler {

    public $settings;

    /**
     * initialize
     *
     * This method hooks this object and others into the WordPress action
     * and filter API.
     *
     * @return void
     * @throws HookException
     */
    public function initialize() {
        // Load youtube playlist options
        $this->loadYoutubePlaylistsAjaxOptions();

        $this->settings = $this->getSettings();
    }

    public function loadYoutubePlaylistsAjaxOptions()
    {
        // Load ajax options from youtube on interaction
        add_filter('acf/load_field/name=youtube_playlists_to_sync', function($field) {
            return;
            /**
             * Request playlists from youtube
             */
            $youtubeChannel = get_field('youtube_channel_id', 'option');
            $youtubeKey = get_field('youtube_server_api_key', 'option');
            $guzzleClient = new GuzzleClient;
            $response = $guzzleClient->get(
                sprintf(
                    'https://www.googleapis.com/youtube/v3/playlists?key=%s&channelId=%s&part=id,snippet&maxResults=50',
                    $youtubeKey,
                    $youtubeChannel
                )
            );

            if (200 === $response->getStatusCode()) {
                $options = json_decode($response->getBody()->getContents());
                if ($options && count($options->items)) {
                    $choices = collect($options->items)
                        ->mapWithKeys(function($playlistItem) {
                            return [
                                $playlistItem->id => $playlistItem->snippet->title
                            ];
                        })
                        ->sort()
                        ->toArray();

                    $field['choices'] = $choices;
                }
            }

            return $field;
        });
    }

    public function getSettings()
    {
        $cachedSettings = get_transient('jw_acf_sitewide_settings');

        if (!$cachedSettings) {
            $objects = get_field_objects('option');
            $settings = [];

            if ($objects && is_array($objects)) {
                foreach ($objects as $keyName => $acfArray) {
                    $cachedSettings[$keyName] = (object)$acfArray;
                }
            }

            set_transient('jw_acf_sitewide_settings', $cachedSettings);
        }

        return $cachedSettings;
    }

    public function getAcfFieldValue($acfFieldName)
    {
        if ($this->settings
            && isset($this->settings->$acfFieldName)) {
            return $this->settings->$acfFieldName['value'];
        }
    }
}