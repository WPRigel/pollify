import '../css/admin.scss';
import apiFetch from '@wordpress/api-fetch';

/**
 * Run the script when dom is ready.
 */

/* global google, jQuery, pollifyAdmin */

( function ( $ ) {
	'use strict';

	// Admin object.
	const PollifyAdmin = {
		/**
		 * Start the engine.
		 *
		 * @since 1.3.9
		 */
		init() {
			// Document ready.
			$( PollifyAdmin.ready );

			// Load the Google Charts API.
			PollifyAdmin.loadGoogleCharts();

			// Wire up permanent delete buttons.
			PollifyAdmin.permanentDelete();
		},

		/**
		 * Document ready.
		 */
		ready() {
			// If there are screen options we have to move them.
			$( '#screen-meta-links, #screen-meta' )
				.prependTo( '#wp-pollify-header-screen' )
				.show();
		},

		/**
		 * Draw the regions map.
		 *
		 * @since 1.3.9
		 */
		drawRegionsMap() {
			const geoChartMap = document.getElementById( 'geo-chart-map' );
			const locationVotes = JSON.parse( geoChartMap.dataset.locations );

			const data = google.visualization.arrayToDataTable( locationVotes );

			const options = {
				colorAxis: { colors: [ '#91cdff', '#2271b1' ] },
				magnifyingGlass: { enable: true, zoomFactor: 15 },
			};

			const chart = new google.visualization.GeoChart( geoChartMap );

			chart.draw( data, options );
		},

		/**
		 * Load the Google Charts API.
		 *
		 * @since 1.3.9
		 */
		loadGoogleCharts() {
			if ( document.getElementById( 'geo-chart-map' ) ) {
				google.charts.load( 'current', {
					packages: [ 'geochart' ],
				} );

				google.charts.setOnLoadCallback( PollifyAdmin.drawRegionsMap );
			}
		},

		/**
		 * Handle permanent poll deletion with stats confirmation.
		 */
		permanentDelete() {
			$( document ).on(
				'click',
				'.pollify-delete-permanently',
				async function ( e ) {
					e.preventDefault();

					const pollId = $( this ).data( 'poll-id' );

					try {
						const stats = await apiFetch( {
							url: `${ pollifyAdmin.restUrl }${ pollId }/stats`,
							method: 'GET',
						} );

						let message = pollifyAdmin.confirmMsg + '\n\n';
						message += `Total Votes: ${ stats.total_votes }\n`;
						message +=
							`Unique Voters: ` +
							( stats.unique_voters !== null
								? stats.unique_voters
								: 'N/A (Anonymous Poll)' );

						// eslint-disable-next-line no-alert
						if ( ! window.confirm( message ) ) {
							return;
						}

						const row = $( this ).closest( 'tr' );

						await apiFetch( {
							url: `${ pollifyAdmin.restUrl }${ pollId }/permanent-delete`,
							method: 'DELETE',
						} );

						row.fadeOut( 300, function () {
							$( this ).remove();
						} );
					} catch ( error ) {
						// eslint-disable-next-line no-alert
						window.alert(
							'Error: ' + ( error.message || 'Unknown error' )
						);
					}
				}
			);
		},
	};

	PollifyAdmin.init();
} )( jQuery );
