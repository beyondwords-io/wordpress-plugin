/**
 * Internal dependencies
 */
import { useHasPlayAudioAction } from './hooks';

/**
 * Renders its children only when the BeyondWords player can load a preview.
 *
 * @param {Object}  props          Component props.
 * @param {Element} props.children Content to gate behind the play-audio check.
 *
 * @return {Element|null} The children when the player can load, else null.
 */
export default function PlayAudioCheck( { children } ) {
	return useHasPlayAudioAction() ? children : null;
}
