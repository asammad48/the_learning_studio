( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var rows = document.querySelector( '.tls-q-rows' );
		var addButton = document.querySelector( '.tls-q-add' );
		var template = document.getElementById( 'tls-q-row-template' );
		if ( ! rows || ! addButton || ! template ) {
			return;
		}

		function syncTypeVisibility( row ) {
			var typeField = row.querySelector( '.tls-q-type' );
			var optionsWrap = row.querySelector( '.tls-q-options-wrap' );
			var tfWrap = row.querySelector( '.tls-q-tf-wrap' );
			if ( ! typeField ) {
				return;
			}
			var isTrueFalse = 'true_false' === typeField.value;
			if ( optionsWrap ) {
				optionsWrap.hidden = isTrueFalse;
			}
			if ( tfWrap ) {
				tfWrap.hidden = ! isTrueFalse;
			}
		}

		function bindRow( row ) {
			var removeButton = row.querySelector( '.tls-q-remove' );
			if ( removeButton ) {
				removeButton.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					row.remove();
				} );
			}
			var typeField = row.querySelector( '.tls-q-type' );
			if ( typeField ) {
				typeField.addEventListener( 'change', function () {
					syncTypeVisibility( row );
				} );
			}
		}

		rows.querySelectorAll( '.tls-q-row' ).forEach( bindRow );

		addButton.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var fragment = template.content.cloneNode( true );
			var row = fragment.querySelector( '.tls-q-row' );
			if ( row ) {
				var newIndex = 'new' + Date.now() + Math.floor( Math.random() * 1000 );
				row.querySelectorAll( '[name]' ).forEach( function ( field ) {
					field.name = field.name.replace( '__new__', newIndex );
				} );
			}
			rows.appendChild( fragment );
			if ( row ) {
				bindRow( row );
				syncTypeVisibility( row );
				var firstField = row.querySelector( 'input, textarea' );
				if ( firstField ) {
					firstField.focus();
				}
			}
		} );
	} );
}() );
