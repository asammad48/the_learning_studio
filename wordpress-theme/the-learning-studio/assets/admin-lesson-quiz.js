( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var rows = document.querySelector( '.tls-quiz-rows' );
		var addButton = document.querySelector( '.tls-quiz-add' );
		var template = document.getElementById( 'tls-quiz-row-template' );
		if ( ! rows || ! addButton || ! template ) {
			return;
		}

		function bindRemove( row ) {
			var removeButton = row.querySelector( '.tls-quiz-remove' );
			if ( removeButton ) {
				removeButton.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					row.remove();
				} );
			}
		}

		rows.querySelectorAll( '.tls-quiz-row' ).forEach( bindRemove );

		addButton.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var fragment = template.content.cloneNode( true );
			var row = fragment.querySelector( '.tls-quiz-row' );
			if ( row ) {
				var newIndex = 'new' + Date.now() + Math.floor( Math.random() * 1000 );
				row.querySelectorAll( '[name]' ).forEach( function ( field ) {
					field.name = field.name.replace( '__new__', newIndex );
				} );
			}
			rows.appendChild( fragment );
			if ( row ) {
				bindRemove( row );
				var firstField = row.querySelector( 'input, textarea' );
				if ( firstField ) {
					firstField.focus();
				}
			}
		} );
	} );
}() );
