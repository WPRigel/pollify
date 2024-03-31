import { cloneDeep } from "lodash";
import { nanoid } from 'nanoid';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useEffect, useState, useRef, useMemo } from "@wordpress/element";
import { subscribe, useSelect, select } from '@wordpress/data';
import {
	Button,
	ButtonGroup,
	SelectControl,
	TextareaControl,
	CheckboxControl,
	Spinner,
	ToolbarGroup,
	ToolbarButton,
	PanelBody,
	__experimentalUnitControl as UnitControl,
	__experimentalUseCustomUnits as useCustomUnits,
	__experimentalBorderControl as BorderControl,
} from "@wordpress/components";
import {
	RichText,
	useBlockProps,
	BlockControls,
	InspectorControls,
	PanelColorSettings,
	useSetting,
}  from '@wordpress/block-editor';
import apiFetch from "@wordpress/api-fetch";
import OptionsWrapper from './options-wrapper';

import './style.scss';


/**
 * Returns `true` if the post is done saving, `false` otherwise.
 *
 * @returns {Boolean}
 */
const useAfterSave = () => {
    const [ isPostSaved, setIsPostSaved ] = useState( false );
    const isPostSavingInProgress = useRef( false );
    const { isSavingPost, isAutosavingPost } = useSelect( ( __select ) => {
        return {
            isSavingPost: __select( 'core/editor' ).isSavingPost(),
            isAutosavingPost: __select( 'core/editor' ).isAutosavingPost(),
        }
    } );

    useEffect( () => {
        if ( ( isSavingPost || isAutosavingPost ) && ! isPostSavingInProgress.current ) {
            setIsPostSaved( false );
            isPostSavingInProgress.current = true;
        }
        if ( ! ( isSavingPost || isAutosavingPost ) && isPostSavingInProgress.current ) {
            // Code to run after post is done saving.
            setIsPostSaved( true );
            isPostSavingInProgress.current = false;
        }
    }, [ isSavingPost, isAutosavingPost ] );

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
const Edit = ( props ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ errors, setErrors ] = useState( [] );
	const { clientId, attributes, setAttributes } = props;
	const [ isSavingProcess, setSavingProcess ] = useState(false);

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
		allowedPerComputerResponse,
	} = attributes;

	const availableUnits = useSetting( 'spacing.units' );
	const units = useCustomUnits( {
		availableUnits: availableUnits || [ '%', 'px', 'em', 'rem', 'vw' ],
	} );

	useEffect( () => {
		// Check if id is 0 or undefined or null. If yes the create a new poll.
		if ( ! pollClientId ) {
			setAttributes( { pollClientId: clientId } );
		}
	}, [] );

	if ( isLoading ) {
		return (
			<div { ...useBlockProps( { className: 'poll-form' } ) }>
				<Spinner />
			</div>
		);
	}

	if ( errors.length ) {
		return (
			<div { ...useBlockProps( { className: 'poll-form' } ) }>
				{ errors.map( ( error, index ) => {
					return (
						<div key={ index } className='error'>
							{ error }
						</div>
					);
				} ) }
			</div>
		);
	}

	const style = {
		'--pollify-form-width': width,
		'--pollify-submit-button-bg-color': submitButtonBgColor,
		'--pollify-submit-button-text-color': submitButtonTextColor,
		'--pollify-submit-button-hover-bg-color': submitButtonHoverBgColor,
		'--pollify-submit-button-hover-text-color': submitButtonHoverTextColor,
	};

	const blockProps = useBlockProps( { className: 'wp-block-pollify-editor-wrapper', style } );

	return (
		<div { ...blockProps }>
			<InspectorControls group="settings">
				<PanelBody title={ __( 'General settings', 'poll-creator' ) }>
					<SelectControl
						label={ __( 'Status', 'poll-creator' ) }
						value={ status }
						options={ [
							{ label: __( 'Open', 'poll-creator' ), value: 'publish' },
							{ label: __( 'Close', 'poll-creator' ), value: 'draft' },
						] }
						onChange={ ( status ) => setAttributes( { status } ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Confiramtion message', 'poll-creator' ) }>
					<SelectControl
						label={ __( 'On submission', 'poll-creator' ) }
						value={ confirmationMessageType }
						options={ [
							{ label: __( 'View results', 'poll-creator' ), value: 'view-result' },
							{ label: __( 'View message', 'poll-creator' ), value: 'view-message' },
						] }
						onChange={ ( confirmationMessageType ) => setAttributes( { confirmationMessageType } ) }
					/>

					{ confirmationMessageType === 'view-message' && (
						<TextareaControl
							value={ confirmationMessage || __( 'Thanks for voting!', 'poll-creator' ) }
							label={ __( 'Message text', 'crowdsignal-forms' ) }
							placeholder={ __(
								'Thanks for voting!',
								'poll-creator'
							) }
							onChange={ ( confirmationMessage ) => setAttributes( { confirmationMessage } ) }
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Response settings', 'poll-creator' ) }>
					<CheckboxControl
						label={ __( 'Allowed one response per computer', 'poll-creator' ) }
						help={ __( 'If checked, only one response per computer will be allowed.', 'poll-creator' ) }
						checked={ allowedPerComputerResponse }
						onChange={ ( allowedPerComputerResponse ) => setAttributes( { allowedPerComputerResponse } ) }
					/>
				</PanelBody>

			</InspectorControls>
			<InspectorControls group="styles">
				<PanelColorSettings
					title={ __( 'Submit button colors', 'poll-creator' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: submitButtonBgColor,
							onChange: ( submitButtonBgColor ) => setAttributes( { submitButtonBgColor } ),
							label: __( 'Background Color', 'poll-creator' ),
						},
						{
							value: submitButtonTextColor,
							onChange: ( submitButtonTextColor ) => setAttributes( { submitButtonTextColor } ),
							label: __( 'Text Color', 'poll-creator' ),
						},
						{
							value: submitButtonHoverBgColor,
							onChange: ( submitButtonHoverBgColor ) => setAttributes( { submitButtonHoverBgColor } ),
							label: __( 'Hover Background Color', 'poll-creator' ),
						},
						{
							value: submitButtonHoverTextColor,
							onChange: ( submitButtonHoverTextColor ) => setAttributes( { submitButtonHoverTextColor } ),
							label: __( 'Hover Text Color', 'poll-creator' ),
						},
					] }
				>
					<ButtonGroup aria-label={ __( 'Button width' ) }>
						<h2>{ __( 'Button width', 'poll-creator' ) }</h2>

						{ [ 25, 50, 75, 100 ].map( ( widthValue ) => {
							return (
								<Button
									key={ widthValue }
									size="small"
									variant={
										widthValue === submitButtonWidth
											? 'primary'
											: undefined
									}
									onClick={ () => {
										// Check if we are toggling the width off
										const buttonWidth = submitButtonWidth === widthValue ? undefined : widthValue;

										// Update attributes.
										setAttributes( { submitButtonWidth: buttonWidth } );
									} }
								>
									{ widthValue }%
								</Button>
							);
						} ) }

						{ ( submitButtonWidth && 100 !== submitButtonWidth ) && (
							<>
								<h2>{ __( 'Button alignment', 'poll-creator' ) }</h2>

								{ [ 'left', 'center', 'right' ].map( ( alignValue ) => (
									<Button
										key={ alignValue }
										size="medium"
										variant={
											alignValue === submitButtonAlign
												? 'primary'
												: undefined
										}
										onClick={ () => {
											// Update attributes.
											setAttributes( { submitButtonAlign: alignValue } );
										} }
									>
										{ alignValue }
									</Button>
								) ) }
							</>
						) }
					</ButtonGroup>
				</PanelColorSettings>
			</InspectorControls>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon='yes'
						label="Multi check"
						onClick={ () => setAttributes( { optionType: 'multi-check' } ) }
						isActive={ optionType === 'multi-check' }
					/>
					<ToolbarButton
						icon='marker'
						label="Radio button"
						onClick={ () => setAttributes( { optionType: 'radio' } ) }
						isActive={ optionType === 'radio' }
					/>
				</ToolbarGroup>
			</BlockControls>
			<div className='pollify-poll-form'>
				<RichText
					tagName='h4'
					value={title}
					onChange={ ( title ) => setAttributes( { title } ) }
					placeholder={ __( 'Enter the poll question', 'poll-creator' ) }
					allowedFormats={  [ 'core/bold', 'core/link', 'core/italic' ] }
					className='poll-title'
				/>
				<RichText
					tagName='p'
					value={description}
					onChange={ ( description ) => setAttributes( { description } ) }
					placeholder={ __( 'Add a description (optional)', 'poll-creator' ) }
					allowedFormats={  [ 'core/bold', 'core/link', 'core/italic' ] }
					className='poll-description'
				/>
				<OptionsWrapper
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>

				<div className={ classnames( 'wp-block-button poll-block-button', {
					[ `align-${ submitButtonAlign }` ]: submitButtonAlign,
					} ) }>
					<div className={ classnames( 'submit-button-wrapper', {
					[ `has-custom-width wp-block-button-width-${ submitButtonWidth }` ]: submitButtonWidth,
					} ) }>
						<RichText
							className="wp-block-button__link submit-button"
							onChange={ ( submitButtonLabel ) => setAttributes( { submitButtonLabel } ) }
							value={ submitButtonLabel }
							allowedFormats={ [] }
							multiline={ false }
							disableLineBreaks={ true }
						/>
					</div>
				</div>
			</div>
		</div>
	);
}

export default Edit;
