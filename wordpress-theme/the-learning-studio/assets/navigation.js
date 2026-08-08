( function () {
	'use strict';
	const button = document.querySelector( '.menu-toggle' );
	const menu = document.getElementById( 'primary-menu' );
	if ( ! button || ! menu ) {
		return;
	}
	const navigation = button.closest( '.nav' );
	const labels = window.tlsNavigation || {
		expandSubmenu: 'Expand submenu for %s',
		collapseSubmenu: 'Collapse submenu for %s',
	};
	const submenuButtons = [];

	function formatLabel( template, label ) {
		return template.replace( '%s', label );
	}

	function setSubmenuState( submenuButton, open ) {
		const item = submenuButton.parentElement;
		const label = submenuButton.dataset.menuLabel;
		item.classList.toggle( 'submenu-open', open );
		submenuButton.setAttribute( 'aria-expanded', String( open ) );
		submenuButton.setAttribute( 'aria-label', formatLabel( open ? labels.collapseSubmenu : labels.expandSubmenu, label ) );
	}

	function closeSubmenus( exception ) {
		submenuButtons.forEach( function ( submenuButton ) {
			const containsException = exception && submenuButton.parentElement.contains( exception );
			if ( submenuButton !== exception && ! containsException ) {
				setSubmenuState( submenuButton, false );
			}
		} );
	}

	navigation.classList.add( 'navigation-ready' );
	menu.querySelectorAll( '.menu-item-has-children' ).forEach( function ( item, index ) {
		const link = item.querySelector( ':scope > a' );
		const submenu = item.querySelector( ':scope > .sub-menu' );
		if ( ! link || ! submenu ) {
			return;
		}

		const submenuId = submenu.id || 'primary-submenu-' + ( index + 1 );
		const menuLabel = link.textContent.trim();
		const submenuButton = document.createElement( 'button' );
		submenu.id = submenuId;
		submenuButton.type = 'button';
		submenuButton.className = 'submenu-toggle';
		submenuButton.dataset.menuLabel = menuLabel;
		submenuButton.setAttribute( 'aria-controls', submenuId );
		submenuButton.innerHTML = '<span aria-hidden="true">&#9662;</span>';
		item.insertBefore( submenuButton, submenu );
		setSubmenuState( submenuButton, false );
		submenuButtons.push( submenuButton );

		submenuButton.addEventListener( 'click', function () {
			const open = submenuButton.getAttribute( 'aria-expanded' ) !== 'true';
			closeSubmenus( open ? submenuButton : null );
			setSubmenuState( submenuButton, open );
		} );
	} );

	button.addEventListener( 'click', function () {
		const open = button.getAttribute( 'aria-expanded' ) === 'true';
		button.setAttribute( 'aria-expanded', String( ! open ) );
		menu.classList.toggle( 'is-open', ! open );
		if ( open ) {
			closeSubmenus();
		}
	} );

	navigation.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Escape' ) {
			return;
		}

		const openButton = event.target.closest( '.menu-item-has-children' )?.querySelector( ':scope > .submenu-toggle[aria-expanded="true"]' );
		if ( openButton ) {
			setSubmenuState( openButton, false );
			openButton.focus();
			return;
		}

		if ( button.getAttribute( 'aria-expanded' ) === 'true' ) {
			button.setAttribute( 'aria-expanded', 'false' );
			menu.classList.remove( 'is-open' );
			closeSubmenus();
			button.focus();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! navigation.contains( event.target ) ) {
			closeSubmenus();
		}
	} );
}() );
