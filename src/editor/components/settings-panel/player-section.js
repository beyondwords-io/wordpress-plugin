/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	EMBED_NONE,
	getEmbedOptions,
	isEmbedValid,
	OUTPUT_AUDIO,
	SOURCE_POST,
} from './helpers';

export function PlayerSection() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const source = meta.beyondwords_source || SOURCE_POST;
	const output = meta.beyondwords_output || OUTPUT_AUDIO;
	const embed = meta.beyondwords_embed || EMBED_NONE;

	const embedOptions = getEmbedOptions( source, output );

	const setEmbed = ( value ) => {
		setMeta( { ...meta, beyondwords_embed: value } );
	};

	// When Source × Output narrows the option list and the current embed is no
	// longer offered, fall back to None (gap-#4 option a). The post then either
	// has no embedded asset or the user picks a valid one.
	useEffect( () => {
		if ( ! isEmbedValid( embed, source, output ) ) {
			setEmbed( EMBED_NONE );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ source, output ] );

	return (
		<PanelBody title={ __( 'Player', 'speechkit' ) } initialOpen={ true }>
			<SelectControl
				className="beyondwords--embed"
				label={ __( 'Embed', 'speechkit' ) }
				help={ __(
					'Pick which generated asset is shown on this post. All other generated assets stay available in BeyondWords.',
					'speechkit'
				) }
				options={ embedOptions }
				value={ embed }
				onChange={ setEmbed }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</PanelBody>
	);
}

export default PlayerSection;
