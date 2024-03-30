/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/poll/edit.js":
/*!**************************!*\
  !*** ./src/poll/edit.js ***!
  \**************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var nanoid__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! nanoid */ "./node_modules/nanoid/index.browser.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _options_wrapper__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./options-wrapper */ "./src/poll/options-wrapper.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./style.scss */ "./src/poll/style.scss");













/**
 * Returns `true` if the post is done saving, `false` otherwise.
 *
 * @returns {Boolean}
 */
const useAfterSave = () => {
  const [isPostSaved, setIsPostSaved] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const isPostSavingInProgress = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
  const {
    isSavingPost,
    isAutosavingPost
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.useSelect)(__select => {
    return {
      isSavingPost: __select('core/editor').isSavingPost(),
      isAutosavingPost: __select('core/editor').isAutosavingPost()
    };
  });
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if ((isSavingPost || isAutosavingPost) && !isPostSavingInProgress.current) {
      setIsPostSaved(false);
      isPostSavingInProgress.current = true;
    }
    if (!(isSavingPost || isAutosavingPost) && isPostSavingInProgress.current) {
      // Code to run after post is done saving.
      setIsPostSaved(true);
      isPostSavingInProgress.current = false;
    }
  }, [isSavingPost, isAutosavingPost]);
  return isPostSaved;
};

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
const Edit = props => {
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [errors, setErrors] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const {
    clientId,
    attributes,
    setAttributes
  } = props;
  const [isSavingProcess, setSavingProcess] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const {
    pollId,
    pollClientId,
    title,
    description,
    options,
    optionType,
    status,
    width,
    submitButtonLabel,
    submitButtonBgColor,
    submitButtonTextColor,
    submitButtonHoverBgColor,
    submitButtonHoverTextColor,
    submitButtonWidth,
    submitButtonAlign,
    confirmationMessageType,
    confirmationMessage,
    allowedPerComputerResponse
  } = attributes;
  const availableUnits = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.useSetting)('spacing.units');
  const units = (0,_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.__experimentalUseCustomUnits)({
    availableUnits: availableUnits || ['%', 'px', 'em', 'rem', 'vw']
  });

  // const getPoll = ( pollId ) => {
  // 	// Get poll object using apiFetch function via this wp-json/pollify/v1/polls/{pollId} rest api.
  // 	apiFetch( {
  // 		path: `/pollify/v1/polls/${pollId}`,
  // 	} ).then( ( response ) => {
  // 		// We will push a new option object because of the next option focus.
  // 		const updatedOptions = [...response.options, { type: 'text', option: '' }];

  // 		// console.log( options );

  // 		setAttributes({
  // 			pollId: parseInt(response.id),
  // 			title: response.title,
  // 			description: response.description,
  // 			options: updatedOptions,
  // 		});

  // 		// console.log( options );

  // 		setIsLoading( false );
  // 	} ).catch( ( error ) => {
  // 		setIsLoading( false );
  // 		setErrors( [ error.message ] );
  // 	});
  // };

  // const createPoll = () => {
  // 	// Create a new poll using apiFetch function via this wp-json/pollify/v1/polls rest api.
  // 	apiFetch( {
  // 		method: 'POST',
  // 		path: `/pollify/v1/polls`,
  // 		data: {
  // 			title: title,
  // 			options: options,
  // 		},
  // 	} ).then( ( response ) => {
  // 		// We will push a new option object because of next option focus.
  // 		response.options.push( {
  // 			type: 'text',
  // 			option: '',
  // 		} );

  // 		setAttributes( {
  // 			pollId: parseInt(response.id),
  // 			title: response.title,
  // 			description: response.description,
  // 			options: response.options,
  // 		} );
  // 		setIsLoading( false );
  // 	} ).catch( ( error ) => {
  // 		setIsLoading( false );
  // 		setErrors( [ error.message ] );
  // 	});
  // }

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    // if ( ! pollId ) {
    // 	createPoll();
    // } else {
    // 	getPoll( pollId );
    // }
    // Check if id is 0 or undefined or null. If yes the create a new poll.
    if (!pollClientId) {
      setAttributes({
        pollClientId: clientId
      });
    }
    console.log((0,nanoid__WEBPACK_IMPORTED_MODULE_10__.nanoid)());
    console.log(clientId);
  }, []);
  if (isLoading) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.useBlockProps)({
        className: 'poll-form'
      })
    }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Spinner, null));
  }
  if (errors.length) {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.useBlockProps)({
        className: 'poll-form'
      })
    }, errors.map((error, index) => {
      return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        key: index,
        className: "error"
      }, error);
    }));
  }
  const style = {
    '--pollify-form-width': width,
    '--pollify-submit-button-bg-color': submitButtonBgColor,
    '--pollify-submit-button-text-color': submitButtonTextColor,
    '--pollify-submit-button-hover-bg-color': submitButtonHoverBgColor,
    '--pollify-submit-button-hover-text-color': submitButtonHoverTextColor
  };
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.useBlockProps)({
    className: 'wp-block-pollify-editor-wrapper',
    style
  });
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...blockProps
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.InspectorControls, {
    group: "settings"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('General settings', 'pollify')
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.SelectControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Status', 'pollify'),
    value: status,
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Open', 'pollify'),
      value: 'publish'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Close', 'pollify'),
      value: 'draft'
    }],
    onChange: status => setAttributes({
      status
    })
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Confiramtion message', 'pollify')
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.SelectControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('On submission', 'pollify'),
    value: confirmationMessageType,
    options: [{
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('View results', 'pollify'),
      value: 'view-result'
    }, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('View message', 'pollify'),
      value: 'view-message'
    }],
    onChange: confirmationMessageType => setAttributes({
      confirmationMessageType
    })
  }), confirmationMessageType === 'view-message' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.TextareaControl, {
    value: confirmationMessage || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Thanks for voting!', 'pollify'),
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Message text', 'crowdsignal-forms'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Thanks for voting!', 'pollify'),
    onChange: confirmationMessage => setAttributes({
      confirmationMessage
    })
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.PanelBody, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Response settings', 'pollify')
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.CheckboxControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Allowed one response per computer', 'pollify'),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('If checked, only one response per computer will be allowed.', 'pollify'),
    checked: allowedPerComputerResponse,
    onChange: allowedPerComputerResponse => setAttributes({
      allowedPerComputerResponse
    })
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.InspectorControls, {
    group: "styles"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.PanelColorSettings, {
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Submit button colors', 'pollify'),
    initialOpen: false,
    colorSettings: [{
      value: submitButtonBgColor,
      onChange: submitButtonBgColor => setAttributes({
        submitButtonBgColor
      }),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Background Color', 'pollify')
    }, {
      value: submitButtonTextColor,
      onChange: submitButtonTextColor => setAttributes({
        submitButtonTextColor
      }),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Text Color', 'pollify')
    }, {
      value: submitButtonHoverBgColor,
      onChange: submitButtonHoverBgColor => setAttributes({
        submitButtonHoverBgColor
      }),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Hover Background Color', 'pollify')
    }, {
      value: submitButtonHoverTextColor,
      onChange: submitButtonHoverTextColor => setAttributes({
        submitButtonHoverTextColor
      }),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Hover Text Color', 'pollify')
    }]
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.ButtonGroup, {
    "aria-label": (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Button width')
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Button width', 'pollify')), [25, 50, 75, 100].map(widthValue => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Button, {
      key: widthValue,
      size: "small",
      variant: widthValue === submitButtonWidth ? 'primary' : undefined,
      onClick: () => {
        // Check if we are toggling the width off
        const buttonWidth = submitButtonWidth === widthValue ? undefined : widthValue;

        // Update attributes.
        setAttributes({
          submitButtonWidth: buttonWidth
        });
      }
    }, widthValue, "%");
  }), submitButtonWidth && 100 !== submitButtonWidth && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Button alignment', 'pollify')), ['left', 'center', 'right'].map(alignValue => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.Button, {
    key: alignValue,
    size: "medium",
    variant: alignValue === submitButtonAlign ? 'primary' : undefined,
    onClick: () => {
      // Update attributes.
      setAttributes({
        submitButtonAlign: alignValue
      });
    }
  }, alignValue)))))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.BlockControls, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.ToolbarGroup, null, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.ToolbarButton, {
    icon: "yes",
    label: "Multi check",
    onClick: () => setAttributes({
      optionType: 'multi-check'
    }),
    isActive: optionType === 'multi-check'
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_5__.ToolbarButton, {
    icon: "marker",
    label: "Radio button",
    onClick: () => setAttributes({
      optionType: 'radio'
    }),
    isActive: optionType === 'radio'
  }))), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "pollify-poll-form"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.RichText, {
    tagName: "h4",
    value: title,
    onChange: title => setAttributes({
      title
    }),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Enter the poll question', 'pollify'),
    allowedFormats: ['core/bold', 'core/link', 'core/italic'],
    className: "poll-title"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.RichText, {
    tagName: "p",
    value: description,
    onChange: description => setAttributes({
      description
    }),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Add a description (optional)', 'pollify'),
    allowedFormats: ['core/bold', 'core/link', 'core/italic'],
    className: "poll-description"
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_options_wrapper__WEBPACK_IMPORTED_MODULE_8__["default"], {
    attributes: attributes,
    setAttributes: setAttributes
  }), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_2___default()('wp-block-button poll-block-button', {
      [`align-${submitButtonAlign}`]: submitButtonAlign
    })
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_2___default()('submit-button-wrapper', {
      [`has-custom-width wp-block-button-width-${submitButtonWidth}`]: submitButtonWidth
    })
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_6__.RichText, {
    className: "wp-block-button__link submit-button",
    onChange: submitButtonLabel => setAttributes({
      submitButtonLabel
    }),
    value: submitButtonLabel,
    allowedFormats: [],
    multiline: false,
    disableLineBreaks: true
  })))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Edit);

