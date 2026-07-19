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
	getDefaultEmbed,
	getEmbedOptions,
	isEmbedValid,
	OUTPUT_AUDIO,
	SOURCE_POST,
} from './helpers';
import Stack from '../stack';

export function PlayerSection( { withPanel = true } ) {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	// `useEntityProp` yields undefined meta until the post entity record is hydrated.
	const [ rawMeta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const meta = rawMeta ?? {};

	const source = meta.beyondwords_source || SOURCE_POST;
	const output = meta.beyondwords_output || OUTPUT_AUDIO;

	const stored = meta.beyondwords_embed;
	// Unset → default to the first asset so the player shows ("None" is the
	// opt-out; the v7.0.0 migration converts legacy `beyondwords_disabled` posts).
	const embed = stored || getDefaultEmbed( source, output );

	const embedOptions = getEmbedOptions( source, output );

	const setEmbed = ( value ) => {
		setMeta( { ...meta, beyondwords_embed: value } );
	};

	// Never write meta on mount: an unset value already means "show", and a
	// mount-time write races the other panels' preselect writes (e.g. Generate audio).
	useEffect( () => {
		if ( stored && ! isEmbedValid( stored, source, output ) ) {
			setEmbed( EMBED_NONE );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ source, output ] );

	const field = (
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
	);

	// Document/pre-publish panels render the field without nesting another panel.
	if ( ! withPanel ) {
		return <Stack>{ field }</Stack>;
	}

	return (
		<PanelBody title={ __( 'Player', 'speechkit' ) } initialOpen={ true }>
			<Stack>{ field }</Stack>
		</PanelBody>
	);
}

export default PlayerSection;
