<?php

namespace PrestoPlayer\Pro\Blocks;

use PrestoPlayer\Services\ReusableVideos;
use PrestoPlayer\Playlist;
use PrestoPlayer\Services\PreloadService;

/**
 * Playlist Block.
 */
class PlaylistBlock {

	public static $instance = 0;

	/**
	 * Register Block
	 *
	 * @return void
	 */
	public function register() {
		if ( ! defined( 'PRESTO_PLAYER_PLUGIN_DIR' ) ) {
			return;
		}
		// the block.json does not exist.
		if ( ! file_exists( PRESTO_PLAYER_PLUGIN_DIR . 'src/admin/blocks/blocks/playlist' ) ) {
			return;
		}
		register_block_type(
			PRESTO_PLAYER_PLUGIN_DIR . 'src/admin/blocks/blocks/playlist',
			array(
				'render_callback' => array( $this, 'renderBlock' ),
			)
		);
	}

	/**
	 * Get block classes
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function getClasses( $attributes ) {
		$block_alignment = isset( $attributes['align'] ) ? sanitize_text_field( $attributes['align'] ) : '';
		return ! empty( $block_alignment ) ? 'align' . $block_alignment : '';
	}

	/**
	 * Map playlist block to playlist config
	 *
	 * @param [type] $block
	 * @return void
	 */
	public function mapPlaylistConfig( $block ) {
		// if video_id is empty, skip.
		if ( empty( $block['attrs']['id'] ) ) {
			return;
		}

		// get the media hub block.
		$media = parse_blocks( ReusableVideos::get( $block['attrs']['id'] ) );

		// get nested or this block.
		$video = $media[0]['innerBlocks'][0] ?? $media[0] ?? null;

		// must have a video block.
		if ( empty( $video['attrs'] ) ) {
			return;
		}

		$parsed_attrs = $this->preProcessPresets( ( new Playlist() )->parsed_attributes( $video['blockName'], $video['attrs'] ) );

		return array(
			'id'       => $block['attrs']['id'] ?? 0,
			'config'   => $parsed_attrs,
			'title'    => html_entity_decode( strval( $block['attrs']['title'] ?? '' ) ),
			'duration' => $block['attrs']['duration'] ?? '',
		);
	}

	/**
	 * Dynamic block output
	 *
	 * @param array $attributes block attributes.
	 * @param string $content block content.
	 * @param object $block block object.
	 * @return string
	 */
	public function renderBlock( $attributes, $content, $block ) {
		// we need a block.
		if ( empty( $block ) ) {
			return '';
		}

		// we need inner blocks.
		$playlist_blocks = ! empty( $block->parsed_block['innerBlocks'][1]['innerBlocks'] ) ? $block->parsed_block['innerBlocks'][1]['innerBlocks'] : array();
		if ( empty( $playlist_blocks ) ) {
			return '';
		}

		return $this->html(
			wp_parse_args(
				$attributes,
				array(
					'styles' => $this->getComponentStyles( $attributes ),
					'items'  => array_map( array( $this, 'mapPlaylistConfig' ), $playlist_blocks ),
					'class'  => $this->getClasses( $attributes ),
				)
			)
		);
	}

	/**
	 * Render HTML for Playlist
	 * 
	 * @param $data Attributes for the Playlist
	 * @param $presto_playlist_instance Instance ID
	 * 
	 * @return HTML template
	 */
	public function html( $data ) {
		if ( empty( $data['items'] ) ) {
			return '';
		}

		$data['instance'] = self::$instance++;

		// Preload playlist block component resources.
		if ( class_exists('\PrestoPlayer\Services\PreloadService') ) {
			$preloadService = new PreloadService();
			$preloadService->add(
				[
					'presto-player',
					'presto-playlist',
				]
			);
			$preloadService->bootstrap();
		}

		// TODO: child template system
		ob_start(); ?>

		<figure class="wp-block-playlist presto-block-playlist <?php echo esc_attr( $data['class'] ?? '' ); ?>" style="<?php echo esc_attr( $data['styles'] ?? '' ); ?>">
			<presto-playlist heading='<?php echo esc_attr( $data['heading'] ?? '' ); ?>' id="presto-playlist-<?php echo (int) $data['instance']; ?>">
				<span slot="unauthorized">
					<?php
					if ( file_exists( PRESTO_PLAYER_PLUGIN_DIR . 'templates/unauthorized.php' ) ) {
						include PRESTO_PLAYER_PLUGIN_DIR . 'templates/unauthorized.php';
					}
					?>
				</span>
			</presto-playlist>
		</figure>

		<?php $this->initComponentScript( $data ); ?>

		<?php
		$template = ob_get_contents();
		ob_end_clean();

		return $template;
	}

