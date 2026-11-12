/**
 * WordPress dependencies
 */
import { addAction, addFilter } from '@wordpress/hooks';
import { dispatch, select } from '@wordpress/data';

// Written by the server after the editor loads; see doc/async-rest-migration.md.
const SERVER_WRITTEN_META_KEYS = [
	'beyondwords_content_id',
	'beyondwords_project_id',
	'beyondwords_preview_token',
	'beyondwords_integration_method',
	'beyondwords_error_message',
	'beyondwords_generate_audio',
];

/**
 * Server-written meta keys the author hasn't changed since the last save.
 *
 * @param {Object} meta          Meta about to be saved.
 * @param {Object} persistedMeta Meta as last received from the server.
 * @return {string[]} Meta keys.
 */
export function uneditedServerMetaKeys( meta, persistedMeta ) {
	if ( ! meta || ! persistedMeta ) {
		return [];
	}

	return SERVER_WRITTEN_META_KEYS.filter(
		( key ) => key in meta && meta[ key ] === persistedMeta[ key ]
	);
}

let omittedKeys = [];

addFilter(
	'editor.preSavePost',
	'beyondwords/omit-unedited-meta',
	( edits ) => {
		omittedKeys = uneditedServerMetaKeys(
			edits?.meta,
			select( 'core/editor' ).getCurrentPost()?.meta
		);

		if ( ! omittedKeys.length ) {
			return edits;
		}

		const meta = { ...edits.meta };
		omittedKeys.forEach( ( key ) => delete meta[ key ] );

		return { ...edits, meta };
	}
);

// The store still holds the omitted values as edits, which would be sent next time.
addAction(
	'editor.savePost',
	'beyondwords/omit-unedited-meta',
	( post, options ) => {
		const persistedMeta = select( 'core/editor' ).getCurrentPost()?.meta;

		// An autosave leaves the current post untouched, so there is nothing to reset to.
		if ( omittedKeys.length && persistedMeta && ! options?.isAutosave ) {
			dispatch( 'core/editor' ).editPost(
				{
					meta: Object.fromEntries(
						omittedKeys.map( ( key ) => [
							key,
							persistedMeta[ key ],
						] )
					),
				},
				{ undoIgnore: true }
			);
		}

		omittedKeys = [];
	}
);
