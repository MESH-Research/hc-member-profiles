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

      // cancel button to send user back to view mode
      $( '#profile-edit-form #cancel' ).click( function( e ) {
        e.preventDefault();
        window.location = $( '#public' ).attr( 'href' );
      } );

      $( '#profile-edit-form input' ).on( 'change', mla_commons_profile.editor_change_handler );

      $('.js-dynamic-height').dynamicMaxHeight();
      // buddypress adds ajax & link-like functionality to buttons. prevent page from reloading when "show more" button pressed.
      $('.js-dynamic-show-hide').click(function(e) {
        e.preventDefault();
      });
      // button is also not automatically hid if itemheight < maxheight. fix it
      $.each($('.js-dynamic-height'), function() {
        if (parseInt($(this).attr('data-maxheight')) > parseInt($(this).attr('data-itemheight'))) {
          $(this).find('.js-dynamic-show-hide').hide();
        }
      });

      $( '#remove_academic_interest_filter' ).live( 'click', mla_commons_profile.remove_academic_interest_filter );
    },

    remove_academic_interest_filter: function( e ) {
      e.preventDefault();

      $( '#academic_interest' ).hide();

      // show message until new results load
      $( '.academic_interest_removed' ).show();

      jQuery.removeCookie( 'academic_interest_term_taxonomy_id', { path: '/' } );

      window.location.replace( window.location.pathname + window.location.search.replace( /academic_interests=[^&]+/, '' ) );
    },

    /**
     * when changes are made to any field, alert the user their changes are not yet saved
     * for now, just hide the "saved" notice if it exists to avoid confusion
     * TODO highlight changed field(s) (three separate field types to deal with: normal inputs, select2, & tinymce)
     */
    editor_change_handler: function() {
      $( '.bp-template-notice.updated' ).slideUp();
    }

  }

  $( document ).ready( mla_commons_profile.init );

} )( jQuery );
