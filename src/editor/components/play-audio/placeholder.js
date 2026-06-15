/**
 * WordPress dependencies
 */
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BeyondwordsIcon } from '../icon';

/**
 * Shown wherever the BeyondWords player would render but the post doesn't yet
 * have what the player needs to load (no generated content, still pending, a
 * missing project id, …). Keeps every player render site consistent instead of
 * leaving an empty space.
 *
 * @return {Element} The placeholder element.
 */
export default function PlayerPlaceholder() {
	return (
		<Placeholder
			icon={ <BeyondwordsIcon /> }
			label={ __( 'BeyondWords', 'speechkit' ) }
			instructions={ __(
				'The BeyondWords audio player will appear here.',
				'speechkit'
			) }
		/>
	);
}
