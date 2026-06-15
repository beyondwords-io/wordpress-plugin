/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BeyondwordsIcon } from '../icon';

// Register the block
registerBlockType( 'beyondwords/player', {
	icon: <BeyondwordsIcon />,
	// The live player can't render in the editor, so show a standard Placeholder
	// describing where it will appear (the saved markup still embeds the player).
	edit: function Edit() {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<Placeholder
					icon={ <BeyondwordsIcon /> }
					label={ __( 'BeyondWords', 'speechkit' ) }
					instructions={ __(
						'The BeyondWords audio player will appear here.',
						'speechkit'
					) }
				/>
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