	/**
	 * Dynamically initialize component via script tag
	 * We have to do this because we cannot send arrays or object in plain html
	 */
	public function initComponentScript( $data = array() ) {
		if ( empty( $data['items'] ) ) {
			return '';
		}
		?>
		<script>
			var playlist = document.querySelector('presto-playlist#presto-playlist-<?php echo (int) $data['instance']; ?>');
			playlist.listTextSingular = '<?php echo esc_attr( $data['listTextSingular'] ?? '' ); ?>';
			playlist.listTextPlural = '<?php echo esc_attr( $data['listTextPlural'] ?? '' ); ?>';
			playlist.transitionDuration = <?php echo esc_attr( $data['transitionDuration'] ?? 5 ); ?>;
			playlist.items = <?php echo wp_json_encode( $data['items'] ); ?>;
		</script>
		<?php
	}

	/**
	 * Get the color preset css variable.
	 *
	 * @param string $value The value.
	 *
	 * @return string|void
	 */
	public function getColorPresetCssVar( $value ) {
		if ( ! $value ) {
			return;
		}

		// kadence fix.
		if ( 'Kadence' === wp_get_theme()->name && strpos( $value, 'theme-' ) !== false ) {
			return 'var(' . str_replace( 'theme-', '--global-', $value ) . ')';
		}

		return "var(--wp--preset--color--$value)";
	}

	public function getComponentStyles( $attributes ) {
		$style = 'border: none;';

		if ( ! empty( $attributes['highlightColor'] ) ) {
			$style .= '--presto-playlist-highlight-color: ' . $attributes['highlightColor'] . '; ';
			if ( ! empty( $attributes['matchPlaylistToPlayerColor'] ) ) {
				$style .= '--presto-player-highlight-color: ' . $attributes['highlightColor'] . ';';
			}
		}

		if ( ! empty( $attributes['style']['border']['radius'] ) ) {
			$style .= '--presto-playlist-border-radius: ' . $attributes['style']['border']['radius'] . ';';
		}

		if ( ! empty( $attributes['style']['border']['width'] ) ) {
			$style .= '--presto-playlist-border-width: ' . $attributes['style']['border']['width'] . ';';
		}

		// border color.
		if ( ! empty( $attributes['borderColor'] ) || ! empty( $attributes['style']['border']['color'] ) ) {
			$border_color = ! empty( $attributes['borderColor'] ) ? $this->getColorPresetCssVar( $attributes['borderColor'] ) : $attributes['style']['border']['color'];
			$style       .= '--presto-playlist-border-color: ' . $border_color . ';';
		}


		// text color.
		if ( ! empty( $attributes['textColor'] ) || ! empty( $attributes['style']['color']['text'] ) ) {
			$text_color = ! empty( $attributes['textColor'] ) ? $this->getColorPresetCssVar( $attributes['textColor'] ) : $attributes['style']['color']['text'];
			$style     .= '--presto-playlist-text-color: ' . $text_color . ';';
		}

		// background color.
		if ( ! empty( $attributes['backgroundColor'] ) || ! empty( $attributes['style']['color']['background'] ) ) {
			$bg_color = ! empty( $attributes['backgroundColor'] ) ? $this->getColorPresetCssVar( $attributes['backgroundColor'] ) : $attributes['style']['color']['background'];
			$style   .= '--presto-playlist-background-color: ' . $bg_color . ';';
		}

		$transitionDuration = ( ! empty( $attributes['transitionDuration'] ) ) ? $attributes['transitionDuration'] . 's' : '5s';
		$style             .= '--presto-playlist-transition-duration: ' . $transitionDuration;

		return $style;
	}

	/**
	 * Preprocess the presets to make sure they are compatible with the playlist.
	 * 
	 * @param $attrs Attributes for the Playlist
	 * @return $attrs Parsed Attributes for the Playlist
	 */
	public function preProcessPresets( $attrs ) {
		// Setting the lazy load for YT videos to false in order to prevent the video from not playing. - #Issue - BPP-77
		if ( isset( $attrs['preset'] ) && isset( $attrs['preset']['lazy_load_youtube'] ) ) {
			$attrs['preset']['lazy_load_youtube'] = false;
		}

		// Disable Loop to avoid replaying of same item in the playlist.
		if ( isset( $attrs['preset'] ) && isset( $attrs['preset']['on_video_end'] ) ) {
			$attrs['preset']['on_video_end'] = 'select';
		}

		// Disable sticky scroll until we can get it working with playlist.
		if ( isset( $attrs['preset'] ) && isset( $attrs['preset']['sticky_scroll'] ) ) {
			$attrs['preset']['sticky_scroll'] = false;
		}

		return $attrs;
	}
}
