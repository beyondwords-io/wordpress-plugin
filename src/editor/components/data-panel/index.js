/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ContentId from '../content-id';

export function DataPanel() {
	return (
		<PanelBody
			title={ __( 'Data', 'speechkit' ) }
			initialOpen={ true }
			className="beyondwords beyondwords-sidebar__data"
		>
			<ContentId wrapper={ PanelRow } />
		</PanelBody>
	);
}

export default DataPanel;
