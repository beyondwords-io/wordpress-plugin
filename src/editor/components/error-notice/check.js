/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

export function ErrorNoticeCheck( { hasErrorNoticeAction, children } ) {
	if ( ! hasErrorNoticeAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const beyondwordsErrorMessage =
			getEditedPostAttribute( 'meta' ).beyondwords_error_message;
		const speechkitErrorMessage =
			getEditedPostAttribute( 'meta' ).speechkit_error_message;

		return {
			hasErrorNoticeAction:
				!! beyondwordsErrorMessage || !! speechkitErrorMessage,
		};
	} ),
] )( ErrorNoticeCheck );