/***/ }),

/***/ "./src/poll/index.js":
/*!***************************!*\
  !*** ./src/poll/index.js ***!
  \***************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./edit */ "./src/poll/edit.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block.json */ "./src/poll/block.json");
/**
 * WordPress Dependencies.
 */


/**
 * Internal dependencies
 */



/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_2__, {
  icon: {
    foreground: '#41C9E2',
    src: 'chart-bar'
  },
  /**
   * @see ./edit.js
   */
  edit: _edit__WEBPACK_IMPORTED_MODULE_1__["default"],
  /**
   * @see ./save.js
   */
  save: () => null
});

/***/ }),

/***/ "./src/poll/option.js":
/*!****************************!*\
  !*** ./src/poll/option.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);



const Option = ({
  index,
  option,
  onChange,
  onNewOption,
  onDelete,
  attributes
}) => {
  const {
    optionType
  } = attributes;
  const handleChange = value => {
    onChange(index, value);
  };
  const handleKeyDown = event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      onNewOption(index + 1);
    }
  };
  const handleDelete = () => onDelete(index);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "option"
  }, (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "option-selector"
  }, optionType === 'multi-check' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    name: "poll-option[]",
    className: "checkbox"
  }), optionType === 'radio' && (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "radio",
    name: "poll-option",
    className: "radio"
  })), (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.RichText, {
    tagName: "label",
    className: "option-label",
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enter option', 'pollify'),
    multiline: false,
    preserveWhiteSpace: false,
    onChange: handleChange,
    onKeyDown: handleKeyDown,
    onRemove: handleDelete,
    onReplace: undefined,
    value: option.option,
    allowedFormats: [],
    withoutInteractiveFormatting: true,
    disableLineBreaks: true
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Option);

/***/ }),

