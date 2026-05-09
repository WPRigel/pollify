import '../css/admin.scss';
/**
 * Run the script when dom is ready.
 */

/* global google, jQuery */

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
	};

	PollifyAdmin.init();
} )( jQuery );
