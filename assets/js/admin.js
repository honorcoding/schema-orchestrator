/*
 * Schema Orchestrator: admin script.
 *
 * Shows a live "valid / not valid" message under every JSON box.
 * This is only a convenience. The server checks the JSON again when saving.
 */
( function () {
	'use strict';

	function check( textarea, status ) {

		var text = textarea.value.trim();
		var data;

		status.className = 'so-json-status';

		if ( text === '' ) {
			status.textContent = 'Empty: no changes will be made.';
			return;
		}

		try {
			data = JSON.parse( text );
		} catch ( error ) {
			status.textContent = 'Not valid JSON: ' + error.message;
			status.className += ' is-error';
			return;
		}

		if ( data === null || typeof data !== 'object' || Array.isArray( data ) ) {
			status.textContent = 'The top level must be an object, like { "WebSite": { "name": "My site" } }.';
			status.className += ' is-error';
			return;
		}

		status.textContent = 'Valid JSON.';
		status.className += ' is-ok';
	}

	function setup( textarea ) {

		var status = textarea.nextElementSibling;

		if ( ! status || status.className.indexOf( 'so-json-status' ) === -1 ) {
			return;
		}

		var timer = null;

		textarea.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				check( textarea, status );
			}, 300 );
		} );

		check( textarea, status );
	}

	function init() {
		Array.prototype.forEach.call( document.querySelectorAll( 'textarea[data-so-json]' ), setup );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
