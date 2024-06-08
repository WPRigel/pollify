/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/global/css/admin.scss":
/*!***********************************!*\
  !*** ./src/global/css/admin.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!********************************!*\
  !*** ./src/global/js/admin.js ***!
  \********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _css_admin_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../css/admin.scss */ "./src/global/css/admin.scss");

/**
 * Run the script when dom is ready.
 */

/* global wpforms_admin, jconfirm, wpCookies, Choices, List, wpf, google */

;
(function ($) {
  'use strict';

  // Admin object.
  const PollifyAdmin = {
    /**
     * Start the engine.
     *
     * @since 1.3.9
     */
    init: function () {
      // Document ready.
      $(PollifyAdmin.ready);

      // Load the Google Charts API.
      PollifyAdmin.loadGoogleCharts();
    },
    /**
     * Document ready.
     */
    ready: function () {
      // If there are screen options we have to move them.
      $('#screen-meta-links, #screen-meta').prependTo('#wp-pollify-header-screen').show();
    },
    /**
     * Draw the regions map.
     *
     * @since 1.3.9
     * @return void
     */
    drawRegionsMap: function () {
      var geoChartMap = document.getElementById('geo-chart-map');
      var locationVotes = JSON.parse(geoChartMap.dataset.locations);
      var data = google.visualization.arrayToDataTable(locationVotes);
      var options = {
        colorAxis: {
          colors: ['#91cdff', '#2271b1']
        },
        magnifyingGlass: {
          enable: true,
          zoomFactor: 15
        }
      };
      var chart = new google.visualization.GeoChart(geoChartMap);
      chart.draw(data, options);
    },
    /**
     * Load the Google Charts API.
     *
     * @since 1.3.9
     */
    loadGoogleCharts: function () {
      if (document.getElementById('geo-chart-map')) {
        google.charts.load('current', {
          'packages': ['geochart']
        });
        google.charts.setOnLoadCallback(PollifyAdmin.drawRegionsMap);
      }
    }
  };
  PollifyAdmin.init();
})(jQuery);
})();

/******/ })()
;
//# sourceMappingURL=admin.js.map