/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ContentSection from './content-section';
import FormatSection from './format-section';
import VoiceSection from './voice-section';
import PlayerSection from './player-section';

export { ContentSection, FormatSection, VoiceSection, PlayerSection };

export function SettingsPanel() {
	return (
		<Fragment>
			<ContentSection />
			<FormatSection />
			<VoiceSection />
			<PlayerSection />
		</Fragment>
	);
}

export default SettingsPanel;
