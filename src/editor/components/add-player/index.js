/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BeyondwordsIcon } from '../icon';
import PlayAudio from '../play-audio';
import PlayerPlaceholder from '../play-audio/placeholder';
import { useHasPlayAudioAction } from '../play-audio/hooks';

// Register the block
registerBlockType( 'beyondwords/player', {
	icon: <BeyondwordsIcon />,
	edit: function Edit() {
		const blockProps = useBlockProps();

		// Mirror the sidebar Preview panel: render a live (but non-interactive)
		// player once the post has everything the player needs, otherwise a
		// placeholder describing where the player will appear.
		const canPreview = useHasPlayAudioAction();

		return (
			<div { ...blockProps }>
				{ canPreview ? (
					// <Disabled> keeps the player visible and rendered but
					// non-interactive, so the block stays selectable/movable.
					<Disabled>
						<PlayAudio />
					</Disabled>
				) : (
					<PlayerPlaceholder />
				) }
			</div>
		);
	},
	save: function Save() {
		const blockProps = useBlockProps.save( { contentEditable: false } );

		return (
			<div { ...blockProps }>
				<div
					data-beyondwords-player="true"
					contentEditable="false"
				></div>
			</div>
		);
	},
} );
