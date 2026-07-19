/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Toggle from '../toggle';

const META_KEY = 'beyondwords_generate_audio';

/**
 * Parse a stored Generate audio flag.
 *
 * @param {*} value The meta value.
 *
 * @return {boolean|null} true/false when set, null when unset.
 */
const parseFlag = ( value ) => {
	if ( value === '1' ) {
		return true;
	}
	if ( value === '0' ) {
		return false;
	}
	return null;
};

/**
 * Resolve the preselect mode for a post type's config.
 *
 * Tolerant of the pre-7.0.0 shapes (`'1'` and a bare taxonomy array).
 *
 * @param {*} config The preselect entry for a post type.
 *
 * @return {string} One of 'off', 'all', 'terms'.
 */
const resolveMode = ( config ) => {
	if ( config === '1' || config === 1 || config === true ) {
		return 'all';
	}

	if ( config && typeof config === 'object' ) {
		if ( config.mode === 'all' || config.mode === 'terms' ) {
			return config.mode;
		}
		// Legacy { taxonomy: [ ids ] } shape, before migration.
		if ( ! ( 'mode' in config ) ) {
			return Object.keys( config ).length ? 'terms' : 'off';
		}
	}

	return 'off';
};

/**
 * Resolve the selected term map for a post type's config.
 *
 * @param {*} config The preselect entry for a post type.
 *
 * @return {Object} Map of taxonomy slug to term IDs.
 */
const resolveTerms = ( config ) => {
	if ( config && typeof config === 'object' ) {
		if (
			config.mode === 'terms' &&
			config.terms &&
			typeof config.terms === 'object'
		) {
			return config.terms;
		}
		if ( ! ( 'mode' in config ) ) {
			return config; // Legacy shape.
		}
	}
	return {};
};

export function GenerateAudio( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const { editPost } = useDispatch( 'core/editor' );

	const { checked } = useSelect( ( select ) => {
		const {
			getCurrentPostType,
			getCurrentPostAttribute,
			getEditedPostAttribute,
			getPostEdits,
		} = select( 'core/editor' );

		const { getTaxonomy } = select( 'core' );
		const { getSettings } = select( 'beyondwords/settings' );

		const postType = getCurrentPostType();
		const savedMeta = getCurrentPostAttribute( 'meta' ) || {};
		const editedMeta = getPostEdits()?.meta || {};

		// The user's explicit decision, if any: an in-session edit wins over
		// saved meta.
		let explicit;
		if ( META_KEY in editedMeta ) {
			explicit = parseFlag( editedMeta[ META_KEY ] );
		} else {
			explicit =
				parseFlag( savedMeta[ META_KEY ] ) ??
				parseFlag( savedMeta.speechkit_generate_audio );
		}

		// Reactive: for term-gated post types this recomputes as taxonomy terms
		// are edited, so the toggle follows the preselect rule in both directions.
		const getShouldPreselect = () => {
			const settings = getSettings();
			if ( ! settings ) {
				return false;
			}

			const preselect =
				settings.preselect && typeof settings.preselect === 'object'
					? settings.preselect
					: {};

			if ( ! ( postType in preselect ) ) {
				return false;
			}

			const config = preselect[ postType ];
			const mode = resolveMode( config );

			if ( mode === 'all' ) {
				return true;
			}
			if ( mode !== 'terms' ) {
				return false;
			}

			const terms = resolveTerms( config );

			// OR across every listed taxonomy/term (exact term-ID match).
			return Object.keys( terms ).some( ( slug ) => {
				const ids = terms[ slug ];
				if ( ! Array.isArray( ids ) || ! ids.length ) {
					return false;
				}

				// Tolerant: skip taxonomies that aren't registered/loaded.
				const taxonomy = getTaxonomy( slug );
				if ( ! taxonomy ) {
					return false;
				}

				const assigned = getEditedPostAttribute(
					taxonomy.rest_base || slug
				);
				if ( ! Array.isArray( assigned ) ) {
					return false;
				}

				const wanted = ids.map( String );
				return assigned.some( ( id ) =>
					wanted.includes( String( id ) )
				);
			} );
		};

		return {
			// Derived only — no meta write for the preselect case, so the post isn't
			// dirtied; the server persists it via Preselect::should_preselect_for_post().
			checked:
				explicit !== undefined && explicit !== null
					? explicit
					: getShouldPreselect(),
		};
	}, [] );

	const handleChange = () => {
		// An explicit choice is a real edit (dirties the post) and overrides the
		// preselect rule from then on.
		editPost( { meta: { [ META_KEY ]: ! checked ? '1' : '0' } } );
	};

	// State-reflecting caption: the label reads the current state, not the action.
	const label = checked
		? __( 'Generation enabled', 'speechkit' )
		: __( 'Generation disabled', 'speechkit' );

	return (
		<Wrapper>
			<Toggle
				className="beyondwords--generate-audio"
				label={ label }
				checked={ Boolean( checked ) }
				onChange={ handleChange }
			/>
		</Wrapper>
	);
}

export default GenerateAudio;
