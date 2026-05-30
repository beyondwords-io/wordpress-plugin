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
	getSourceOptions,
	sourceIncludesScript,
	SOURCE_POST,
	projectDefaultOption,
} from './helpers';

export function ContentSection() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const scriptTemplates = useSelect(
		( select ) => select( 'beyondwords/settings' ).getScriptTemplates(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const source = meta.beyondwords_source || SOURCE_POST;
	const scriptTemplateId = meta.beyondwords_script_template_id || '';

	const setSource = ( value ) => {
		setMeta( { ...meta, beyondwords_source: value } );
	};

	const setScriptTemplateId = ( value ) => {
		setMeta( { ...meta, beyondwords_script_template_id: value } );
	};

	const hasScriptTemplates = ( scriptTemplates ?? [] ).length > 0;

	const scriptTemplateOptions = [
		projectDefaultOption(),
		...( scriptTemplates ?? [] ).map( ( template ) => ( {
			label: decodeEntities( template.name ?? template.slug ?? '' ),
			value: String( template.id ),
		} ) ),
	];

	return (
		<PanelBody title={ __( 'Content', 'speechkit' ) } initialOpen={ true }>
			<SelectControl
				className="beyondwords--source"
				label={ __( 'Source', 'speechkit' ) }
				options={ getSourceOptions() }
				value={ source }
				onChange={ setSource }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			{ sourceIncludesScript( source ) && hasScriptTemplates && (
				<SelectControl
					className="beyondwords--script-template"
					label={ __( 'Script template', 'speechkit' ) }
					options={ scriptTemplateOptions }
					value={ scriptTemplateId }
					onChange={ setScriptTemplateId }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
		</PanelBody>
	);
}

export default ContentSection;
