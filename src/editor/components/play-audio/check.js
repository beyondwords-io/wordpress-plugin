/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { canPlayAudio } from './helpers';

export function PlayAudioCheck( { hasPlayAudioAction, children } ) {
	if ( ! hasPlayAudioAction ) {
		return null;
	}

	return children;
}

export default compose( [
	withSelect( ( select ) => ( {
		hasPlayAudioAction: canPlayAudio( select ),
	} ) ),
] )( PlayAudioCheck );
