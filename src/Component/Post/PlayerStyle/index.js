/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Flex, FlexBlock } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

export function PlayerStyle( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const { postType, playerStyles, defaultPlayerStyle } = useSelect( ( select ) => {
		let playerStyles = [];

		const postType = select( 'core/editor' ).getCurrentPostType();
		const { beyondwords_project_id: postProjectId } = select('core/editor').getEditedPostAttribute('meta');

		if ( postProjectId ) {
			playerStyles = select( 'beyondwords/settings' ).getPlayerStyles( postProjectId ) || [];
		} else {
			const { getSettings } = select( 'beyondwords/settings' );
			const { projectId: settingsProjectId } = getSettings();
			playerStyles = select( 'beyondwords/settings' ).getPlayerStyles( settingsProjectId ) || [];
		}

		return {
			postType,
			playerStyles,
			defaultPlayerStyle: playerStyles.find(x => x.default)
		}
	}, [] );

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const playerStyle = meta.beyondwords_player_style || defaultPlayerStyle?.value;

	const setPlayerStyle = ( newPlayerStyle ) => {
		setMeta( {
			...meta,
			beyondwords_player_style: newPlayerStyle,
		} );
	};

	if (! playerStyles.length) {
		return false;
	}

	return (
		<Wrapper>
			<Flex>
				<FlexBlock>
					<SelectControl
						className="beyondwords--player-style"
						label={ __( 'Player style', 'speechkit' ) }
						options={ [
							{
								label: '',
								value: '',
							},
							...playerStyles,
						] }
						onChange={ ( val ) => setPlayerStyle( val ) }
						value={ playerStyle }
						__nextHasNoMarginBottom
					/>
				</FlexBlock>
			</Flex>
		</Wrapper>
	);
}

export default PlayerStyle;
