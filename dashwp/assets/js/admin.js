/* global jQuery, dashwpAdmin */
( function ( $ ) {
	'use strict';

	var data = window.dashwpAdmin || {};
	var i18n = data.i18n || {};

	function textColor( hex ) {
		hex = String( hex || '' ).replace( '#', '' );
		if ( hex.length === 3 ) {
			hex = hex[ 0 ] + hex[ 0 ] + hex[ 1 ] + hex[ 1 ] + hex[ 2 ] + hex[ 2 ];
		}
		if ( hex.length !== 6 ) {
			return '#ffffff';
		}
		var r = parseInt( hex.substr( 0, 2 ), 16 );
		var g = parseInt( hex.substr( 2, 2 ), 16 );
		var b = parseInt( hex.substr( 4, 2 ), 16 );
		return ( r * 299 + g * 587 + b * 114 ) / 1000 > 160 ? '#1d2327' : '#ffffff';
	}

	/* ------------------------------------------------------------------
	 * Live preview
	 * ---------------------------------------------------------------- */

	var $tile = $( '.dashwp-preview .dashwp-tile' );

	function setColor( color ) {
		if ( ! $tile.length || ! color ) {
			return;
		}
		$tile[ 0 ].style.setProperty( '--dashwp-color', color );
		$tile[ 0 ].style.setProperty( '--dashwp-text', textColor( color ) );
	}

	function setIcon( icon ) {
		icon = String( icon || '' ).replace( /[^a-z0-9 _-]/gi, '' ).trim();
		if ( ! icon ) {
			return;
		}
		$( '#dashwp-icon' ).val( icon );
		$( '.dashwp-icon-name' ).text( icon );
		$( '.dashwp-icon-current, .dashwp-preview .dashwp-tile__icon' ).each( function () {
			$( this ).empty().append( $( '<span aria-hidden="true">' ).attr( 'class', icon ) );
		} );
	}

	$( '#dashwp-title' ).on( 'input', function () {
		$tile.find( '.dashwp-tile__title' ).text( this.value );
	} );

	$( '#dashwp-desc' ).on( 'input', function () {
		var $desc = $tile.find( '.dashwp-tile__desc' );
		if ( ! $desc.length ) {
			$desc = $( '<span class="dashwp-tile__desc">' ).appendTo( $tile );
		}
		$desc.text( this.value ).toggle( this.value !== '' );
	} );

	$( '#dashwp-color' ).wpColorPicker( {
		change: function ( event, ui ) {
			setColor( ui.color.toString() );
		},
		clear: function () {
			setColor( $( '#dashwp-color' ).data( 'default-color' ) );
		},
		palettes: [ '#2271b1', '#00a32a', '#e8710a', '#d63638', '#8c5ae8', '#0a9396', '#c2185b', '#1d2327' ],
	} );

	/* ------------------------------------------------------------------
	 * Icon picker
	 * ---------------------------------------------------------------- */

	var $modal = $( '#dashwp-icon-modal' );
	var $grid = $modal.find( '.dashwp-modal__grid' );
	var $search = $modal.find( '.dashwp-modal__search' );
	var library = 'dashicons';
	var lastFocus = null;

	function iconLabel( icon ) {
		return icon.replace( /^dashicons dashicons-/, '' ).replace( /^fa-(solid|regular|brands) fa-/, '' );
	}

	function renderGrid() {
		var term = $search.val().toLowerCase().trim();
		var current = $( '#dashwp-icon' ).val();
		var icons = ( library === 'fa' ? data.fa : data.dashicons ) || [];
		var frag = document.createDocumentFragment();
		var shown = 0;

		icons.forEach( function ( icon ) {
			var label = iconLabel( icon );
			if ( term && label.indexOf( term ) === -1 && icon.indexOf( term ) === -1 ) {
				return;
			}
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'dashwp-icon-option' + ( icon === current ? ' is-selected' : '' );
			btn.setAttribute( 'data-icon', icon );
			btn.title = icon;
			var glyph = document.createElement( 'span' );
			glyph.className = icon;
			glyph.setAttribute( 'aria-hidden', 'true' );
			var small = document.createElement( 'small' );
			small.textContent = label;
			btn.appendChild( glyph );
			btn.appendChild( small );
			frag.appendChild( btn );
			shown++;
		} );

		$grid.empty();
		if ( shown ) {
			$grid[ 0 ].appendChild( frag );
		} else {
			$grid.append( $( '<p class="dashwp-modal__empty">' ).text( i18n.noResults ) );
		}
	}

	function openModal() {
		lastFocus = document.activeElement;
		var current = $( '#dashwp-icon' ).val() || '';
		library = /\bfa-/.test( current ) ? 'fa' : 'dashicons';
		$modal.find( '.dashwp-modal__tabs .button' ).removeClass( 'is-active' ).filter( '[data-lib="' + library + '"]' ).addClass( 'is-active' );
		$search.val( '' );
		$( '#dashwp-custom-icon' ).val( current );
		renderGrid();
		$modal.prop( 'hidden', false );
		$( 'body' ).addClass( 'dashwp-modal-open' );
		$search.trigger( 'focus' );
		var $sel = $grid.find( '.is-selected' );
		if ( $sel.length ) {
			$sel[ 0 ].scrollIntoView( { block: 'center' } );
		}
	}

	function closeModal() {
		$modal.prop( 'hidden', true );
		$( 'body' ).removeClass( 'dashwp-modal-open' );
		if ( lastFocus ) {
			lastFocus.focus();
		}
	}

	$( '.dashwp-icon-open, .dashwp-icon-current' ).on( 'click', openModal );
	$modal.on( 'click', '[data-close]', closeModal );
	$( document ).on( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && ! $modal.prop( 'hidden' ) ) {
			closeModal();
		}
	} );

	$modal.on( 'click', '.dashwp-modal__tabs .button', function () {
		library = $( this ).data( 'lib' );
		$( this ).addClass( 'is-active' ).siblings().removeClass( 'is-active' );
		renderGrid();
		$search.trigger( 'focus' );
	} );

	var searchTimer;
	$search.on( 'input', function () {
		clearTimeout( searchTimer );
		searchTimer = setTimeout( renderGrid, 120 );
	} );

	$grid.on( 'click', '.dashwp-icon-option', function () {
		setIcon( $( this ).data( 'icon' ) );
		closeModal();
	} );

	function applyCustom() {
		var val = $( '#dashwp-custom-icon' ).val().trim();
		// "house" or "fa-house" alone → assume Font Awesome solid.
		if ( val && ! /\s/.test( val ) && val.indexOf( 'dashicons' ) !== 0 ) {
			val = 'fa-solid ' + ( val.indexOf( 'fa-' ) === 0 ? val : 'fa-' + val );
		} else if ( /^dashicons-/.test( val ) ) {
			val = 'dashicons ' + val;
		}
		if ( val ) {
			setIcon( val );
			closeModal();
		}
	}

	$modal.on( 'click', '.dashwp-custom-apply', applyCustom );
	$( '#dashwp-custom-icon' ).on( 'keydown', function ( e ) {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			applyCustom();
		}
	} );

	/* ------------------------------------------------------------------
	 * List: delete confirmation & drag-and-drop ordering
	 * ---------------------------------------------------------------- */

	$( '.dashwp-delete' ).on( 'click', function ( e ) {
		if ( ! window.confirm( i18n.confirmDelete ) ) {
			e.preventDefault();
		}
	} );

	var $status = $( '.dashwp-order-status' );

	$( '.dashwp-list' ).sortable( {
		handle: '.dashwp-handle',
		placeholder: 'dashwp-row-placeholder',
		axis: 'y',
		update: function () {
			var ids = $( this ).children( '.dashwp-row' ).map( function () {
				return $( this ).data( 'id' );
			} ).get();

			$.post( data.ajaxUrl, { action: 'dashwp_reorder', _ajax_nonce: data.nonce, ids: ids } )
				.done( function ( res ) {
					if ( res && res.success ) {
						$status.removeClass( 'is-error' ).text( i18n.saved ).stop( true ).show().delay( 1500 ).fadeOut();
					} else {
						$status.addClass( 'is-error' ).text( i18n.saveError ).show();
					}
				} )
				.fail( function () {
					$status.addClass( 'is-error' ).text( i18n.saveError ).show();
				} );
		},
	} );
}( jQuery ) );
