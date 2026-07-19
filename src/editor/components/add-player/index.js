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
	edit: function Edit() {
		const blockProps = useBlockProps();

		// The live player is not embedded in the editor preview — it only
		// renders on the front end. Show a static Placeholder marking where
		// the player will appear in the published post.
		return (
			<div { ...blockProps }>
				<Placeholder
					icon={ <BeyondwordsIcon /> }
					label={ __( 'BeyondWords Player', 'speechkit' ) }
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
