/* global jQuery, beyondwords */

'use strict';

jQuery( document ).ready( function ( $ ) {
	$( document ).on( 'click', 'input[name="post_category[]"]', function () {
		const postType = beyondwords.postType;
		const preselect =
			typeof beyondwords.preselect === 'object' &&
			beyondwords.preselect !== null
				? beyondwords.preselect
				: {};

		if ( ! ( postType in preselect ) ) {
			return;
		}

		// Exit if Generate audio is not being handled by taxonomies
		if ( ! ( 'category' in preselect[ postType ] ) ) {
			return;
		}

		// Exit if category is not an array
		if ( ! Array.isArray( preselect[ postType ].category ) ) {
			return;
		}

		const $checkbox = $( 'input#beyondwords_generate_audio' );

		if ( ! $checkbox ) {
			return;
		}

		// Get ALL currently-checked categories
		const checkedCategories = $( 'input[name="post_category[]"]:checked' )
			.map( function () {
				return this.value;
			} )
			.get();

		const intersections = checkedCategories.filter(
			( e ) => preselect[ postType ].category.indexOf( e ) !== -1
		);

		if ( intersections?.length ) {
			$checkbox.prop( 'checked', true );
		} else {
			$checkbox.prop( 'checked', false );
		}
	} );
} );
