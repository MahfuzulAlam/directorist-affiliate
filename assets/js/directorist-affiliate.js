/**
 * Directorist Affiliate — front-end and admin behaviour.
 *
 * 1. Copy-to-clipboard for referral links.
 * 2. Dashboard link builder (destination select → generated link).
 * 3. AJAX submission for every plugin form (marked with [data-da-ajax]).
 *    Forms fall back to a normal POST when fetch is unavailable.
 * 4. Admin niceties: settings sub-tabs, select-all checkboxes.
 *
 * Vanilla JS only — no jQuery.
 *
 * @package DirectoristAffiliate
 */

( function () {
	'use strict';

	var config = window.directoristAffiliate || {};

	function text( key, fallback ) {
		return config[ key ] || fallback;
	}

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
		var original = button.getAttribute( 'data-original-label' );

		if ( null === original ) {
			original = button.textContent;
			button.setAttribute( 'data-original-label', original );
		}

		button.textContent = button.getAttribute( 'data-copied-label' ) || 'Copied!';
		button.classList.add( 'is-copied' );

		window.clearTimeout( button.dataCopyTimer );

		button.dataCopyTimer = window.setTimeout( function () {
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
	 * Dashboard link builder
	 * --------------------------------------------------------------------- */

	document.addEventListener( 'change', function ( event ) {
		var select = event.target.closest( '[data-da-link-select]' );

		if ( ! select ) {
			return;
		}

		var output = document.getElementById( select.getAttribute( 'data-da-link-output' ) );

		if ( output ) {
			output.value = select.value;
		}
	} );

	/* -----------------------------------------------------------------------
	 * Modal dialogs
	 *
	 * Uses the native <dialog> element, so focus trapping, Escape-to-close
	 * and the backdrop come from the browser. If <dialog> is unsupported the
	 * form is revealed inline instead of being unreachable.
	 * --------------------------------------------------------------------- */

	function openModal( dialog ) {
		if ( typeof dialog.showModal === 'function' ) {
			dialog.showModal();
		} else {
			dialog.setAttribute( 'open', 'open' );
		}

		var firstField = dialog.querySelector( 'input:not([type="hidden"]), select, textarea' );

		if ( firstField ) {
			firstField.focus();
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var opener = event.target.closest( '[data-da-modal-open]' );

		if ( opener ) {
			var dialog = document.getElementById( opener.getAttribute( 'data-da-modal-open' ) );

			if ( dialog ) {
				event.preventDefault();
				openModal( dialog );
			}

			return;
		}

		var closer = event.target.closest( '[data-da-modal-close]' );

		if ( closer ) {
			var owner = closer.closest( 'dialog' );

			if ( owner ) {
				event.preventDefault();

				if ( typeof owner.close === 'function' ) {
					owner.close();
				} else {
					owner.removeAttribute( 'open' );
				}
			}
		}
	} );

	// Clicking the backdrop (outside the dialog's own box) closes it.
	document.addEventListener( 'click', function ( event ) {
		var dialog = event.target;

		if ( 'DIALOG' !== dialog.tagName || ! dialog.open ) {
			return;
		}

		var box = dialog.getBoundingClientRect();
		var inside = event.clientX >= box.left && event.clientX <= box.right &&
			event.clientY >= box.top && event.clientY <= box.bottom;

		if ( ! inside && typeof dialog.close === 'function' ) {
			dialog.close();
		}
	} );

	/* -----------------------------------------------------------------------
	 * Date range filter — reveal the custom inputs only for "Custom range"
	 * --------------------------------------------------------------------- */

	document.addEventListener( 'change', function ( event ) {
		var preset = event.target.closest( '[data-da-daterange-preset]' );

		if ( ! preset ) {
			return;
		}

		var wrapper = preset.closest( '[data-da-daterange]' );
		var custom  = wrapper ? wrapper.querySelector( '[data-da-daterange-custom]' ) : null;

		if ( ! custom ) {
			return;
		}

		var isCustom = 'custom' === preset.value;

		custom.hidden = ! isCustom;
		wrapper.classList.toggle( 'is-custom', isCustom );

		if ( isCustom ) {
			var from = custom.querySelector( 'input[type="date"]' );

			if ( from ) {
				from.focus();
			}

			return;
		}

		// Leaving custom mode: drop stale dates so they aren't submitted.
		Array.prototype.forEach.call( custom.querySelectorAll( 'input[type="date"]' ), function ( input ) {
			input.value = '';
		} );

		// Any other preset is self-describing, so apply it immediately.
		if ( preset.form ) {
			preset.form.submit();
		}
	} );

	/* -----------------------------------------------------------------------
	 * Activity panels on the affiliate dashboard
	 *
	 * Progressive enhancement: without JS both panels render stacked under
	 * their own headings, so nothing is unreachable.
	 * --------------------------------------------------------------------- */

	Array.prototype.forEach.call( document.querySelectorAll( '[data-da-panels]' ), function ( group ) {
		var buttons = Array.prototype.slice.call( group.querySelectorAll( '[data-panel-target]' ) );
		var panels  = Array.prototype.slice.call( group.querySelectorAll( '[data-panel]' ) );

		if ( ! buttons.length || ! panels.length ) {
			return;
		}

		group.classList.add( 'is-tabbed' );

		function show( name, remember ) {
			buttons.forEach( function ( button ) {
				var active = button.getAttribute( 'data-panel-target' ) === name;

				button.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				button.setAttribute( 'tabindex', active ? '0' : '-1' );
			} );

			panels.forEach( function ( panel ) {
				panel.classList.toggle( 'is-active', panel.getAttribute( 'data-panel' ) === name );
			} );

			// Recorded in the URL so the tab survives the page reload that
			// follows saving payout details or requesting a payout — landing
			// back on the first tab would lose the visitor's place.
			if ( remember && window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', '#da-' + name );
			}
		}

		buttons.forEach( function ( button, index ) {
			button.addEventListener( 'click', function () {
				show( button.getAttribute( 'data-panel-target' ), true );
			} );

			// Left/right arrows move between tabs, per the tablist pattern.
			button.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowRight' !== event.key && 'ArrowLeft' !== event.key ) {
					return;
				}

				event.preventDefault();

				var next = buttons[ ( index + ( 'ArrowRight' === event.key ? 1 : buttons.length - 1 ) ) % buttons.length ];
				next.focus();
				show( next.getAttribute( 'data-panel-target' ), true );
			} );
		} );

		// Open the tab named in the URL when it exists, else the first one.
		var requested = ( window.location.hash || '' ).replace( '#da-', '' );
		var known     = panels.some( function ( panel ) {
			return panel.getAttribute( 'data-panel' ) === requested;
		} );

		show( known ? requested : panels[ 0 ].getAttribute( 'data-panel' ), false );
	} );

	/* -----------------------------------------------------------------------
	 * Payout method pickers
	 *
	 * Only the chosen method's fields are shown, and the rest are *disabled*
	 * rather than merely hidden — a hidden input still posts, which would
	 * send half-filled details for a method the affiliate did not pick.
	 * --------------------------------------------------------------------- */

	function syncMethodFields( scope ) {
		var select = scope.querySelector( '[data-da-method-select]' );

		if ( ! select ) {
			return;
		}

		// Inside the request modal the whole block can be collapsed behind a
		// saved default; collapsed means "use what is on file", so nothing in
		// here should be submitted at all.
		var wrap    = scope.closest( '[data-da-method-wrap]' );
		var dormant = !! ( wrap && wrap.hidden );

		Array.prototype.forEach.call( scope.querySelectorAll( '[data-da-method-fields]' ), function ( group ) {
			var active = group.getAttribute( 'data-da-method-fields' ) === select.value;

			group.hidden = ! active;

			Array.prototype.forEach.call( group.querySelectorAll( 'input' ), function ( input ) {
				input.disabled = dormant || ! active;
			} );
		} );

		select.disabled = dormant;

		var hint   = scope.querySelector( '[data-da-method-hint]' );
		var option = select.options[ select.selectedIndex ];

		if ( hint && option ) {
			hint.textContent = option.getAttribute( 'data-hint' ) || '';
		}
	}

	Array.prototype.forEach.call( document.querySelectorAll( '[data-da-methods]' ), function ( scope ) {
		syncMethodFields( scope );

		var select = scope.querySelector( '[data-da-method-select]' );

		if ( select ) {
			select.addEventListener( 'change', function () {
				syncMethodFields( scope );
			} );
		}
	} );

	// "Change" swaps the saved-method summary for the full picker.
	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-da-change-method]' );

		if ( ! trigger ) {
			return;
		}

		var form    = trigger.closest( 'form' );
		var wrap    = form ? form.querySelector( '[data-da-method-wrap]' ) : null;
		var summary = form ? form.querySelector( '[data-da-saved-method]' ) : null;

		if ( ! wrap ) {
			return;
		}

		wrap.hidden = false;

		if ( summary ) {
			summary.hidden = true;
		}

		var scope = wrap.querySelector( '[data-da-methods]' );

		if ( scope ) {
			syncMethodFields( scope );
			var select = scope.querySelector( '[data-da-method-select]' );

			if ( select ) {
				select.focus();
			}
		}
	} );

	/* -----------------------------------------------------------------------
	 * Select-all checkboxes in admin tables
	 * --------------------------------------------------------------------- */

	function rowCheckboxes( master ) {
		var scope = master.closest( 'table' );

		return scope ? Array.prototype.slice.call( scope.querySelectorAll( 'tbody input[type="checkbox"]' ) ) : [];
	}

	document.addEventListener( 'change', function ( event ) {
		var master = event.target.closest( '[data-da-check-all]' );

		if ( master ) {
			rowCheckboxes( master ).forEach( function ( box ) {
				box.checked = master.checked;
			} );

			return;
		}

		// Keep the master checkbox in sync with individual rows.
		var row = event.target.closest( 'tbody input[type="checkbox"]' );

		if ( ! row ) {
			return;
		}

		var table = row.closest( 'table' );
		var head  = table ? table.querySelector( '[data-da-check-all]' ) : null;

		if ( ! head ) {
			return;
		}

		var boxes   = rowCheckboxes( head );
		var checked = boxes.filter( function ( box ) {
			return box.checked;
		} );

		head.checked       = boxes.length > 0 && checked.length === boxes.length;
		head.indeterminate = checked.length > 0 && checked.length < boxes.length;
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
			// A form inside a modal shows its notice in the modal, not behind it.
			var panel = form.closest( 'dialog' ) ||
				form.closest( '.directorist-affiliate-tab-panel' ) ||
				form.closest( '.wrap' );

			if ( ! panel ) {
				return;
			}

			notice = panel.querySelector( '.directorist-affiliate-js-notice' );

			if ( ! notice ) {
				notice = document.createElement( 'div' );
				panel.insertBefore( notice, panel.firstChild );
			}

			notice.className = 'notice directorist-affiliate-js-notice ' + ( isSuccess ? 'notice-success' : 'notice-error' );
			notice.textContent = '';

			var paragraph = document.createElement( 'p' );
			paragraph.textContent = message;
			notice.appendChild( paragraph );
		} else {
			var wrap = form.closest( '.directorist-affiliate-wrap' ) || form.parentNode;
			notice   = wrap.querySelector( '.directorist-affiliate-js-notice' );

			if ( ! notice ) {
				notice = document.createElement( 'div' );
				form.parentNode.insertBefore( notice, form );
			}

			notice.className = 'directorist-affiliate-notice directorist-affiliate-js-notice ' +
				( isSuccess ? 'directorist-affiliate-notice--success' : 'directorist-affiliate-notice--error' );
			notice.textContent = message;
		}

		notice.setAttribute( 'role', isSuccess ? 'status' : 'alert' );
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

	/* -----------------------------------------------------------------------
	 * Unsaved settings guard
	 * --------------------------------------------------------------------- */

	( function () {
		var form = document.querySelector( '.directorist-affiliate-settings-tabs form' );

		if ( ! form ) {
			return;
		}

		var dirty = false;

		form.addEventListener( 'change', function () {
			dirty = true;
		} );

		form.addEventListener( 'submit', function () {
			dirty = false;
		} );

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( ! dirty ) {
				return;
			}

			event.preventDefault();
			event.returnValue = '';
		} );
	} )();

	/* -----------------------------------------------------------------------
	 * Submit handler
	 * --------------------------------------------------------------------- */

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
			button.textContent = text( 'submittingLabel', 'Submitting…' );
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
					: text( 'genericError', 'Something went wrong. Please try again.' );

				restoreButton();
				showFormNotice( form, message, !! ( result && result.success ) );

				if ( result && result.success ) {
					handleSuccess( form );
				}
			} )
			.catch( function () {
				restoreButton();
				showFormNotice( form, text( 'genericError', 'Something went wrong. Please try again.' ), false );
			} );
	} );
} )();
