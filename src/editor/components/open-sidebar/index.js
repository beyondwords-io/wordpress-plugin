/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

export function OpenSidebar( { openSidebar, wrapper = Fragment } ) {
	const Wrapper = wrapper;

	return (
		<Wrapper>
			<p style={ { marginBottom: 0, paddingBottom: 0 } }>
				{ __( 'Open the', 'speechkit' ) }{ ' ' }
				<a
					href="#beyondwords-plugin-sidebar"
					onClick={ () => {
						openSidebar();
					} }
				>
					{ __( 'BeyondWords sidebar', 'speechkit' ) }
				</a>{ ' ' }
				{ __( 'for additional options and features.', 'speechkit' ) }
			</p>
		</Wrapper>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		return {
			src: getEditedPostAttribute( 'meta' )._speechkit_link,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { openGeneralSidebar } = dispatch( 'core/edit-post' );
		return {
			openSidebar: () => {
				openGeneralSidebar(
					'beyondwords-plugin-sidebar/plugin-sidebar'
				);
			},
		};
	} ),
] )( OpenSidebar );
