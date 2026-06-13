/**
 * Internal dependencies
 */
import { useCanPlayAudio } from './hooks';

export function PlayAudioCheck( { children } ) {
	const hasPlayAudioAction = useCanPlayAudio();

	if ( ! hasPlayAudioAction ) {
		return null;
	}

	return children;
}

export default PlayAudioCheck;
