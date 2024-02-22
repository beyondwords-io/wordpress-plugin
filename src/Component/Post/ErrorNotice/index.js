/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ErrorNoticeCheck from './check';

export function ErrorNotice( { errorMessage, wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const classNames = [
		'beyondwords-sidebar__post-status-description',
		'beyondwords-sidebar__post-status-description--error',
	];

	return (
		<ErrorNoticeCheck>
			<Wrapper>
				<div>
					<span className={ classNames.join( ' ' ) }>
						{ errorMessage }
					</span>
				</div>
			</Wrapper>
		</ErrorNoticeCheck>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const beyondwordsErrorMessage =
			getEditedPostAttribute( 'meta' ).beyondwords_error_message;
		const speechkitErrorMessage =
			getEditedPostAttribute( 'meta' ).speechkit_error_message;

		return {
			errorMessage: beyondwordsErrorMessage
				? beyondwordsErrorMessage
				: speechkitErrorMessage,
		};
	} ),
] )( ErrorNotice );
