<?php

namespace PrestoPlayer\Pro\Services\EmailCollection;

use PrestoPlayer\Models\AudioPreset;
use PrestoPlayer\Models\Preset;

class EmailCollectionAjaxHandler {

	/**
	 * Register actions
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_presto_player_email_submit', array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_presto_player_email_submit', array( $this, 'handle' ) );
	}

	/**
	 * Handle form submission
	 *
	 * @return void
	 */
	public function handle() {

		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( 'Nonce invalid', 403 );
		}

		// verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'wp_rest' ) ) {
			return wp_send_json_error( 'Nonce invalid', 403 );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		// validate request
		$this->validate( $email );

		// silently fail for these
		if ( empty( $_POST['video_id'] ) ) {
			return wp_send_json_error( 'This video does not have an id.' );
		}
		if ( empty( $_POST['preset_id'] ) ) {
			return wp_send_json_error( 'You must send a preset.' );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : '';

		// prepare data
		$data = $this->prepareData( $_POST );

		// get or save submission
		$saved = $this->getOrSave( $data );

		// Get Preset
		if ( 'audio' === $provider ) {
			$preset = new AudioPreset( $data['preset_id'] );
		} else {
			$preset = new Preset( $data['preset_id'] );            
		}

		/**
		 * Use this hook to collect form submission data
		 * 
		 * $save array Data to save
		 * $post WP_Post Submission Post
		 * $created boolean whether the submission was created (true) or not (false)
		 */
		do_action( 'presto_player/pro/forms/save', $data, $preset, $saved['post'], $saved['created'] );

		// send success
		wp_send_json_success( $saved );
	}

	/**
	 * Prepare and sanitize data
	 *
	 * @param array $data
	 * @return array
	 */
	public function prepareData( $data ) {
		return array(
			'email'     => sanitize_email( $data['email'] ),
			'firstname' => ! empty( $data['firstname'] ) ? sanitize_text_field( $data['firstname'] ) : '',
			'lastname'  => ! empty( $data['lastname'] ) ? sanitize_text_field( $data['lastname'] ) : '',
			'video_id'  => ! empty( $data['video_id'] ) ? (int) $data['video_id'] : null,
			'preset_id' => ! empty( $data['preset_id'] ) ? (int) $data['preset_id'] : null,
		);
	}

	/**
	 * Checks for duplicate submission before adding one
	 *
	 * @return array [$post, $created]
	 */
	public function getOrSave( $save ) {
		// check for duplicates
		$saved = get_posts(
			array(
				'post_type'  => 'pp_email_submission',
				'meta_query' => array(
					array(
						'key'     => 'email',
						'value'   => sanitize_email( $save['email'] ),
						'compare' => '=',
					),
				),
			)
		);

		// already captured
		if ( ! empty( $saved[0] ) ) {
			return array(
				'post'    => $saved[0],
				'created' => false,
			);
		}

		// add submission
		$saved = wp_insert_post(
			array(
				'post_type'   => 'pp_email_submission',
				'post_status' => 'publish',
				'meta_input'  => $save,
			)
		);

		$saved = get_post( $saved );

		return array(
			'post'    => $saved,
			'created' => true,
		);
	}

	/**
	 * Validate submission
	 *
	 * @return void
	 */
	public function validate( $email ) {
		$errors = array();

		// email is not valid
		if ( empty( $email ) ) {
			$errors[] = __( 'Please enter a valid email address', 'presto-player' );
		}

		// let extendions add additional validation errors
		$errors = apply_filters( 'presto_player/pro/forms/validation', $errors );

		// we have validation errors
		if ( ! empty( $errors ) ) {
			wp_send_json_error( $errors, 400 );
		}

		return true;
	}
}
