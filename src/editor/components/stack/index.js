/**
 * WordPress dependencies
 */
import { Flex } from '@wordpress/components';

/**
 * Vertical stack with a consistent gap.
 *
 * Our controls set `__nextHasNoMarginBottom`, so the vertical spacing between
 * sibling fields is owned here — a single Flex `gap` — rather than by per-control
 * margins. Use it to wrap the contents of every panel/section so spacing stays
 * consistent across the editor.
 *
 * @param {Object}  props          Props (forwarded to Flex).
 * @param {Element} props.children Stack contents.
 *
 * @return {Element} The stack.
 */
export function Stack( { children, ...props } ) {
	return (
		<Flex
			direction="column"
			gap={ 4 }
			align="stretch"
			justify="flex-start"
			{ ...props }
		>
			{ children }
		</Flex>
	);
}

export default Stack;
