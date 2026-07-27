/**
 * Directorist Affiliate front-end behaviour.
 *
 * Currently: copy-to-clipboard for the referral link.
 *
 * @package DirectoristAffiliate
 */

( function () {
	'use strict';

	function fallbackCopy( input ) {
		input.select();
		input.setSelectionRange( 0, 99999 );

		try {
			return document.execCommand( 'copy' );
		} catch ( err ) {
			return false;
		}
	}

	function showCopied( button ) {
		var original = button.textContent;

		button.textContent = button.getAttribute( 'data-copied-label' ) || 'Copied!';
		button.classList.add( 'is-copied' );

		window.setTimeout( function () {
			button.textContent = original;
			button.classList.remove( 'is-copied' );
		}, 2000 );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.directorist-affiliate-copy' );

		if ( ! button ) {
			return;
		}

		var target = document.getElementById( button.getAttribute( 'data-target' ) );

		if ( ! target ) {
			return;
		}

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( target.value ).then(
				function () {
					showCopied( button );
				},
				function () {
					if ( fallbackCopy( target ) ) {
						showCopied( button );
					}
				}
			);
		} else if ( fallbackCopy( target ) ) {
			showCopied( button );
		}
	} );
} )();