/***/ "./src/poll/options-wrapper.js":
/*!*************************************!*\
  !*** ./src/poll/options-wrapper.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _option_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./option.js */ "./src/poll/option.js");
/* harmony import */ var nanoid__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! nanoid */ "./node_modules/nanoid/index.browser.js");




const setCaretPosition = el => {
  // Focus on the div
  el.focus();

  // Create a range
  const range = document.createRange();

  // Select the content of the div
  range.selectNodeContents(el);

  // Collapse the range to the end
  range.collapse(false);

  // Clear existing selections
  const sel = window.getSelection();
  sel.removeAllRanges();

  // Add the new range
  sel.addRange(range);
};
const shiftAnswerFocus = (wrapper, index) => {
  // Set the cursor at the end of the text.
  const element = wrapper.querySelectorAll('[role=textbox]')[index];
  element && setCaretPosition(element);
};
const OptionsWrapper = ({
  attributes,
  setAttributes
}) => {
  // Set a reference to the poll options wrapper.
  const optionsWrapperRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useRef)();
  const {
    options
  } = attributes;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (options.length === 0) {
      // Push a new options with nanoID.
      setAttributes({
        options: [{
          option_id: (0,nanoid__WEBPACK_IMPORTED_MODULE_2__.nanoid)(),
          type: 'text',
          option: ''
        }]
      });
    }
  }, []);
  const handleChangeOption = (index, value) => {
    // Update the options array.
    setAttributes({
      options: options.map((option, i) => {
        if (index === i) {
          option.option = value;
        }
        return option;
      })
    });

    // Create a new option object once the last option is filled.
    if (index === options.length - 1) {
      setAttributes({
        options: [...options, {
          option_id: (0,nanoid__WEBPACK_IMPORTED_MODULE_2__.nanoid)(),
          type: 'text',
          option: ''
        }]
      });
    }
  };
  const handleNewOption = insertAt => {
    // Insert a new option object in the options array.
    if (insertAt <= options.length) {
      setAttributes({
        options: [...options.slice(0, insertAt), {
          option_id: (0,nanoid__WEBPACK_IMPORTED_MODULE_2__.nanoid)(),
          type: 'text',
          option: ''
        }, ...options.slice(insertAt, options.length)]
      });
      shiftAnswerFocus(optionsWrapperRef.current, Math.min(insertAt, options.length));
    }
  };
  const handleOnDelete = index => {
    shiftAnswerFocus(optionsWrapperRef.current, Math.max(index - 1, 0));
    // Delete an option object from the options array.
    if (options.length > 1) {
      setAttributes({
        options: options.filter((option, i) => {
          return i !== index;
        })
      });
    }
  };
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "poll-options-wrapper",
    ref: optionsWrapperRef
  }, options.length && options.map((option, index) => {
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)(_option_js__WEBPACK_IMPORTED_MODULE_1__["default"], {
      attributes: attributes,
      key: index,
      parentRef: optionsWrapperRef,
      index: index,
      option: option,
      onChange: handleChangeOption,
      onNewOption: handleNewOption,
      onDelete: handleOnDelete
    });
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (OptionsWrapper);

/***/ }),

