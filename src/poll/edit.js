import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useEffect } from "@wordpress/element";
import {
	Button,
	ButtonGroup,
	SelectControl,
	TextControl,
	TextareaControl,
	CheckboxControl,
	TimePicker,
	ToolbarGroup,
	ToolbarButton,
	PanelBody
} from "@wordpress/components";
import {
	RichText,
	useBlockProps,
	BlockControls,
	InspectorControls,
	PanelColorSettings,
}  from '@wordpress/block-editor';
import OptionsWrapper from './options-wrapper';

import './style.scss';

/**
 * Is poll closed or not.
 *
 * @param {*} pollStatus
 * @param {*} closedAfterDateTimeUTC
 * @param {*} currentDateTime
 * @returns
 */
const isPollClosed = (
	pollStatus,
	closedAfterDateTimeUTC,
	currentDateTime = new Date()
) => {
	if ( 'draft' === pollStatus ) {
		return true;
	}

	if ( 'schedule' === pollStatus ) {
		const closedAfterDateTime = new Date( closedAfterDateTimeUTC );

		return closedAfterDateTime < currentDateTime;
	}

	return false;
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
	const { clientId, attributes, setAttributes } = props;

	const {
		pollClientId,
		title,
		description,
		optionType,
		status,
		endDate,
		closePollState,
		closePollmessage,
		submitButtonLabel,
		submitButtonBgColor,
		submitButtonTextColor,
		submitButtonHoverBgColor,
		submitButtonHoverTextColor,
		closingBannerBgColor,
		closingBannerTextColor,
		submitButtonWidth,
		submitButtonAlign,
		confirmationMessageType,
		confirmationMessage,
		viewResultconfirmationMessage,
		allowedPerComputerResponse,
		anonymousVoting,
		anonymousVotingMethod,
		requireLogin,
		requireLoginMessage,
		requireLoginAction,
		requireLoginUrl,
	} = attributes;

	const handlePollStatusChange = ( status ) => {
		setAttributes( {
			endDate:
				status === 'schedule'
					? new Date(
							new Date().getTime() + 24 * 60 * 60 * 1000
					  ).toISOString()
					: null,
			status,
		} );
	};

	const handleEndDateChange = ( endDate ) => {
		const dateTime = new Date( endDate );
		setAttributes( { endDate: dateTime.toISOString() } );
	};

	useEffect( () => {
		// Check if id is 0 or undefined or null. If yes the create a new poll.
		if ( ! pollClientId ) {
			setAttributes( { pollClientId: clientId } );
		}
	}, [] );

	const style = {
		'--pollify-submit-button-bg-color': submitButtonBgColor,
		'--pollify-submit-button-text-color': submitButtonTextColor,
		'--pollify-submit-button-hover-bg-color': submitButtonHoverBgColor,
		'--pollify-submit-button-hover-text-color': submitButtonHoverTextColor,
		'--pollify-closing-banner-bg-color': closingBannerBgColor,
		'--pollify-closing-banner-text-color': closingBannerTextColor,
	};

	const isClosed = isPollClosed( status, endDate );

	const blockProps = useBlockProps( { className: 'wp-block-pollify-editor-wrapper', style } );

	return (
		<div { ...blockProps }>
			<InspectorControls group="settings">
				<PanelBody title={ __( 'General settings', 'poll-creator' ) } className="pollify-general-settings-sidebar-wrap">
					<SelectControl
						label={ __( 'Status', 'poll-creator' ) }
						value={ status }
						options={ [
							{ label: __( 'Open', 'poll-creator' ), value: 'publish' },
							{ label: __( 'Close', 'poll-creator' ), value: 'draft' },
							{ label: __( 'Close after', 'poll-creator' ), value: 'schedule' },
						] }
						onChange={ handlePollStatusChange }
					/>

					{ ( status === 'draft' || status === 'schedule' ) && (
						<>
							{ ( status === 'schedule' )  && (
								<TimePicker
									currentTime={ endDate }
									onChange={ handleEndDateChange }
									is12Hour={true}
								/>
							) }

							<SelectControl
								label={ __( 'When poll is closed', 'poll-creator' ) }
								value={ closePollState }
								options={ [
									{ label: __( 'Show poll result', 'poll-creator' ), value: 'show-result' },
									{ label: __( 'Hide poll', 'poll-creator' ), value: 'hide-poll' },
									{ label: __( 'Show poll close message', 'poll-creator' ), value: 'show-message' },
								] }
								onChange={ ( closePollState ) => setAttributes( { closePollState } ) }
							/>
						</>
					) }

					{ closePollState === 'show-message' && (
						<TextareaControl
							value={ closePollmessage || __( 'This poll is closed', 'poll-creator' ) }
							label={ __( 'Close message text', 'poll-creator' ) }
							placeholder={ __(
								'This poll is closed',
								'poll-creator'
							) }
							onChange={ ( closePollmessage ) => setAttributes( { closePollmessage } ) }
						/>
					) }

				</PanelBody>
				<PanelBody title={ __( 'Confiramtion message', 'poll-creator' ) } className="pollify-confirmation-settings-sidebar-wrap">
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
					{ confirmationMessageType === 'view-result' && (
						<TextareaControl
							value={ viewResultconfirmationMessage || __( 'Thanks for voting!', 'poll-creator' ) }
							label={ __( 'View result message text', 'crowdsignal-forms' ) }
							placeholder={ __(
								'Thanks for voting!',
								'poll-creator'
							) }
							onChange={ ( viewResultconfirmationMessage ) => setAttributes( { viewResultconfirmationMessage } ) }
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Response settings', 'poll-creator' ) } className="pollify-response-settings-sidebar-wrap">
					<CheckboxControl
						label={ __( 'Require login to vote', 'poll-creator' ) }
						help={ __( 'When enabled, only logged-in users can vote. Duplicate prevention uses user account instead of IP or browser storage.', 'poll-creator' ) }
						checked={ requireLogin }
						onChange={ ( requireLogin ) => setAttributes( { requireLogin } ) }
					/>

					{ requireLogin && (
						<TextareaControl
							label={ __( 'Login required message', 'poll-creator' ) }
							value={ requireLoginMessage || __( 'Please log in to vote.', 'poll-creator' ) }
							placeholder={ __( 'Please log in to vote.', 'poll-creator' ) }
							onChange={ ( requireLoginMessage ) => setAttributes( { requireLoginMessage } ) }
						/>
					) }

					{ requireLogin && (
						<TextControl
							label={ __( 'Custom login URL', 'poll-creator' ) }
							help={ __( 'Leave empty to use the default WordPress login page. Useful for third-party login plugins.', 'poll-creator' ) }
							value={ requireLoginUrl || '' }
							placeholder="https://"
							onChange={ ( requireLoginUrl ) => setAttributes( { requireLoginUrl } ) }
						/>
					) }

					{ requireLogin && (
						<SelectControl
							label={ __( 'When not logged in, show:', 'poll-creator' ) }
							value={ requireLoginAction || 'hide' }
							options={ [
								{ label: __( 'Login message (hide the poll)', 'poll-creator' ), value: 'hide' },
								{ label: __( 'Poll with results + login popup on vote', 'poll-creator' ), value: 'popup' },
							] }
							onChange={ ( requireLoginAction ) => setAttributes( { requireLoginAction } ) }
						/>
					) }

					<CheckboxControl
						label={ __( 'Enable Anonymous Voting', 'poll-creator' ) }
						help={ __( 'When enabled, no personal data (IP, location, user agent) will be collected. GDPR compliant.', 'poll-creator' ) }
						checked={ anonymousVoting }
						onChange={ ( anonymousVoting ) => setAttributes( { anonymousVoting } ) }
					/>

					<CheckboxControl
						label={ requireLogin
							? __( 'One vote per user', 'poll-creator' )
							: anonymousVoting
								? __( 'Prevent duplicate votes', 'poll-creator' )
								: __( 'Allowed one response per computer', 'poll-creator' )
						}
						help={ requireLogin
							? __( 'If checked, each logged-in user can only vote once (tracked by user account).', 'poll-creator' )
							: anonymousVoting
								? __( 'If checked, users can only vote once using browser storage. If unchecked, users can vote unlimited times (truly anonymous).', 'poll-creator' )
								: __( 'If checked, only one response per computer will be allowed (tracked by IP address).', 'poll-creator' )
						}
						checked={ allowedPerComputerResponse }
						onChange={ ( allowedPerComputerResponse ) => setAttributes( { allowedPerComputerResponse } ) }
					/>

					{ anonymousVoting && allowedPerComputerResponse && ! requireLogin && (
						<SelectControl
							label={ __( 'Storage method for duplicate prevention', 'poll-creator' ) }
							value={ anonymousVotingMethod }
							options={ [
								{ label: __( 'Local Storage - Persistent (prevents revoting even after browser restart)', 'poll-creator' ), value: 'localStorage' },
								{ label: __( 'Session Storage - Temporary (allows revoting after browser closes)', 'poll-creator' ), value: 'sessionStorage' },
								{ label: __( 'Cookie - Persistent with expiration (prevents revoting for 30 days)', 'poll-creator' ), value: 'cookie' },
							] }
							help={ __( 'Choose how to store the vote flag on user\'s browser.', 'poll-creator' ) }
							onChange={ ( anonymousVotingMethod ) => setAttributes( { anonymousVotingMethod } ) }
						/>
					) }
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
				<PanelColorSettings
					title={ __( 'Poll closing banner', 'poll-creator' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: closingBannerBgColor,
							onChange: ( closingBannerBgColor ) => setAttributes( { closingBannerBgColor } ),
							label: __( 'Background Color', 'poll-creator' ),
						},
						{
							value: closingBannerTextColor,
							onChange: ( closingBannerTextColor ) => setAttributes( { closingBannerTextColor } ),
							label: __( 'Text Color', 'poll-creator' ),
						},
					] }
				/>
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

				{ isClosed &&
					<div className='closing-banner'>
						<p>{ closePollmessage }</p>
					</div>
				}

				{ ! isClosed &&
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
				}
			</div>
		</div>
	);
}

export default Edit;
