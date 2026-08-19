/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { select, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Stack from '../stack';
import Toggle from '../toggle';
import VoicePicker from '../voice-picker';

export function VoiceSection( { withPanel = true } ) {
	const postType = useSelect(
		( s ) => s( 'core/editor' ).getCurrentPostType(),
		[]
	);

	// `useEntityProp` yields undefined meta until the post entity record is hydrated.
	const [ rawMeta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const meta = rawMeta ?? {};

	const languageCode = meta.beyondwords_language_code || '';
	const voiceId = meta.beyondwords_body_voice_id || '';

	// "Customize" is opt-in: a post counts as customised once it carries an explicit
	// language or voice. Local state so toggling on reveals the pickers pre-choice.
	const [ customize, setCustomize ] = useState(
		() => !! ( languageCode || voiceId )
	);

	const projectId = useSelect(
		( s ) =>
			s( 'core/editor' ).getEditedPostAttribute( 'meta' )
				?.beyondwords_project_id ||
			s( 'beyondwords/settings' ).getSettings()?.projectId,
		[]
	);

	// Customize-on with no choice yet: fetch the project default language and seed
	// only the Language (a project default voice can belong to another language).
	const needsDefault = customize && ! languageCode && ! voiceId;

	const project = useSelect(
		( s ) =>
			needsDefault
				? s( 'beyondwords/settings' ).getProject( projectId )
				: null,
		[ needsDefault, projectId ]
	);

	// True once the project fetch has settled; keeps the spinner up until then to
	// avoid a one-frame "empty dropdown then spinner" flicker.
	const projectResolved = useSelect(
		( s ) =>
			! needsDefault ||
			s( 'beyondwords/settings' ).hasFinishedResolution( 'getProject', [
				projectId,
			] ),
		[ needsDefault, projectId ]
	);

	// Apply the resolved default language. Reads the freshest meta — not the
	// closure's — so a concurrent edit during the async fetch isn't clobbered.
	useEffect( () => {
		if ( needsDefault && project?.language ) {
			const current =
				select( 'core/editor' ).getEditedPostAttribute( 'meta' );
			setMeta( {
				...current,
				beyondwords_language_code: project.language,
			} );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ needsDefault, project ] );

	const loadingProject = customize && needsDefault && ! projectResolved;

	// Storing the pair together keeps the language and its voice consistent.
	const setVoice = ( { languageCode: nextCode, voiceId: nextVoiceId } ) => {
		setMeta( {
			...meta,
			beyondwords_language_code: nextCode,
			beyondwords_body_voice_id: nextVoiceId,
		} );
	};

	// Customize off reverts to the project defaults by clearing both choices.
	const toggleCustomize = () => {
		const next = ! customize;
		setCustomize( next );

		if ( ! next ) {
			setVoice( { languageCode: '', voiceId: '' } );
		}
	};

	const fields = (
		<Stack>
			<Toggle
				className="beyondwords--customize"
				label={ __( 'Customize', 'speechkit' ) }
				checked={ customize }
				onChange={ toggleCustomize }
			/>
			{ loadingProject && (
				<div className="beyondwords--languages-spinner">
					<Spinner />
				</div>
			) }
			<VoicePicker
				enabled={ customize && ! loadingProject }
				languageCode={ languageCode }
				voiceId={ voiceId }
				onChange={ setVoice }
			/>
		</Stack>
	);

	// Document/pre-publish panels render the fields without nesting another panel.
	if ( ! withPanel ) {
		return fields;
	}

	return (
		<PanelBody title={ __( 'Voice', 'speechkit' ) } initialOpen={ true }>
			{ fields }
		</PanelBody>
	);
}

export default VoiceSection;
