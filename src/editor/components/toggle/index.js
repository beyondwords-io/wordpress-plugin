/**
 * WordPress dependencies
 */
import { Flex, FlexBlock, FlexItem, FormToggle } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';

/**
 * BeyondWords toggle control.
 *
 * Standardised layout: the switch sits on the left and the label fills the row
 * to its right, laid out with the Flex primitives.
 *
 * @param {Object}   props
 * @param {string}   [props.className] Extra class for the Flex wrapper.
 * @param {string}   props.label       Visible, clickable label.
 * @param {boolean}  props.checked     Whether the toggle is on.
 * @param {Function} props.onChange    Change handler.
 * @param {boolean}  [props.disabled]  Whether the toggle is disabled.
 *
 * @return {Element} The toggle.
 */
export function Toggle( {
	className,
	label,
	checked,
	onChange,
	disabled = false,
} ) {
	const instanceId = useInstanceId( Toggle, 'beyondwords-toggle' );

	const classes = [ 'beyondwords-toggle', className ]
		.filter( Boolean )
		.join( ' ' );

	return (
		<Flex className={ classes } justify="flex-start" align="center">
			<FlexItem>
				<FormToggle
					id={ instanceId }
					checked={ checked }
					onChange={ onChange }
					disabled={ disabled }
				/>
			</FlexItem>
			<FlexBlock>
				<label htmlFor={ instanceId }>{ label }</label>
			</FlexBlock>
		</Flex>
	);
}

export default Toggle;
