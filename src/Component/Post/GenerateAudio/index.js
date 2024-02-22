/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { Fragment, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import GenerateAudioCheck from './check';

export function GenerateAudio( {
	generateAudio,
	generateAudioEdited,
	setGenerateAudio,
	wrapper,
} ) {
	const Wrapper = wrapper || Fragment;

	// Set "Generate audio" to "1" in the store when it has been preselected
	useEffect( () => {
		if ( ! generateAudioEdited && generateAudio ) {
			setGenerateAudio( generateAudio );
		}
	}, [ generateAudioEdited, generateAudio ] );

	return (
		<GenerateAudioCheck>
			<Wrapper>
				<CheckboxControl
					className="beyondwords--generate-audio"
					label={ __( 'Generate audio', 'speechkit' ) }
					checked={ generateAudio }
					onChange={ () => {
						setGenerateAudio( ! generateAudio );
					} }
				/>
			</Wrapper>
		</GenerateAudioCheck>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const {
			getCurrentPostAttribute,
			getCurrentPostType,
			getEditedPostAttribute,
			getPostEdits,
		} = select( 'core/editor' );

		const { getGenerateAudioEdited } = select( 'beyondwords/interactions' );
		const { getSettings } = select( 'beyondwords/settings' );

		/**
		 * Get the Generate audio value.
		 *
		 * This is a little complex because it is also controlled (auto-checked/unchecked)
		 * based on the assigned Categories, and we need to be able to override it.
		 */
		const getGenerateAudio = () => {
			const { meta } = getPostEdits();

			// Has "Generate audio" been edited in this session (manually checked or unchecked)?
			if (
				getGenerateAudioEdited() &&
				meta &&
				'beyondwords_generate_audio' in meta
			) {
				return meta.beyondwords_generate_audio === '1';
			}

			// Check various custom fields in the saved post
			const {
				/* eslint-disable-next-line camelcase */
				beyondwords_generate_audio,
				/* eslint-disable-next-line camelcase */
				speechkit_generate_audio,
				/* eslint-disable-next-line camelcase */
				publish_post_to_speechkit,
			} = getCurrentPostAttribute( 'meta' );

			if (
				/* eslint-disable-next-line camelcase */
				beyondwords_generate_audio === '1' ||
				/* eslint-disable-next-line camelcase */
				speechkit_generate_audio === '1' ||
				/* eslint-disable-next-line camelcase */
				publish_post_to_speechkit === '1'
			) {
				return true;
			}

			if (
				/* eslint-disable-next-line camelcase */
				beyondwords_generate_audio === '0' ||
				/* eslint-disable-next-line camelcase */
				speechkit_generate_audio === '0' ||
				/* eslint-disable-next-line camelcase */
				publish_post_to_speechkit === '0'
			) {
				return false;
			}

			return null;
		};

		/**
		 * Should we preselect "Generate audio", based on the plugin setting?
		 */
		const getShouldPreselect = () => {
			const settings = getSettings();

			// Do we have settings?
			if ( ! settings ) {
				return false;
			}

			const preselect =
				typeof settings.preselect === 'object' &&
				settings.preselect !== null
					? settings.preselect
					: {};

			const postType = getCurrentPostType();

			// Exit if the current post type does not exist in the plugin settings
			if ( false === postType in preselect ) {
				return false;
			}

			// Is the current post type checked in the plugin settings
			// If it is checked at post-level then we preselect Generate audio regardless
			// of the applied taxonomies
			if ( preselect[ postType ] === '1' ) {
				return true;
			}

			// Get the Post edits
			const postEdits = getPostEdits();

			// Check that categories have been edited?
			// todo support multiple taxonomies
			if ( ! Array.isArray( postEdits.categories ) ) {
				return false;
			}

			// Handle cases where preselect[ postType ] is not an object
			// This can happen when the plugin setting is empty or corrupt
			if (
				typeof preselect[ postType ] !== 'object' ||
				preselect[ postType ] === null
			) {
				return false;
			}

			// Get all Post categories
			const categories = getEditedPostAttribute( 'categories' );

			// Do any Post categories match the plugin settings?
			const hasMatchingCategories = categories.some( ( x ) => {
				// todo support multiple taxonomies
				if ( false === 'category' in preselect[ postType ] ) {
					return false;
				}
				// todo support multiple taxonomies
				return preselect[ postType ].category.includes( String( x ) );
			} );

			if ( hasMatchingCategories ) {
				return true;
			}

			// todo Do any Post OTHER TAXONOMIES match the plugin settings?

			return false;
		};

		const generateAudio = getGenerateAudio();

		return {
			generateAudio:
				generateAudio === null ? getShouldPreselect() : generateAudio,
			generateAudioEdited: getGenerateAudioEdited(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { editPost } = dispatch( 'core/editor' );
		const { setGenerateAudioEdited } = dispatch(
			'beyondwords/interactions'
		);

		return {
			setGenerateAudio: ( generateAudio ) => {
				// Update the Custom Field
				editPost( {
					meta: {
						/* eslint-disable-next-line camelcase */
						beyondwords_generate_audio: generateAudio ? '1' : '0',
					},
				} );
				// Mark "Generate audio" as being (manually) edited, so other components
				// know the checkbox has been changed from it's default value.
				setGenerateAudioEdited( true );
			},
		};
	} ),
] )( GenerateAudio );
