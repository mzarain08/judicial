<?php

namespace PrestoPlayer\Pro\Blocks;

use PrestoPlayer\Plugin;
use PrestoPlayer\Support\Block;
use PrestoPlayer\Models\CurrentUser;
use PrestoPlayer\Pro\Support\Utility;
use PrestoPlayer\Pro\Services\Bunny\BunnyService;

class BunnyCDNBlock extends Block {
	/**
	 * Block name
	 *
	 * @var string
	 */
	protected $name = 'bunny';

    /**
     * Translated block title
     * 
     * @var string
     */
    protected $title;

	/**
	 * Backwards compat
	 */
	public function __construct() {

		if ( version_compare( Plugin::version(), '0.9', '<' ) ) {
			$this->template_name = 'self-hosting';
		}

		add_filter( 'presto_player/block/default_attributes', array( $this, 'addDefaultBunnyAttributes' ), 10 );
		add_filter( 'presto_player/component/attributes', array( $this, 'addBunnyComponentData' ), 10, 2 );

        $this->title = __('Bunny', 'presto-player');		
	}

	public function registerBlockType() {
		if ( ! defined( 'PRESTO_PLAYER_PLUGIN_DIR' ) ) {
			return;
		}

		// Backwards compat.
		if ( version_compare( \PrestoPlayer\Plugin::version(), '2.0.0-beta1', '<' ) ) {
			add_action(
				'wp_enqueue_scripts',
				function () {
					wp_enqueue_script( 'presto-components' );
				}
			);
			return parent::registerBlockType();
		}

		register_block_type(
			PRESTO_PLAYER_PLUGIN_DIR . 'src/admin/blocks/blocks/bunny',
			array(
				'render_callback' => array( $this, 'html' ),
			)
		);
	}

	/**
	 * Add thumbnail and preview to bunny attributes
	 *
	 * @param array $data
	 * @param array $attributes
	 * @return array
	 */
	public function addDefaultBunnyAttributes( $data ) {
		$attributes = $data['blockAttributes'];

		if ( 'bunny' !== $data['provider'] ) {
			return $data;
		}

		$data['bunny'] = array(
			'thumbnail' => ! empty( $attributes['thumbnail'] ) ? $attributes['thumbnail'] : '',
			'preview'   => ! empty( $attributes['preview'] ) ? $attributes['preview'] : '',
		);

		// maybe sign urls
		if ( ! empty( $attributes['visibility'] ) ) {
			if ( 'private' === $attributes['visibility'] ) {
				$data['bunny']['thumbnail'] = BunnyService::signURL( $data['bunny']['thumbnail'] );
				$data['bunny']['preview']   = BunnyService::signURL( $data['bunny']['preview'] );
			}
		}

		return $data;
	}

	/**
	 * Add bunny data to component
	 *
	 * @param array $attributes
	 * @return array
	 */
	public function addBunnyComponentData( $data, $attributes ) {
		if ( 'bunny' !== $attributes['provider'] ) {
			return $data;
		}
		return array_merge(
			$data,
			array(
				'bunny',
			)
		);
	}

	/**
	 * Middleware to check for user access
	 * We will load a blank video here
	 *
	 * @param array  $attributes
	 * @param string $content
	 * @return bool
	 */
	public function middleware( $attributes, $content ) {
		// if private and user cannot access video, don't load
		if ( ! empty( $attributes['visibility'] ) && 'private' === $attributes['visibility'] ) {
			if ( ! CurrentUser::canAccessVideo( ! empty( $attributes['id'] ) ? $attributes['id'] : 0 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Override attributes
	 *
	 * @param array $attributes
	 * @return array
	 */
	public function overrideAttributes( $attributes ) {
		$load = $this->middleware( $attributes, '' );

		if ( ! $load ) {
			return array();
		}

		if ( ! empty( $attributes['src'] ) ) {
			if ( Utility::isStream( $attributes['src'] ) ) {
				wp_enqueue_script( 'hls.js' );
			}
		}

		if ( ! empty( $attributes['visibility'] ) && 'private' === $attributes['visibility'] ) {
			$attributes['src'] = ! empty( $attributes['src'] ) ? BunnyService::signURL( $attributes['src'] ) : '';
		}

		return $attributes;
	}
}
