/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PlayAudio from '../play-audio';
import { useHasPlayAudioAction } from '../play-audio/hooks';

// Register the block
registerBlockType( 'beyondwords/player', {
	edit: function Edit() {
		const blockProps = useBlockProps();

		// Mirror the sidebar Preview panel: render a live (but non-interactive)
		// player once the post has everything the player needs, otherwise a
		// placeholder prompting the user to generate audio.
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
					<Placeholder
						icon="controls-volumeon"
						label={ __( 'BeyondWords Player', 'speechkit' ) }
						instructions={ __(
							'The BeyondWords player will be displayed here once audio has been generated for this content.',
							'speechkit'
						) }
					/>
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
