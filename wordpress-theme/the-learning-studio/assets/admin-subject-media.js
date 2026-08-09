( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.tls-subject-media' ).forEach( function ( wrapper ) {
			var selectButton = wrapper.querySelector( '.tls-media-select' );
			var removeButton = wrapper.querySelector( '.tls-media-remove' );
			var idInput = wrapper.querySelector( 'input[type="hidden"]' );
			var preview = wrapper.querySelector( '.tls-media-preview' );
			var frame = null;

			if ( ! selectButton || ! idInput || 'undefined' === typeof wp || ! wp.media ) {
				return;
			}

			function updateUi( attachment ) {
				if ( attachment ) {
					idInput.value = attachment.id;
					var size = ( attachment.sizes && attachment.sizes.thumbnail ) ? attachment.sizes.thumbnail : attachment;
					preview.innerHTML = '';
					var img = document.createElement( 'img' );
					img.src = size.url;
					img.alt = '';
					img.style.cssText = 'max-width:150px;height:auto;display:block;border-radius:6px';
					preview.appendChild( img );
					if ( removeButton ) {
						removeButton.hidden = false;
					}
				} else {
					idInput.value = '';
					preview.innerHTML = '';
					if ( removeButton ) {
						removeButton.hidden = true;
					}
				}
			}

			selectButton.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				if ( ! frame ) {
					frame = wp.media( {
						title: selectButton.getAttribute( 'data-title' ) || '',
						library: { type: 'image' },
						multiple: false,
					} );
					frame.on( 'select', function () {
						var attachment = frame.state().get( 'selection' ).first().toJSON();
						updateUi( attachment );
					} );
				}
				frame.open();
			} );

			if ( removeButton ) {
				removeButton.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					updateUi( null );
				} );
			}
		} );
	} );
}() );
