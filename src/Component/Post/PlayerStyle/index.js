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

	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const postProjectId = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' )
				?.beyondwords_project_id,
		[]
	);

	const settingsProjectId = useSelect(
		( select ) => select( 'beyondwords/settings' ).getSettings()?.projectId,
		[]
	);

	const playerStyles = useSelect(
		( select ) => {
			const projectId = postProjectId || settingsProjectId;
			return projectId
				? select( 'beyondwords/settings' ).getPlayerStyles(
						projectId
				  ) || []
				: [];
		},
		[ postProjectId, settingsProjectId ]
	);

	const defaultPlayerStyle = playerStyles.find( ( style ) => style.default );

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const playerStyle =
		meta.beyondwords_player_style || defaultPlayerStyle?.value;

	const setPlayerStyle = ( newPlayerStyle ) => {
		setMeta( {
			...meta,
			beyondwords_player_style: newPlayerStyle,
		} );
	};

	if ( ! playerStyles.length ) {
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
						__next40pxDefaultSize
					/>
				</FlexBlock>
			</Flex>
		</Wrapper>
	);
}

export default PlayerStyle;
