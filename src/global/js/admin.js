import '../css/admin.scss';
/**
 * Run the script when dom is ready.
 */

/* global wpforms_admin, jconfirm, wpCookies, Choices, List, wpf */

;( function( $ ) {

	'use strict';

	// Admin object.
	const PollifyAdmin = {

		/**
		 * Start the engine.
		 *
		 * @since 1.3.9
		 */
		init: function() {
			// Document ready.
			$( PollifyAdmin.ready );
		},

		/**
		 * Document ready.
		 */
		ready: function() {

			// If there are screen options we have to move them.
			$( '#screen-meta-links, #screen-meta' ).prependTo( '#wp-pollify-header-screen' ).show();
		},
	};

	PollifyAdmin.init();

} )( jQuery );
