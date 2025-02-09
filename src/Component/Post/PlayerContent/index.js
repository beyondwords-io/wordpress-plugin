/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Flex, FlexBlock } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

export function PlayerContent( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const postType = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostType()
	}, [] );

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const playerContent = meta.beyondwords_player_content || '';

	const setPlayerContent = ( value ) => {
		setMeta( {
			...meta,
			beyondwords_player_content: value,
		} );
	};

	return (
		<Wrapper>
			<Flex>
				<FlexBlock>
					<SelectControl
						className="beyondwords--player-content"
						label={ __( 'Player content', 'speechkit' ) }
						options={ [
							{
								label: 'Article',
								value: '',
							},
							{
								label: 'Summary',
								value: 'summary',
							},
						] }
						onChange={ ( val ) => setPlayerContent( val ) }
						value={ playerContent }
						__nextHasNoMarginBottom
					/>
				</FlexBlock>
			</Flex>
		</Wrapper>
	);
}

export default PlayerContent;
