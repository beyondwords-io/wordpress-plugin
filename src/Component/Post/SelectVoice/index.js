/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Flex, FlexBlock, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { Fragment, useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

export function SelectVoice( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const { settings } = useSelect( ( select ) => {
		return {
			settings: select( 'beyondwords/settings' ).getSettings(),
		};
	}, [] );

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const languageCode =
		meta.beyondwords_language_code || settings.projectLanguageCode;

	const { languages } = useSelect( ( select ) => {
		return {
			languages: select( 'beyondwords/settings' ).getLanguages(),
		};
	}, [] );

	const defaultLanguage = languages?.find(
		( item ) => item.code === languageCode
	);

	const setLanguageCode = ( newLanguageCode ) => {
		setMeta( {
			...meta,
			beyondwords_language_code: newLanguageCode,
		} );
	};

	const setAllVoiceIds = ( newVoiceId ) => {
		setMeta( {
			...meta,
			beyondwords_body_voice_id: newVoiceId,
			beyondwords_title_voice_id: newVoiceId,
			beyondwords_summary_voice_id: newVoiceId,
		} );
	};

	const { voices } = useSelect(
		( select ) => {
			return {
				voices: languageCode
					? select( 'beyondwords/settings' ).getVoices( languageCode )
					: [],
			};
		},
		[ languageCode ]
	);

	const defaultVoice = useMemo( () => {
		const postVoiceId = meta.beyondwords_body_voice_id;
		const projectVoiceId = settings.projectBodyVoiceId;
		const defaultVoiceId = `${ defaultLanguage?.default_voices?.body?.id }`;

		for ( const item of voices ?? [] ) {
			if ( postVoiceId === `${ item.id }` ) {
				return `${ item.id }`;
			}
			if ( projectVoiceId === `${ item.id }` ) {
				return `${ item.id }`;
			}
			if ( defaultVoiceId === `${ item.id }` ) {
				return `${ item.id }`;
			}
		}

		return '';
	}, [ defaultLanguage, meta, settings, voices ] );

	const languageOptions = useMemo( () => {
		return ( languages ?? [] ).map( ( language ) => {
			const { accent, code, name } = language;
			return {
				// eslint-disable-next-line prettier/prettier
				label: `${ decodeEntities( name ) } (${ decodeEntities( accent ) })`,
				value: decodeEntities( code ),
			};
		} );
	}, [ languages ] );

	const voiceOptions = useMemo( () => {
		return ( voices ?? [] ).map( ( voice ) => {
			return {
				label: decodeEntities( voice.name ),
				value: decodeEntities( `${ voice.id }` ),
			};
		} );
	}, [ voices ] );

	if ( ! languageOptions.length ) {
		return false;
	}

	return (
		<>
			<Wrapper>
				<Flex>
					<FlexBlock>
						<SelectControl
							className="beyondwords--select-language"
							label={ __( 'Language', 'speechkit' ) }
							options={ languageOptions }
							onChange={ ( val ) => setLanguageCode( val ) }
							value={ languageCode }
							__nextHasNoMarginBottom
						/>
					</FlexBlock>
				</Flex>
			</Wrapper>
			<Wrapper>
				<Flex>
					<FlexBlock>
						{ ! voiceOptions?.length && (
							<Spinner
								className="beyondwords--spinner-voices"
								style={ { marginTop: '1rem' } }
							/>
						) }
						{ !! voiceOptions?.length && (
							<SelectControl
								className="beyondwords--select-voice"
								label={ __( 'Voice', 'speechkit' ) }
								options={ voiceOptions }
								onChange={ ( val ) => setAllVoiceIds( val ) }
								disabled={ ! voiceOptions?.length }
								value={ defaultVoice }
								__nextHasNoMarginBottom
							/>
						) }
					</FlexBlock>
				</Flex>
			</Wrapper>
		</>
	);
}

export default SelectVoice;
