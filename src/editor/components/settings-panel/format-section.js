/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import {
	getOutputOptions,
	outputIncludesVideo,
	OUTPUT_AUDIO,
	projectDefaultOption,
} from './helpers';
import Stack from '../stack';

export function FormatSection() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const projectId = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )
				?.beyondwords_project_id ||
			select( 'beyondwords/settings' ).getSettings()?.projectId,
		[]
	);

	const videoTemplates = useSelect(
		( select ) => select( 'beyondwords/settings' ).getVideoTemplates(),
		[]
	);

	const videoSizes = useSelect(
		( select ) =>
			select( 'beyondwords/settings' ).getVideoSizes( projectId ),
		[ projectId ]
	);

	// `useEntityProp` returns `{}` (so `meta` is undefined) until the post entity
	// record is hydrated; default to an empty object before reading meta values.
	const [ rawMeta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const meta = rawMeta ?? {};

	const output = meta.beyondwords_output || OUTPUT_AUDIO;
	const videoTemplateId = meta.beyondwords_video_template_id || '';
	const videoSize = meta.beyondwords_video_size || '';

	const setOutput = ( value ) => {
		setMeta( { ...meta, beyondwords_output: value } );
	};

	const setVideoTemplateId = ( value ) => {
		setMeta( { ...meta, beyondwords_video_template_id: value } );
	};

	const setVideoSize = ( value ) => {
		setMeta( { ...meta, beyondwords_video_size: value } );
	};

	const videoTemplateOptions = [
		projectDefaultOption(),
		...( videoTemplates ?? [] ).map( ( template ) => ( {
			label: decodeEntities( template.name ?? template.slug ?? '' ),
			value: String( template.id ),
		} ) ),
	];

	const videoSizeOptions = [
		projectDefaultOption(),
		...( videoSizes ?? [] )
			.filter( ( size ) => size.enabled !== false )
			.map( ( size ) => ( {
				label: decodeEntities(
					size.description
						? `${ size.name } (${ size.description })`
						: size.name
				),
				value: size.name,
			} ) ),
	];

	return (
		<PanelBody title={ __( 'Format', 'speechkit' ) } initialOpen={ true }>
			<Stack>
				<SelectControl
					className="beyondwords--output"
					label={ __( 'Output', 'speechkit' ) }
					options={ getOutputOptions() }
					value={ output }
					onChange={ setOutput }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				{ outputIncludesVideo( output ) && (
					<>
						<SelectControl
							className="beyondwords--video-template"
							label={ __( 'Video template', 'speechkit' ) }
							options={ videoTemplateOptions }
							value={ videoTemplateId }
							onChange={ setVideoTemplateId }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<SelectControl
							className="beyondwords--video-size"
							label={ __( 'Video size', 'speechkit' ) }
							options={ videoSizeOptions }
							value={ videoSize }
							onChange={ setVideoSize }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					</>
				) }
			</Stack>
		</PanelBody>
	);
}

export default FormatSection;
