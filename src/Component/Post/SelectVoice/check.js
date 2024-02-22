/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

export function SelectVoiceCheck( { hasSelectVoiceAction, children } ) {
	if ( ! hasSelectVoiceAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => {
		const { getSettings } = select( 'beyondwords/settings' );

		const { languages } = getSettings();

		return {
			hasSelectVoiceAction: !! languages?.length,
		};
	} ),
] )( SelectVoiceCheck );
