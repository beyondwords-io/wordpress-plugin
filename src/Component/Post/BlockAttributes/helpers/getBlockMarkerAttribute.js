/**
 * WordPress Dependencies
 */
import { select } from '@wordpress/data';

/**
 * External dependencies
 */
import { v4 as uuidv4 } from 'uuid';

/**
 * Get a beyondwordsMarker attribute for a block.
 *
 * Using the "Duplicate" button in the Block toolbar duplicates the marker
 * attribute too, so we attempt to handle this by getting all the markers in the
 * current Post and assinging new UUIDs to markers that already exist.
 *
 * @since 4.0.0
 *
 * @param {Object} attributes Attributes for the block.
 *
 * @return {String} marker The block marker (segment marker in BeyondWords API).
 */
const getBlockMarkerAttribute = ( attributes ) => {
	const { beyondwordsMarker } = attributes;

	if ( ! beyondwordsMarker ) return uuidv4();

	const existingMarkers = getExistingBlockMarkers()

	if ( countInArray( existingMarkers, beyondwordsMarker ) > 1 ) {
		// Return a new UUID if this marker is a duplicate
		return uuidv4();
	}

	// Return the existing marker only if it is not a duplicate
	return beyondwordsMarker;
}

/**
 * Get all existing Block markers for the currently-edited post.
 *
 * If using `getBlocks()` proves to be too respource-intensive then further work
 * will be required to optimise this.
 *
 * @since 4.0.0
 *
 * @return {String[]} markers The block markers for the current Post.
 */
const getExistingBlockMarkers = () => {
	// Get all Blocks in current Post
	const blocks = select( 'core/block-editor' )
		.getBlocks();

	// Return all non-empty markers of the Blocks
	return blocks
		.map( block => block?.attributes?.beyondwordsMarker )
		.filter( marker => marker );
}

/**
 * Count the number of times an item is in an array.
 *
 * @since 4.0.0
 * @since 4.4.0 Ensure param is array
 *
 * @return {Number} count The number of times the item occurs.
 */
function countInArray(array, item) {
	if (! Array.isArray(array)) {
		return 0;
	}

    var count = 0;

    for ( var i = 0; i < array.length; i++ ) {
        if ( array[ i ] === item ) {
            count++;
        }
    }

    return count;
}

export default getBlockMarkerAttribute;