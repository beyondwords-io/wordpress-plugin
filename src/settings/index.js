/**
 * Settings feature entry — registers the `beyondwords/settings` data store.
 */
import { register } from '@wordpress/data';
import store from './store';

register( store );
