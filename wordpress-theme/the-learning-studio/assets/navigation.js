( function () {
	'use strict';
	const button = document.querySelector( '.menu-toggle' );
	const menu = document.getElementById( 'primary-menu' );
	if ( ! button || ! menu ) {
		return;
	}
	button.closest( '.nav' ).classList.add( 'navigation-ready' );
	button.addEventListener( 'click', function () {
		const open = button.getAttribute( 'aria-expanded' ) === 'true';
		button.setAttribute( 'aria-expanded', String( ! open ) );
		menu.classList.toggle( 'is-open', ! open );
	} );
}() );
