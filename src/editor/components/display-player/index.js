/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import DisplayPlayerCheck from './check';

export function DisplayPlayer( { wrapper } ) {
	const Wrapper = wrapper || Fragment;

	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const displayPlayer = meta.beyondwords_disabled !== '1';

	const onUpdateDisplayPlayer = ( newDisplayPlayer ) => {
		setMeta( {
			...meta,
			beyondwords_disabled: newDisplayPlayer ? '' : '1',
		} );
	};

	return (
		<DisplayPlayerCheck>
			<Wrapper>
				<CheckboxControl
					className="beyondwords--display-player"
					label={ __( 'Display player', 'speechkit' ) }
					checked={ displayPlayer }
					onChange={ () => {
						onUpdateDisplayPlayer( ! displayPlayer );
					} }
					__nextHasNoMarginBottom
				/>
			</Wrapper>
		</DisplayPlayerCheck>
	);
}

export default DisplayPlayer;
