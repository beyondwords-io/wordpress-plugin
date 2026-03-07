/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { Fragment, useEffect } from '@wordpress/element';

export function GenerateAudio( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const { editPost } = useDispatch( 'core/editor' );

	const { generateAudio, shouldPreselect, hasExplicitValue } = useSelect(
		( select ) => {
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

				// Give precedence to beyondwords_generate_audio when explicitly set
				if ( beyondwordsValue === '1' ) {
					return true;
				}

				if ( beyondwordsValue === '0' ) {
					return false;
				}

				// Fall back to deprecated speechkit_generate_audio only if
				// beyondwords_generate_audio is not explicitly set
				if ( speechkitValue === '1' ) {
					return true;
				}

				if ( speechkitValue === '0' ) {
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
			const shouldPreselectValue = getShouldPreselect();

			return {
				generateAudio:
					currentValue === null
						? shouldPreselectValue
						: currentValue,
				shouldPreselect: shouldPreselectValue,
				hasExplicitValue: currentValue !== null,
			};
		},
		[]
	);

	// Set "Generate audio" meta when preselected, but only if the value
	// is truly unset (null) — neither in post edits nor saved meta.
	// This prevents overriding an explicit '0' on existing posts, and
	// also prevents the pre-publish panel instance from overriding a
	// user's earlier manual uncheck (since GenerateAudio renders in
	// both the document settings panel and the pre-publish panel).
	useEffect( () => {
		if ( shouldPreselect && ! hasExplicitValue ) {
			editPost( {
				meta: {
					beyondwords_generate_audio: '1',
				},
			} );
		}
	}, [ shouldPreselect, hasExplicitValue, editPost ] );

	const handleChange = () => {
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
