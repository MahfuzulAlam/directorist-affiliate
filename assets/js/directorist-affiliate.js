/**
 * Directorist Affiliate — front-end and admin behaviour.
 *
 * 1. Copy-to-clipboard for the referral link.
 * 2. AJAX submission for every plugin form (marked with [data-da-ajax]).
 *    Forms fall back to a normal POST when fetch is unavailable.
 *
 * @package DirectoristAffiliate
 */

( function () {
	'use strict';

	var config = window.directoristAffiliate || {};

	/* -----------------------------------------------------------------------
	 * Copy referral link
	 * --------------------------------------------------------------------- */

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

	/* -----------------------------------------------------------------------
	 * AJAX forms
	 * --------------------------------------------------------------------- */

	function isAdminForm( form ) {
		return !! form.closest( '.directorist-affiliate-admin' );
	}

	function showFormNotice( form, message, isSuccess ) {
		var notice;

		if ( isAdminForm( form ) ) {
			// WordPress-style admin notice above the page title area.
			notice = form.closest( '.wrap' ).querySelector( '.directorist-affiliate-js-notice' );

			if ( ! notice ) {
				notice = document.createElement( 'div' );
				notice.className = 'directorist-affiliate-js-notice';

				var anchor = form.closest( '.wrap' ).querySelector( '.wp-header-end' ) ||
					form.closest( '.wrap' ).querySelector( 'h1' );
				anchor.parentNode.insertBefore( notice, anchor.nextSibling );
			}

			notice.className = 'notice directorist-affiliate-js-notice ' + ( isSuccess ? 'notice-success' : 'notice-error' );
			notice.innerHTML = '';

			var paragraph = document.createElement( 'p' );
			paragraph.textContent = message;
			notice.appendChild( paragraph );
		} else {
			notice = ( form.closest( '.directorist-affiliate-wrap' ) || form.parentNode ).querySelector( '.directorist-affiliate-js-notice' );

			if ( ! notice ) {
				notice = document.createElement( 'div' );
				form.parentNode.insertBefore( notice, form );
			}

			notice.className = 'directorist-affiliate-notice directorist-affiliate-js-notice ' +
				( isSuccess ? 'directorist-affiliate-notice--success' : 'directorist-affiliate-notice--error' );
			notice.textContent = message;
		}

		notice.scrollIntoView( { behavior: 'smooth', block: 'center' } );
	}

	function handleSuccess( form ) {
		var mode = form.getAttribute( 'data-da-success' ) || 'none';

		if ( 'reload' === mode ) {
			window.setTimeout( function () {
				window.location.reload();
			}, 900 );
		} else if ( 'hide' === mode ) {
			form.hidden = true;
		}
	}

	/* -----------------------------------------------------------------------
	 * Settings sub-tabs (progressive enhancement)
	 *
	 * Sections stay inside one form, so hidden fields still submit; without
	 * JavaScript the sections simply render stacked.
	 * --------------------------------------------------------------------- */

	( function () {
		var container = document.querySelector( '.directorist-affiliate-settings-tabs' );

		if ( ! container ) {
			return;
		}

		var buttons  = Array.prototype.slice.call( container.querySelectorAll( '.directorist-affiliate-subtab-link' ) );
		var sections = Array.prototype.slice.call( container.querySelectorAll( '.directorist-affiliate-settings-section' ) );

		if ( ! buttons.length || ! sections.length ) {
			return;
		}

		container.classList.add( 'is-tabbed' );

		function activate( name, updateHash ) {
			buttons.forEach( function ( button ) {
				var active = button.getAttribute( 'data-target' ) === name;

				button.classList.toggle( 'is-active', active );
				button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );

			sections.forEach( function ( section ) {
				section.classList.toggle( 'is-active', section.getAttribute( 'data-section' ) === name );
			} );

			if ( updateHash && window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', '#da-' + name );
			}
		}

		buttons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				activate( button.getAttribute( 'data-target' ), true );
			} );
		} );

		var initial = ( window.location.hash || '' ).replace( '#da-', '' );
		var known   = sections.some( function ( section ) {
			return section.getAttribute( 'data-section' ) === initial;
		} );

		activate( known ? initial : sections[ 0 ].getAttribute( 'data-section' ), false );
	} )();

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest( 'form[data-da-ajax]' );

		if ( ! form ) {
			return;
		}

		var ajaxUrl = config.ajaxUrl || window.ajaxurl || '';

		// Without an endpoint or fetch support, let the normal POST happen.
		if ( ! ajaxUrl || ! window.fetch ) {
			return;
		}

		event.preventDefault();

		var button   = form.querySelector( 'button[type="submit"], input[type="submit"]' );
		var original = button ? button.textContent : '';

		if ( button ) {
			button.disabled    = true;
			button.textContent = config.submittingLabel || 'Submitting…';
		}

		function restoreButton() {
			if ( button ) {
				button.disabled    = false;
				button.textContent = original;
			}
		}

		var data = new FormData( form );
		data.append( 'action', form.getAttribute( 'data-da-ajax' ) );

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				var message = result && result.data && result.data.message
					? result.data.message
					: ( config.genericError || 'Something went wrong. Please try again.' );

				restoreButton();
				showFormNotice( form, message, !! ( result && result.success ) );

				if ( result && result.success ) {
					handleSuccess( form );
				}
			} )
			.catch( function () {
				restoreButton();
				showFormNotice( form, config.genericError || 'Something went wrong. Please try again.', false );
			} );
	} );
} )();
