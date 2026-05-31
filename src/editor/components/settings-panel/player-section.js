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

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const source = meta.beyondwords_source || SOURCE_POST;
	const output = meta.beyondwords_output || OUTPUT_AUDIO;

	const stored = meta.beyondwords_embed;
	// No explicit choice yet → default to the first asset so the player shows.
	// ("Embed: None" is the deliberate opt-out.) Legacy `beyondwords_disabled`
	// posts are converted to "None" by the v7.0.0 migration, so an unset value
	// here always means "show".
	const embed = stored || getDefaultEmbed( source, output );

	const embedOptions = getEmbedOptions( source, output );

	const setEmbed = ( value ) => {
		setMeta( { ...meta, beyondwords_embed: value } );
	};

	// When Source × Output narrows the option list and the *stored* embed is no
	// longer offered, fall back to None.
	//
	// We never write meta on mount: an unset value already means "show the first
	// asset" (Player::is_enabled() treats it that way), and a mount-time write
	// races the other panels' preselect writes — e.g. it would clobber the
	// Generate audio preselect back to off.
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

	// In the document/pre-publish panels we render the field directly inside the
	// existing "BeyondWords" panel rather than nesting another panel.
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
