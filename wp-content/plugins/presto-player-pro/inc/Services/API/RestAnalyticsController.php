<?php

namespace PrestoPlayer\Pro\Services\API;

use PrestoPlayer\Support\Utility;
use PrestoPlayer\Pro\Models\Visit;

class RestAnalyticsController {

	protected $namespace = 'presto-player';
	protected $version   = 'v1';
	protected $base      = 'analytics';

	/**
	 * Register controller
	 *
	 * @return void
	 */
	public function register() {
		// rest routes
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );

		// use ajax routes for front since many security plugins block api requests...
		add_action( 'wp_ajax_presto_player_progress', array( $this, 'ajaxProgress' ) );
		add_action( 'wp_ajax_nopriv_presto_player_progress', array( $this, 'ajaxProgress' ) );
	}

	/**
	 * Save progress
	 *
	 * @return void
	 */
	public function ajaxProgress() {

		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( 'Nonce invalid', 403 );
		}

		// verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'wp_rest' ) ) {
			wp_send_json_error( 'Nonce invalid', 403 );
		}

		// video id is required
		if ( empty( $_POST['video_id'] ) ) {
			wp_send_json_error( 'You must provide a video_id', 400 );
		}

		// get visitor identification
		$id = get_current_user_id();
		$ip = Utility::getIPAddress();

		// allow blocking IP or user id
		if ( apply_filters( 'presto_player_analytics_block', false, $ip, $id ) ) {
			wp_send_json_success();
		}

		// update or create visit within the last day
		// by IP address or user id
		$visit = ( new Visit() )->updateOrCreate(
			array(
				'video_id'   => (int) $_POST['video_id'], // this video id
				'user_id'    => $id ? (int) $id : null, // maybe by user id
				'ip_address' => ! $id ? sanitize_text_field( $ip ) : null, // maybe by ip address
				'date_query' => array(
					'after'  => date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'before' => date( 'Y-m-d 23:59:59', strtotime( 'today' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'field'  => 'created_at', // when it was created
				),
			),
			array(
				'duration'   => isset( $_POST['duration'] ) ? (int) $_POST['duration'] : '',
				'ip_address' => sanitize_text_field( $ip ), // always store ip address
			)
		);

		if ( ! $visit->id ) {
			wp_send_json_error( 'Could not create visit', 500 );
		}

		if ( is_wp_error( $visit ) ) {
			wp_send_json_error( $visit->get_error_message(), 500 );
		}

		// send success
		wp_send_json_success();
	}

	/**
	 * Register rest routes
	 *
	 * @return void
	 */
	public function registerRoutes() {
		// get video timeline
		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/video/(?P<id>\d+)/timeline',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'videoTimeline' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id'      => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'context' => array(
						'default' => 'view',
					),
					'start'   => array(
						'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
					'end'     => array(
						'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
				),
			)
		);

		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/video/(?P<id>\d+)/views',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'videoViews' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id'      => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'context' => array(
						'default' => 'view',
					),
					'start'   => array(
						'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
					'end'     => array(
						'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
				),
			)
		);

		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/video/(?P<id>\d+)/average-watchtime',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'videoAverageWatchTime' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id'      => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'context' => array(
						'default' => 'view',
					),
					'start'   => array(
						'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
					'end'     => array(
						'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
				),
			)
		);

		// analytics
		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/top-users/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'topUsers' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->collectionParams(),
			)
		);

		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/top-videos/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'topVideos' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->collectionParams(),
			)
		);

		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/views/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'totalViewsByDay' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->collectionParams(),
			)
		);

		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/watch-time/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'totalWatchTimeByDay' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => $this->collectionParams(),
			)
		);

		// User Analytics
		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/user/(?P<id>\d+)/total-views',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'totalVideoViewsByUser' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id'      => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'context' => array(
						'default' => 'view',
					),
					'start'   => array(
						'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
					'end'     => array(
						'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
				),
			)
		);

		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/user/(?P<id>\d+)/average-watchtime',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'videoAverageWatchTimeByUser' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id'      => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'context' => array(
						'default' => 'view',
					),
					'start'   => array(
						'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
					'end'     => array(
						'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
				),
			)
		);

		register_rest_route(
			"$this->namespace/$this->version/$this->base",
			'/user/(?P<id>\d+)/total-watchtime',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'videoTotalWatchTimeByUser' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id'      => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'context' => array(
						'default' => 'view',
					),
					'start'   => array(
						'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
					'end'     => array(
						'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.' ),
						'type'        => 'string',
						'format'      => 'date-time',
					),
				),
			)
		);

	}

	/**
	 * Must be able to manage options to access analytics
	 *
	 * @return bool
	 */
	public function permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Nonce refresh
	 *
	 * @return string|int
	 */
	public function createNonce() {
		return wp_create_nonce( 'wp_rest' );
	}

	/**
	 * Individual Video Timeline
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function videoTimeline( \WP_REST_Request $request ) {
		$timeline = ( new Visit() )->timeline(
			array(
				'video_id' => $request['id'],
				'start'    => $request['start'],
				'end'      => $request['end'],
			)
		);

		if ( is_wp_error( $timeline ) ) {
			return $timeline;
		}

		return rest_ensure_response( $timeline );
	}

	public function videoViews( \WP_REST_Request $request ) {
		$timeline = ( new Visit() )->views(
			array(
				'video_id' => $request['id'],
				'start'    => $request['start'],
				'end'      => $request['end'],
			)
		);

		if ( is_wp_error( $timeline ) ) {
			return $timeline;
		}
		return rest_ensure_response( $timeline );
	}

	public function videoAverageWatchTime( \WP_REST_Request $request ) {
		$average = ( new Visit() )->averageWatchTime(
			array(
				'video_id' => $request['id'],
				'start'    => $request['start'],
				'end'      => $request['end'],
			)
		);

		if ( is_wp_error( $average ) ) {
			return $average;
		}

		return rest_ensure_response( $average );
	}

	/**
	 * Total Video views by particular user
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function totalVideoViewsByUser( \WP_REST_Request $request ) {
		$totalVideoViewsByUser = ( new Visit() )->totalVideoViewsByUser(
			array(
				'user_id' => $request['id'],
				'start'   => $request['start'],
				'end'     => $request['end'],
			)
		);

		if ( is_wp_error( $totalVideoViewsByUser ) ) {
			return $totalVideoViewsByUser;
		}
		$data['view'] = $totalVideoViewsByUser;

		return rest_ensure_response( $data );
	}

	/**
	 * Average watch time by particular user
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function videoAverageWatchTimeByUser( \WP_REST_Request $request ) {
		$videoAverageWatchTimeByUser = ( new Visit() )->videoAverageWatchTimeByUser(
			array(
				'user_id' => $request['id'],
				'start'   => $request['start'],
				'end'     => $request['end'],
			)
		);

		if ( is_wp_error( $videoAverageWatchTimeByUser ) ) {
			return $videoAverageWatchTimeByUser;
		}
		$data['view'] = $videoAverageWatchTimeByUser;

		return rest_ensure_response( $data );
	}

	/**
	 * Total watch time by particular user
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function videoTotalWatchTimeByUser( \WP_REST_Request $request ) {
		$videoTotalWatchTimeByUser = ( new Visit() )->videoTotalWatchTimeByUser(
			array(
				'user_id' => $request['id'],
				'start'   => $request['start'],
				'end'     => $request['end'],
			)
		);

		if ( is_wp_error( $videoTotalWatchTimeByUser ) ) {
			return $videoTotalWatchTimeByUser;
		}
		$data['view'] = $videoTotalWatchTimeByUser;

		return rest_ensure_response( $data );
	}



	/**
	 * Total views on all videos by day
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function totalViewsByDay( \WP_REST_Request $request ) {
		$views_by_day = ( new Visit() )->totalViewsByDay(
			array_filter(
				array(
					'start' => $request['start'],
					'end'   => $request['end'],
				)
			)
		);

		$total = 0;

		if ( ! empty( $views_by_day ) ) {
			foreach ( $views_by_day as $key => $day ) {
				$total                       = $total + (int) $day->total;
				$views_by_day[ $key ]->total = (int) $day->total;
			}
		}

		$response = rest_ensure_response( $views_by_day );
		$response->header( 'X-WP-Total', (int) $total );

		return $response;
	}

	/**
	 * Total watch time by day
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function totalWatchTimeByDay( \WP_REST_Request $request ) {
		$time_by_day = ( new Visit() )->totalWatchTimeByDay(
			array_filter(
				array(
					'start' => $request['start'],
					'end'   => $request['end'],
				)
			)
		);

		return rest_ensure_response( $time_by_day );
	}

	/**
	 * Total watch time by day
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function topUsers( \WP_REST_Request $request ) {
		// set args
		$args = array(
			'page'     => $request['page'],
			'per_page' => $request['per_page'],
			'start'    => $request['start'],
			'end'      => $request['end'],
		);

		$user_data = ( new Visit() )->topUsers( array_filter( $args ) );
		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		$total = $user_data['total'];

		$controller = new \WP_REST_Users_Controller();
		$users      = array();
		// attach user data
		foreach ( $user_data['data'] as $data ) {
			$user = get_user_by( 'ID', $data->user_id );

			if ( $user ) {
				// prepare user object for rest response
				$user    = $controller->prepare_item_for_response( $user, $request );
				$users[] = apply_filters(
					'presto_top_user_stats',
					array(
						'user'  => $user->data, // user object
						'stats' => array( // user stats
							array(
								'id'    => 'views_count',
								'title' => 'Views',
								/* translators: %d: visit count */
								'data'  => sprintf( __( '%d views', 'presto-player-pro' ), (int) $data->visit_count ),
							),
							array(
								'id'        => 'average_duration',
								'title'     => __( 'Avg. View Time', 'presto-player-pro' ),
								'className' => 'presto-badge',
								'data'      => Utility::human_readable_duration( gmdate( 'H:i:s', $data->average_duration ) ),
							),
						),
					)
				);
			}
		}

		$max_pages = ceil( $total / (int) $request['per_page'] ?? 10 );

		$response = rest_ensure_response( $users );
		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		return $response;
	}

	/**
	 * Total watch time by day
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function topVideos( \WP_REST_Request $request ) {
		// set args
		$args = array(
			'page'     => $request['page'],
			'per_page' => $request['per_page'],
			'start'    => $request['start'],
			'end'      => $request['end'],
			'user_id'  => $request['user_id'],
		);


		$top = ( new Visit() )->topVideos( array_filter( $args ) );

		if ( is_wp_error( $top ) ) {
			return $top;
		}

		$total      = $top['total'];
		$max_pages  = ceil( $total / (int) $request['per_page'] ?? 10 );
		$video_data = $top['data'];

		$response = rest_ensure_response( $video_data );
		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		return $response;
	}

	/**
	 * Prepare the item for response back to the client
	 * Ensures we're only passing specific fields and double-checks sanitization
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	public function prepareItemForResponse( $item, $request ) {
		$schema   = $this->visitSchema();
		$prepared = array();
		foreach ( $item as $name => $value ) {
			if ( ! empty( $schema['properties'][ $name ] ) ) {
				$prepared[ $name ] = rest_sanitize_value_from_schema( $value, $schema['properties'][ $name ], $name );
			}
		}

		return $prepared;
	}

	/**
	 * API Schema
	 *
	 * @return array
	 */
	public function visitSchema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'video',
			'type'       => 'object',
			'properties' => array(
				'id'         => array(
					'description' => esc_html__( 'Unique identifier for the object.', 'presto-player-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'user_id'    => array(
					'description' => esc_html__( 'ID of user who made the visit.', 'presto-player-pro' ),
					'type'        => 'integer',
				),
				'duration'   => array(
					'description' => esc_html__( 'Duration of the view.', 'presto-player-pro' ),
					'type'        => 'integer',
				),
				'video_id'   => array(
					'description' => esc_html__( 'ID of the video', 'presto-player-pro' ),
					'type'        => 'integer',
				),
				'ip_address' => array(
					'type'     => 'string',
					'readonly' => true,
				),
				'created_at' => array(
					'type'     => 'string',
					'readonly' => true,
				),
				'updated_at' => array(
					'type'     => 'string',
					'readonly' => true,
				),
				'deleted_at' => array(
					'type'     => 'string',
					'readonly' => true,
				),
			),
		);
	}

	public function collectionParams() {
		return array(
			'start'    => array(
				'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.', 'presto-player-pro' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'end'      => array(
				'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.', 'presto-player-pro' ),
				'type'        => 'string',
				'format'      => 'date-time',
			),
			'page'     => array(
				'description' => __( 'Get a current page', 'presto-player-pro' ),
				'type'        => 'integer',
			),
			'per_page' => array(
				'description' => __( 'Get a current page', 'presto-player-pro' ),
				'type'        => 'integer',
			),
		);
	}
}
