/**
 * MLA Commons Profile
 */

( function( $ ) {

	window.mla_commons_profile = {

		init: function() {
			// visibility controls
			$( '#profile-edit-form .editable.hideable' ).each( function() {
				var div = $( this );

				// add visibility controls
				div.append( '<a href="#" class="visibility">hide</a>' );

				// bind visibility controls
				div.find( '.visibility' ).click( function() {
					var a = $( this );

					if ( a.html() === 'hide' ) {
						a.html( 'show' );
						div.addClass( 'collapsed' );
						div.find( 'input[value="adminsonly"]' ).attr( 'checked', true );
						div.find( 'input[value="public"]' ).attr( 'checked', false );
					} else {
						a.html( 'hide' );
						div.removeClass( 'collapsed' );
						div.find( 'input[value="adminsonly"]' ).attr( 'checked', false );
						div.find( 'input[value="public"]' ).attr( 'checked', true );
					}

					return false;
				} );

				if ( div.find( 'input[value="adminsonly"]' ).is( ':checked' ) ) {
					div.find( '.visibility' ).triggerHandler( 'click' );
				}
			} );

			// cancel button to send user back to view mode
			$( '#profile-edit-form #cancel' ).click( function( e ) {
				e.preventDefault();
				window.location = $( '#public' ).attr( 'href' );
			} );

			$( '#remove_academic_interest_filter' ).live( 'click', mla_commons_profile.remove_academic_interest_filter );

			mla_commons_profile.init_show_more_buttons();
		},

		init_show_more_buttons: function() {
			$( '.profile .show-more' ).each( function() {
				var div = $( this );
				var header = div.find( 'h4' );
				var show_more_button = $( '<button class="js-dynamic-show-hide button" title="Show more" data-replace-text="Show less">Show more</button>' );

				header.remove(); // this will be restored after wrapping the remaining contents in div.dynamic-height-wrap

				div
					.addClass( 'js-dynamic-height' )
					.attr( 'data-maxheight', 250 )
					.html( header[0].outerHTML + '<div class="dynamic-height-wrap">' + div.html() + '</div>' + show_more_button[0].outerHTML );
			} );

			// some fields should be taller than the rest
			$( '.profile .work-shared-in-core, .profile .other-publications' ).each( function() {
				$( this ).attr( 'data-maxheight', 400 );
			} );

			$( '.js-dynamic-height' ).dynamicMaxHeight();

			// buddypress adds ajax & link-like functionality to buttons.
			// prevent page from reloading when "show more" button pressed.
			$( '.js-dynamic-show-hide' ).click( function( e ) {
				e.preventDefault();
			} );

			// button is also not automatically hid if itemheight < maxheight. fix it
			$.each( $( '.js-dynamic-height' ), function() {
				if ( parseInt( $( this ).attr('data-maxheight') ) > parseInt( $( this ).attr( 'data-itemheight' ) ) ) {
					$( this ).find( '.js-dynamic-show-hide' ).hide();
				}
			} );
		},

		remove_academic_interest_filter: function( e ) {
			e.preventDefault();

			$( '#academic_interest' ).hide();

			// show message until new results load
			$( '.academic_interest_removed' ).show();

			jQuery.removeCookie( 'academic_interest_term_taxonomy_id', { path: '/' } );

			window.location.replace( window.location.pathname + window.location.search.replace( /academic_interests=[^&]+/, '' ) );
		}
	}

	$( document ).ready( mla_commons_profile.init );

} )( jQuery );
