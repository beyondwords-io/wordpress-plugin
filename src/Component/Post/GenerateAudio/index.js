/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';

export function GenerateAudio( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	// Track whether the user has manually interacted with the checkbox.
	// This prevents taxonomy-based auto-selection from overriding user choice.
	const [ isManuallySet, setIsManuallySet ] = useState( false );

	const { editPost } = useDispatch( 'core/editor' );

	const { generateAudio, shouldPreselect } = useSelect( ( select ) => {
		const {
			getCurrentPostAttribute,
			getCurrentPostType,
			getEditedPostAttribute,
			getPostEdits,
		} = select( 'core/editor' );

		const { getSettings } = select( 'beyondwords/settings' );

		/**
		 * Get the Generate audio value from post meta.
		 *
		 * Returns:
		 * - true/false if explicitly set in meta
		 * - null if not set (should use preselect logic)
		 */
		const getGenerateAudio = () => {
			const { meta } = getPostEdits();

			// Check if edited in this session
			if ( meta && 'beyondwords_generate_audio' in meta ) {
				return meta.beyondwords_generate_audio === '1';
			}

			// Check saved post meta
			const savedMeta = getCurrentPostAttribute( 'meta' ) || {};
			const {
				beyondwords_generate_audio: beyondwordsValue,
				speechkit_generate_audio: speechkitValue,
			} = savedMeta;

			if ( beyondwordsValue === '1' || speechkitValue === '1' ) {
				return true;
			}

			if ( beyondwordsValue === '0' || speechkitValue === '0' ) {
				return false;
			}

			return null;
		};

		/**
		 * Should we preselect "Generate audio", based on the plugin setting?
		 */
		const getShouldPreselect = () => {
			const settings = getSettings();

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
			if ( ! ( postType in preselect ) ) {
				return false;
			}

			// Is the current post type checked at post-level in the plugin settings?
			// If so, preselect Generate audio regardless of taxonomies
			if ( preselect[ postType ] === '1' ) {
				return true;
			}

			// Check if categories have been edited
			// todo: support multiple taxonomies
			const postEdits = getPostEdits();
			if ( ! Array.isArray( postEdits.categories ) ) {
				return false;
			}

			// Handle cases where preselect[ postType ] is not an object
			if (
				typeof preselect[ postType ] !== 'object' ||
				preselect[ postType ] === null
			) {
				return false;
			}

			// Do any post categories match the plugin settings?
			// todo: support multiple taxonomies
			if ( ! ( 'category' in preselect[ postType ] ) ) {
				return false;
			}

			// Get all post categories
			const categories = getEditedPostAttribute( 'categories' );

			return categories.some( ( x ) =>
				preselect[ postType ].category.includes( String( x ) )
			);
		};

		const currentValue = getGenerateAudio();

		return {
			generateAudio:
				currentValue === null ? getShouldPreselect() : currentValue,
			shouldPreselect: getShouldPreselect(),
		};
	}, [] );

	// Set "Generate audio" meta when preselected (on initial load only)
	useEffect( () => {
		if ( ! isManuallySet && shouldPreselect ) {
			editPost( {
				meta: {
					beyondwords_generate_audio: '1',
				},
			} );
		}
	}, [ isManuallySet, shouldPreselect, editPost ] );

	const handleChange = () => {
		setIsManuallySet( true );
		editPost( {
			meta: {
				beyondwords_generate_audio: ! generateAudio ? '1' : '0',
			},
		} );
	};

	return (
		<Wrapper>
			<CheckboxControl
				className="beyondwords--generate-audio"
				label={ __( 'Generate audio', 'speechkit' ) }
				checked={ generateAudio }
				onChange={ handleChange }
				__nextHasNoMarginBottom
			/>
		</Wrapper>
	);
}

export default GenerateAudio;
