/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
// Same file backs the sidebar header's CSS mask, so the mark has one source.
import { ReactComponent as BeyondwordsIcon } from './beyondwords.svg';

export { BeyondwordsIcon };

/**
 * The brand mark followed by the "BeyondWords" label, for a consistent panel title.
 *
 * @return {Element} The title element.
 */
export function BeyondwordsTitle() {
	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				gap: '8px',
			} }
		>
			<BeyondwordsIcon />
			{ __( 'BeyondWords', 'speechkit' ) }
		</span>
	);
}

export default BeyondwordsIcon;
