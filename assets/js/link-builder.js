/**
 * Directorist Affiliate — dashboard link builder.
 *
 * Loaded only by the dashboard shortcode, and only for approved affiliates.
 *
 * Picking a content type reveals either a title search (combobox with an
 * ARIA listbox, keyboard navigable) or a URL field for custom links. Both
 * ask the server for the finished referral link, so the affiliate's code is
 * never taken from the page.
 *
 * @package DirectoristAffiliate
 */

( function () {
	'use strict';

	var config = window.directoristAffiliateLinkBuilder || {};
	var i18n   = config.i18n || {};
	var DEBOUNCE = 300;

	function text( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	document.querySelectorAll( '[data-da-builder]' ).forEach( function ( builder ) {
		var typeSelect   = builder.querySelector( '[data-da-builder-type]' );
		var hint         = builder.querySelector( '[data-da-builder-hint]' );
		var searchGroup  = builder.querySelector( '[data-da-builder-search]' );
		var searchInput  = builder.querySelector( '#da-builder-search' );
		var resultList   = builder.querySelector( '#da-builder-results' );
		var status       = builder.querySelector( '[data-da-builder-status]' );
		var customGroup  = builder.querySelector( '[data-da-builder-custom]' );
		var customInput  = builder.querySelector( '#da-builder-url' );
		var customStatus = builder.querySelector( '[data-da-builder-custom-status]' );
		var outputInput  = builder.querySelector( '[data-da-builder-output] input' );

		if ( ! typeSelect || ! searchInput || ! resultList || ! outputInput ) {
			return;
		}

		var timer     = null;
		var inFlight  = null;
		var activeRow = -1;
		var rows      = [];

		function post( action, fields ) {
			var body = new FormData();
			body.append( 'action', action );
			body.append( 'nonce', config.nonce || '' );

			Object.keys( fields ).forEach( function ( key ) {
				body.append( key, fields[ key ] );
			} );

			// One request at a time: a slow earlier search must never
			// overwrite the results of a later one.
			if ( inFlight ) {
				inFlight.abort();
			}

			inFlight = new AbortController();

			return fetch( config.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
				signal: inFlight.signal
			} ).then( function ( response ) {
				return response.json();
			} );
		}

		var shareLinks = builder.querySelectorAll( '[data-da-share-net]' );
		var shareText  = config.shareText || '';

		/**
		 * Point the output field and every share button at one link.
		 *
		 * Share targets are rebuilt here rather than left at their rendered
		 * value, so sharing always sends whatever the affiliate just built.
		 *
		 * @param {string} link Referral link, or '' to fall back to the home link.
		 */
		function showOutput( link ) {
			var current = link || config.homeLink || '';

			outputInput.value = current;

			shareLinks.forEach( function ( anchor ) {
				var url = encodeURIComponent( current );
				var msg = encodeURIComponent( shareText );
				var href;

				switch ( anchor.getAttribute( 'data-da-share-net' ) ) {
					case 'whatsapp':
						href = 'https://wa.me/?text=' + encodeURIComponent( shareText + ' ' + current );
						break;
					case 'x':
						href = 'https://x.com/intent/tweet?text=' + msg + '&url=' + url;
						break;
					case 'facebook':
						href = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
						break;
					case 'email':
						href = 'mailto:?subject=' + msg + '&body=' + url;
						break;
					default:
						return;
				}

				anchor.setAttribute( 'href', href );
			} );
		}

		function closeList() {
			resultList.hidden = true;
			resultList.innerHTML = '';
			searchInput.setAttribute( 'aria-expanded', 'false' );
			searchInput.removeAttribute( 'aria-activedescendant' );
			rows = [];
			activeRow = -1;
		}

		function highlight( index ) {
			rows.forEach( function ( row, i ) {
				var on = i === index;
				row.classList.toggle( 'is-active', on );
				row.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );

			activeRow = index;

			if ( rows[ index ] ) {
				searchInput.setAttribute( 'aria-activedescendant', rows[ index ].id );
				rows[ index ].scrollIntoView( { block: 'nearest' } );
			}
		}

		function choose( result ) {
			searchInput.value = result.title;
			showOutput( result.link );
			closeList();
			status.textContent = '';
		}

		function renderResults( results ) {
			resultList.innerHTML = '';
			rows = [];

			results.forEach( function ( result, index ) {
				var item = document.createElement( 'li' );
				item.id = 'da-builder-result-' + index;
				item.className = 'da-combo-item';
				item.setAttribute( 'role', 'option' );
				item.setAttribute( 'aria-selected', 'false' );

				var title = document.createElement( 'span' );
				title.className = 'da-combo-title';
				title.textContent = result.title;

				var url = document.createElement( 'span' );
				url.className = 'da-combo-url';
				url.textContent = result.url;

				item.appendChild( title );
				item.appendChild( url );

				item.addEventListener( 'mousedown', function ( event ) {
					event.preventDefault();   // keep focus in the input
					choose( result );
				} );

				resultList.appendChild( item );
				rows.push( item );
			} );

			resultList.hidden = false;
			searchInput.setAttribute( 'aria-expanded', 'true' );
			activeRow = -1;
		}

		function runSearch( term ) {
			var minLength = parseInt( config.minLength, 10 ) || 2;

			if ( term.length < minLength ) {
				closeList();
				status.textContent = term.length ? text( 'typeMore', 'Keep typing…' ) : '';
				return;
			}

			status.textContent = text( 'searching', 'Searching…' );

			post( 'directorist_affiliate_search_content', {
				type: typeSelect.value,
				term: term
			} ).then( function ( result ) {
				if ( ! result || ! result.success ) {
					closeList();
					status.textContent = ( result && result.data && result.data.message ) || text( 'error', 'Something went wrong.' );
					return;
				}

				var found = result.data.results || [];

				if ( ! found.length ) {
					closeList();
					status.textContent = result.data.message || text( 'noResults', 'No matches.' );
					return;
				}

				renderResults( found );
				status.textContent = ( text( 'resultsFound', '%d results available.' ) ).replace( '%d', found.length );
			} ).catch( function ( error ) {
				if ( 'AbortError' === error.name ) {
					return;   // superseded by a newer keystroke
				}

				closeList();
				status.textContent = text( 'error', 'Something went wrong.' );
			} );
		}

		function applyType() {
			var option = typeSelect.options[ typeSelect.selectedIndex ];
			var kind   = option ? option.getAttribute( 'data-kind' ) : 'post_type';

			if ( hint && option ) {
				hint.textContent = option.getAttribute( 'data-hint' ) || '';
			}

			// 'none' needs no input at all — the home link is already correct.
			searchGroup.hidden = 'post_type' !== kind && 'taxonomy' !== kind;
			customGroup.hidden = 'url' !== kind;

			searchInput.value = '';
			customInput.value = '';
			status.textContent = '';
			customStatus.textContent = '';
			customStatus.classList.remove( 'is-error' );
			closeList();
			showOutput( '' );
		}

		typeSelect.addEventListener( 'change', applyType );

		searchInput.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			var term = searchInput.value.trim();
			timer = window.setTimeout( function () {
				runSearch( term );
			}, DEBOUNCE );
		} );

		searchInput.addEventListener( 'keydown', function ( event ) {
			if ( resultList.hidden || ! rows.length ) {
				return;
			}

			if ( 'ArrowDown' === event.key ) {
				event.preventDefault();
				highlight( ( activeRow + 1 ) % rows.length );
			} else if ( 'ArrowUp' === event.key ) {
				event.preventDefault();
				highlight( activeRow <= 0 ? rows.length - 1 : activeRow - 1 );
			} else if ( 'Enter' === event.key && activeRow > -1 ) {
				event.preventDefault();
				rows[ activeRow ].dispatchEvent( new MouseEvent( 'mousedown' ) );
			} else if ( 'Escape' === event.key ) {
				closeList();
			}
		} );

		searchInput.addEventListener( 'blur', function () {
			window.setTimeout( closeList, 150 );
		} );

		// Custom URL: validated server-side, since only the server knows what
		// counts as "on this site".
		function checkCustom() {
			var url = customInput.value.trim();

			if ( ! url ) {
				showOutput( '' );
				customStatus.textContent = '';
				customStatus.classList.remove( 'is-error' );
				return;
			}

			customStatus.classList.remove( 'is-error' );
			customStatus.textContent = text( 'checking', 'Checking…' );

			post( 'directorist_affiliate_custom_link', { url: url } ).then( function ( result ) {
				if ( ! result || ! result.success ) {
					showOutput( '' );
					customStatus.textContent = ( result && result.data && result.data.message ) || text( 'error', 'Something went wrong.' );
					customStatus.classList.add( 'is-error' );
					return;
				}

				showOutput( result.data.link );
				customStatus.textContent = '';
			} ).catch( function ( error ) {
				if ( 'AbortError' === error.name ) {
					return;
				}

				showOutput( '' );
				customStatus.textContent = text( 'error', 'Something went wrong.' );
				customStatus.classList.add( 'is-error' );
			} );
		}

		customInput.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( checkCustom, DEBOUNCE + 200 );
		} );

		customInput.addEventListener( 'blur', checkCustom );


		applyType();
	} );
} )();
