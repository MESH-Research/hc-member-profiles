// profile editing

( function( $ ) {
  $( document ).ready( function() {
    // header fields (hidden in main form, duplicated to be accessible by user in the header)
    var init_social_fields = ( function() {
      var social_field_change_handler = function ( e ) {
        var input = $( this ).find( 'input' );
        $( '#profile-edit-form input[name=' + input.attr( 'name' ) + ']' ).val( input.val() );
      };
      $( '#profile-edit-form' ).find( '.field-twitter, .field-facebook, .field-linkedin, .field-orcid' ).each( function () {
        var clone = $( this ).clone();

        // only keep label & input, no permissions radios (or anything else)
        clone
          .find( ':not( label[for^=field], input[id^=field] )' )
          .remove();

        // remove id to prevent conflict
        clone
          .find( 'input' )
          .removeAttr( 'id' );

        // remove corresponding view-only div
        $( '#item-header-content #item-main' )
          .find( '.' + clone.attr( 'class' ) )
          .remove();

        // move to header
        clone
          .appendTo( '#item-header-content #item-main' )
          .change( social_field_change_handler );
      } );
    } )();

    var init_visibility_controls = ( function() {
      $( '#profile-edit-form .editable' ).each( function() {
        var div = $( this );

        // add visibility controls
        div.append( '<a href="#" class="visibility">hide</a>' );

        // bind visibility controls
        div.find( '.visibility' ).click( function() {
          var a = $( this );

          if ( a.html() === 'hide' ) {
            a.html( 'show' );
            div.addClass( 'collapsed' );
            div.find( '.adminsonly input' ).attr( 'checked', true );
            div.find( '.public input' ).attr( 'checked', false );
          } else {
            a.html( 'hide' );
            div.removeClass( 'collapsed' );
            div.find( '.adminsonly input' ).attr( 'checked', false );
            div.find( '.public input' ).attr( 'checked', true );
          }

          return false;
        } );

        if ( div.find( '.adminsonly input' ).is( ':checked' ) ) {
          div.find( '.visibility' ).triggerHandler( 'click' );
        }
      } );
    } )();

    // cancel button to send user back to view mode
    var init_cancel_button = ( function() {
      $( '#profile-edit-form #cancel' ).click( function( e ) {
        e.preventDefault();
        window.location = $( '#public' ).attr( 'href' );
      } );
    } )();

    // highlight changed fields to encourage user to save changes
    var init_change_highlighting = ( function() {
      $( '#profile-edit-form select, #profile-edit-form input, #profile-edit-form textarea, #item-main input' ).change( function() {
        if ( $( this ).is( 'select' ) ) {
          $( this ).siblings( '.select2' ).find( '.select2-selection' ).addClass( 'changed' );
        } else {
          $( this ).addClass( 'changed' );
        }
      } );
    } )();

    // initialize select2 on academic interests edit field
    $( '.js-basic-multiple-tags' ).select2();
  } );
} )( jQuery );
