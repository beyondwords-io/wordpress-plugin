/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Dashicon,
	ExternalLink,
	HorizontalRule,
	PanelBody,
	PanelRow,
} from '@wordpress/components';

export default () => (
	<PanelBody
		title={ __( 'Help', 'speechkit' ) }
		initialOpen={ true }
		className={ 'beyondwords beyondwords-sidebar__help' }
	>
		<PanelRow>
			{ __(
				'For setup instructions, troubleshooting, and FAQs, see our BeyondWords for WordPress guide.',
				'speechkit'
			) }
		</PanelRow>
		<PanelRow>
			<ExternalLink href="https://docs.beyondwords.io/docs-and-guides/content/connect-cms/wordpress/install?utm_source=wordpress&amp;utm_medium=referral&amp;utm_campaign=&amp;utm_content=plugin">
				{ __( 'Setup guide', 'speechkit' ) }
			</ExternalLink>
		</PanelRow>
		<HorizontalRule />
		<PanelRow>
			{ __( 'Need help? Email our support team.', 'speechkit' ) }
		</PanelRow>
		<PanelRow>
			<Button isSecondary href="mailto:support@beyondwords.io">
				<Dashicon icon="email" />
				{ __( 'Email BeyondWords', 'speechkit' ) }
			</Button>
		</PanelRow>
	</PanelBody>
);