/***/ "./node_modules/classnames/index.js":
/*!******************************************!*\
  !*** ./node_modules/classnames/index.js ***!
  \******************************************/
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;
	var nativeCodeString = '[native code]';

	function classNames() {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg)) {
				if (arg.length) {
					var inner = classNames.apply(null, arg);
					if (inner) {
						classes.push(inner);
					}
				}
			} else if (argType === 'object') {
				if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
					classes.push(arg.toString());
					continue;
				}

				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "./src/poll/style.scss":
/*!*****************************!*\
  !*** ./src/poll/style.scss ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "lodash":
/*!*************************!*\
  !*** external "lodash" ***!
  \*************************/
/***/ ((module) => {

"use strict";
module.exports = window["lodash"];

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["apiFetch"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

"use strict";
module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./node_modules/nanoid/index.browser.js":
/*!**********************************************!*\
  !*** ./node_modules/nanoid/index.browser.js ***!
  \**********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   customAlphabet: () => (/* binding */ customAlphabet),
/* harmony export */   customRandom: () => (/* binding */ customRandom),
/* harmony export */   nanoid: () => (/* binding */ nanoid),
/* harmony export */   random: () => (/* binding */ random),
/* harmony export */   urlAlphabet: () => (/* reexport safe */ _url_alphabet_index_js__WEBPACK_IMPORTED_MODULE_0__.urlAlphabet)
/* harmony export */ });
/* harmony import */ var _url_alphabet_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./url-alphabet/index.js */ "./node_modules/nanoid/url-alphabet/index.js");


