/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

export function BlockAttributesCheck( { supportsCustomFieldsAction, children } ) {
	if ( ! supportsCustomFieldsAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => {
		const { getCurrentPostType } = select( 'core/editor' );
		const postType = getCurrentPostType();

		return {
			supportsCustomFieldsAction:
				!! select( coreStore ).getPostType( postType )?.supports?.['custom-fields'],
		};
	} ),
] )( BlockAttributesCheck );
