/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { BlockControls, useBlockProps } from '@wordpress/block-editor';

// Register the block
registerBlockType( 'beyondwords/player', {
	edit() {
		const blockProps = useBlockProps( { contentEditable: false } );

		return (
			<div { ...blockProps }>
				<BlockControls />
				<div
					data-beyondwords-player="true"
					contentEditable="false"
				></div>
			</div>
		);
	},
	save() {
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
