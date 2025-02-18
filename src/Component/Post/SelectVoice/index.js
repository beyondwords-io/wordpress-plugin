/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Flex, FlexBlock } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { Fragment, useMemo } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import SelectVoiceCheck from './check';

export function SelectVoice( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const languageId = meta.beyondwords_language_id;
	const bodyVoiceId = meta.beyondwords_body_voice_id;

	const setLanguageId = ( newLanguageId ) => {
		setMeta( {
			...meta,
			beyondwords_language_id: newLanguageId,
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

	const { languages } = useSelect( ( select ) => {
		return {
			languages: select( 'beyondwords/settings' ).getLanguages(),
		}
	}, [] );

	const { voices } = useSelect( ( select ) => {
		return {
			voices: languageId ? select( 'beyondwords/settings' ).getVoices( languageId ) : [],
		}
	}, [ languageId ] );

	const languageOptions = useMemo( () => {
		return ( languages ?? [] ).map( ( language ) => {
			return {
				label: decodeEntities( language.name ),
				value: decodeEntities( language.id ),
			};
		} );
	}, [ languages ] );

	const voiceOptions = useMemo( () => {
		return ( voices ?? [] ).map( ( voice ) => {
			return {
				label: decodeEntities( voice.name ),
				value: decodeEntities( voice.id ),
			};
		} );
	}, [ voices ] );

	if (! languageOptions.length) {
		return false;
	}

	return (
		<SelectVoiceCheck>
			<Wrapper>
				<Flex>
					<FlexBlock>
						<SelectControl
							className="beyondwords--select-language"
							label={ __( 'Language', 'speechkit' ) }
							options={ [
								{
									label: __( 'Project default', 'speechkit' ),
									value: '',
								},
								...languageOptions,
							] }
							onChange={ ( val ) => setLanguageId( val ) }
							value={ languageId }
							__nextHasNoMarginBottom
						/>
					</FlexBlock>
				</Flex>
			</Wrapper>
			<Wrapper>
				<Flex>
					<FlexBlock>
						<SelectControl
							className="beyondwords--select-voice"
							label={ __( 'Voice', 'speechkit' ) }
							options={ [
								{
									label: '',
									value: '',
								},
								...voiceOptions,
							] }
							onChange={ ( val ) => setAllVoiceIds( val ) }
							disabled={ ! voiceOptions?.length }
							value={ bodyVoiceId }
							__nextHasNoMarginBottom
						/>
					</FlexBlock>
				</Flex>
			</Wrapper>
		</SelectVoiceCheck>
	);
}

export default SelectVoice;
