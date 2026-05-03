/**
 * WordPress dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PendingNoticeCheck from './check';

export function PendingNotice( { projectUrl, wrapper } ) {
	const Wrapper = wrapper || Fragment;

	return (
		<PendingNoticeCheck>
			<Wrapper>
				<div>
					<p>
						{ __(
							'Listen to content saved as “Pending” in the BeyondWords dashboard.',
							'speechkit'
						) }
					</p>
					<ExternalLink href={ projectUrl }>
						{ __( 'BeyondWords dashboard.', 'speechkit' ) }
					</ExternalLink>
				</div>
			</Wrapper>
		</PendingNoticeCheck>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		const beyondwordsProjectId =
			getEditedPostAttribute( 'meta' ).beyondwords_project_id;
		const speechkitProjectId =
			getEditedPostAttribute( 'meta' ).speechkit_project_id;

		const projectId = beyondwordsProjectId || speechkitProjectId;

		const projectUrl = sprintf(
			'%1$s/dashboard/project/%2$d/content',
			process.env.BEYONDWORDS_DASHBOARD_URL,
			projectId
		);

		return {
			projectUrl,
		};
	} ),
] )( PendingNotice );
