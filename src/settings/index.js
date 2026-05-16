/**
 * Settings feature entry — registers the `beyondwords/settings` data store
 * for the block editor to read from.
 */
import { register } from '@wordpress/data';
import store from './store';

register( store );