let random = bytes => crypto.getRandomValues(new Uint8Array(bytes))
let customRandom = (alphabet, defaultSize, getRandom) => {
  let mask = (2 << (Math.log(alphabet.length - 1) / Math.LN2)) - 1
  let step = -~((1.6 * mask * defaultSize) / alphabet.length)
  return (size = defaultSize) => {
    let id = ''
    while (true) {
      let bytes = getRandom(step)
      let j = step
      while (j--) {
        id += alphabet[bytes[j] & mask] || ''
        if (id.length === size) return id
      }
    }
  }
}
let customAlphabet = (alphabet, size = 21) =>
  customRandom(alphabet, size, random)
let nanoid = (size = 21) => {
  let id = ''
  let bytes = crypto.getRandomValues(new Uint8Array(size))
  while (size--) {
    id += _url_alphabet_index_js__WEBPACK_IMPORTED_MODULE_0__.urlAlphabet[bytes[size] & 63]
  }
  return id
}


/***/ }),

/***/ "./node_modules/nanoid/url-alphabet/index.js":
/*!***************************************************!*\
  !*** ./node_modules/nanoid/url-alphabet/index.js ***!
  \***************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   urlAlphabet: () => (/* binding */ urlAlphabet)
/* harmony export */ });
const urlAlphabet =
  'useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict'


/***/ }),

/***/ "./src/poll/block.json":
/*!*****************************!*\
  !*** ./src/poll/block.json ***!
  \*****************************/
/***/ ((module) => {

"use strict";
module.exports = JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"pollify/poll","version":"1.0.0","title":"Poll","category":"widgets","icon":"file:./images/poll-icon.svg","description":"Poll block for creating poll with posts","supports":{"align":["center","full","wide"],"alignWide":true,"html":false,"class":true,"customClassName":false,"color":{"background":true,"text":true,"border":true},"spacing":{"margin":true,"padding":true,"width":true},"__experimentalBorder":{"color":true,"radius":true,"style":true,"width":true,"__experimentalDefaultControls":{"color":true,"radius":true,"style":true,"width":true}}},"attributes":{"pollId":{"type":"integer","default":0},"pollClientId":{"type":"string","default":""},"title":{"type":"string","default":""},"description":{"type":"string","default":""},"options":{"type":"array","default":[]},"optionType":{"type":"string","default":"radio"},"status":{"type":"string","default":"publish"},"width":{"type":"string","default":"100%"},"submitButtonLabel":{"type":"string","default":"Vote"},"submitButtonBgColor":{"type":"string","default":"#3858e9"},"submitButtonTextColor":{"type":"string","default":"#ffffff"},"submitButtonHoverBgColor":{"type":"string","default":"#000000"},"submitButtonHoverTextColor":{"type":"string","default":"#ffffff"},"submitButtonWidth":{"type":"number","default":25},"submitButtonAlign":{"type":"string","default":"left"},"confirmationMessageType":{"type":"string","default":"view-message"},"confirmationMessage":{"type":"string","default":"Thank you for voting!"},"allowedPerComputerResponse":{"type":"boolean","default":false},"style":{"type":"object","default":{"color":{"background":"#ffffff","text":"#222222","border":"#e6e6e6"},"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}},"border":{"color":"#fafafa","style":"solid","width":"3px"}}}},"textdomain":"pollify","editorScript":"file:./index.js","style":"file:./style-index.css","viewScript":"file:./view.js"}');

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
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
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
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"poll/index": 0,
/******/ 			"poll/style-index": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkpollify"] = self["webpackChunkpollify"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["poll/style-index"], () => (__webpack_require__("./src/poll/index.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=index.js.map