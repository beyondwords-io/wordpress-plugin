/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

export function PendingNoticeCheck( { hasPendingNoticeAction, children } ) {
	if ( ! hasPendingNoticeAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const beyondwordsProjectId =
			getEditedPostAttribute( 'meta' ).beyondwords_project_id;
		const speechkitProjectId =
			getEditedPostAttribute( 'meta' ).speechkit_project_id;

		const status = getEditedPostAttribute( 'status' );

		const hasProjectId = beyondwordsProjectId || speechkitProjectId;

		return {
			hasPendingNoticeAction: !! hasProjectId && status === 'pending',
		};
	} ),
] )( PendingNoticeCheck );
