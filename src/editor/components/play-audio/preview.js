/**
 * Internal dependencies
 */
import PlayAudio from './index';
import PlayerPlaceholder from './placeholder';
import { useHasPlayAudioAction } from './hooks';

/**
 * Renders the live BeyondWords player when the post has everything the player
 * needs, otherwise a placeholder describing where the player will appear.
 *
 * Shared by both sidebar render sites — the Preview panel and the
 * document-settings panel — so they behave identically and neither ever shows
 * an empty space. (The `beyondwords/player` block does the same player/
 * placeholder split inline because it wraps the live player in `<Disabled>`.)
 *
 * @param {Object}   props         Component props.
 * @param {Function} props.wrapper Element to wrap the player in (e.g. PanelRow).
 *
 * @return {Element} The player or the placeholder.
 */
export default function PlayerPreview( { wrapper } ) {
	return useHasPlayAudioAction() ? (
		<PlayAudio wrapper={ wrapper } />
	) : (
		<PlayerPlaceholder />
	);
}
