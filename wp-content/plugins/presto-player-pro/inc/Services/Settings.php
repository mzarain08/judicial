<?php

namespace PrestoPlayer\Pro\Services;

use PrestoPlayer\Pro\Models\Bunny\PullZone;
use PrestoPlayer\Pro\Models\Bunny\StorageZone;

class Settings {

	public function register() {
		add_action( 'admin_init', array( $this, 'registerSettings' ) );
		add_action( 'rest_api_init', array( $this, 'registerSettings' ) );

		add_filter( 'pre_option_presto_player_fluentcrm', array( $this, 'filterFluent' ) );
	}

	// option will update if fluent crm is active
	public function filterFluent( $value ) {
		if ( is_array( $value ) ) {
			return array_merge( $value, [ 'connected' => is_plugin_active( 'fluent-crm/fluent-crm.php' ) ] );
		}
		return $value;
	}

	public function registerSettings() {
		\register_setting(
			'presto_player',
			'presto_player_bunny_stream_public',
			array(
				'type'         => 'object',
				'description'  => __( 'Bunny.net public video stream settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_bunny_stream_public',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'video_library_id'      => array(
								'type' => 'integer',
							),
							'video_library_api_key' => array(
								'type' => 'string',
							),
							'pull_zone_id'          => array(
								'type' => 'integer',
							),
							'pull_zone_url'         => array(
								'type' => 'string',
							),
							'token_auth_key'        => array(
								'type' => 'string',
							),
						),
					),
				),
				'default'      => array(),
			)
		);

		\register_setting(
			'presto_player',
			'presto_player_bunny_stream',
			array(
				'type'         => 'object',
				'description'  => __( 'Bunny.net stream general settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_bunny_stream',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'hls_start_level'        => array(
								'type' => 'integer',
							),
							'disable_legacy_storage' => array(
								'type' => 'boolean',
							),
						),
					),
				),
				'default'      => array(
					'hls_start_level'        => 480,
					'disable_legacy_storage' => false,
				),
			)
		);

		\register_setting(
			'presto_player',
			'presto_player_bunny_stream_private',
			array(
				'type'         => 'object',
				'description'  => __( 'Bunny.net private video stream settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_bunny_stream_private',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'video_library_id'      => array(
								'type' => 'integer',
							),
							'video_library_api_key' => array(
								'type' => 'string',
							),
							'pull_zone_id'          => array(
								'type' => 'integer',
							),
							'pull_zone_url'         => array(
								'type' => 'string',
							),
							'token_auth_key'        => array(
								'type' => 'string',
							),
						),
					),
				),
				'default'      => array(),
			)
		);

		$pull_zone = new PullZone();
		\register_setting(
			'presto_player',
			'presto_player_bunny_pull_zones',
			array(
				'type'         => 'object',
				'description'  => __( 'Bunny.net settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_bunny_pull_zones',
					'type'   => 'object',
					'schema' => array(
						'properties' => $pull_zone->getFillableSchema(),
					),
				),
				'default'      => array(),
			)
		);

		$storage_zone = new StorageZone();
		\register_setting(
			'presto_player',
			'presto_player_bunny_storage_zones',
			array(
				'type'         => 'object',
				'description'  => __( 'Bunny.net settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_bunny_storage_zones',
					'type'   => 'object',
					'schema' => array(
						'properties' => $storage_zone->getFillableSchema(),
					),
				),
				'default'      => array(),
			)
		);

		// unique install id
		\register_setting(
			'presto_player',
			'presto_player_bunny_uid',
			array(
				'type'         => 'string',
				'description'  => __( 'A generated unique install id for Bunny.net.', 'presto-player-pro' ),
				'show_in_rest' => true,
			)
		);

		// mailchimp
		\register_setting(
			'presto_player',
			'presto_player_mailchimp',
			array(
				'type'         => 'object',
				'description'  => __( 'Mailchimp settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_mailchimp',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'api_key'   => array(
								'type' => 'string',
							),
							'connected' => array(
								'type' => 'boolean',
							),
						),
					),
				),
				'default'      => array(
					'api_key'   => '',
					'connected' => false,
				),
			)
		);

		// mailerlite
		\register_setting(
			'presto_player',
			'presto_player_mailerlite',
			array(
				'type'         => 'object',
				'description'  => __( 'Mailerlite settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_mailerlite',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'api_key'   => array(
								'type' => 'string',
							),
							'connected' => array(
								'type' => 'boolean',
							),
						),
					),
				),
				'default'      => array(
					'api_key'   => '',
					'connected' => false,
				),
			)
		);

		// activecampaign
		\register_setting(
			'presto_player',
			'presto_player_activecampaign',
			array(
				'type'         => 'object',
				'description'  => __( 'ActiveCampaign settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_activecampaign',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'api_key'   => array(
								'type' => 'string',
							),
							'url'       => array(
								'type' => 'string',
							),
							'connected' => array(
								'type' => 'boolean',
							),
						),
					),
				),
				'default'      => array(
					'api_key'   => '',
					'url'       => '',
					'connected' => false,
				),
			)
		);

		// fluent crm
		\register_setting(
			'presto_player',
			'presto_player_fluentcrm',
			array(
				'type'         => 'object',
				'description'  => __( 'Is fluent crm connected', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_fluentcrm',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'connected' => array(
								'type' => 'boolean',
							),
						),
					),
				),
				'default'      => array(
					'connected' => false,
				),
			)
		);

		/**
		 * License
		 */
		\register_setting(
			'presto_player',
			'presto_player_license',
			array(
				'type'         => 'object',
				'description'  => __( 'License settings.', 'presto-player-pro' ),
				'show_in_rest' => array(
					'name'   => 'presto_player_license',
					'type'   => 'object',
					'schema' => array(
						'properties' => array(
							'key' => array(
								'type' => 'string',
							),
						),
					),
				),
				'default'      => array(
					'enable'     => false,
					'purge_data' => true,
				),
			)
		);
	}
}
