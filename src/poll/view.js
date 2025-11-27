/**
 * Poll frontend handler.
 *
 * @package wpRigel\Poll
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { sanitize } from 'dompurify';

const Poll = {

	init: function() {
		const pollWrappers = document.querySelectorAll( '.pollify-poll-form' );

		// Loop through each form and attach submit event listeners.
		pollWrappers.forEach( ( pollWrapper ) => {
			const pollForm = pollWrapper.querySelector( 'form' );
			pollForm?.addEventListener( 'submit', Poll.submit );
		} );
	},

	/**
	 * Check if user has already voted (for anonymous voting).
	 *
	 * @param {string} pollId The poll ID.
	 * @param {string} method The storage method (localStorage, sessionStorage, cookie).
	 * @return {boolean} True if already voted, false otherwise.
	 */
	hasVoted: function( pollId, method ) {
		const key = `pollify_voted_${ pollId }`;

		switch ( method ) {
			case 'localStorage':
				return localStorage.getItem( key ) !== null;
			case 'sessionStorage':
				return sessionStorage.getItem( key ) !== null;
			case 'cookie':
				return document.cookie.split( '; ' ).find( ( row ) => row.startsWith( `${ key }=` ) ) !== undefined;
			default:
				return false;
		}
	},

	/**
	 * Mark user as voted (for anonymous voting).
	 *
	 * @param {string} pollId The poll ID.
	 * @param {string} method The storage method (localStorage, sessionStorage, cookie).
	 */
	markAsVoted: function( pollId, method ) {
		const key = `pollify_voted_${ pollId }`;
		const value = Date.now().toString();

		switch ( method ) {
			case 'localStorage':
				localStorage.setItem( key, value );
				break;
			case 'sessionStorage':
				sessionStorage.setItem( key, value );
				break;
			case 'cookie':
				// Set cookie with 30 days expiration
				const expirationDays = 30;
				const date = new Date();
				date.setTime( date.getTime() + ( expirationDays * 24 * 60 * 60 * 1000 ) );
				const expires = `expires=${ date.toUTCString() }`;
				document.cookie = `${ key }=${ value }; ${ expires }; path=/; SameSite=Strict`;
				break;
		}
	},

	sanitizeHTML: function( html ) {
		return sanitize( html, { USE_PROFILES: { html: true } } );
	},

	startLoading: function( element ) {
		const formWrapper = element.closest( '.pollify-poll-form' );
		const html = `<div class="loader-wrapper"><div class="loader"></div></div>`;

		// Add style css opacity to .wp-block-pollify-poll class wrapper.
		formWrapper.style.opacity = '0.5';

		// Insert the loading html into the form.
		formWrapper.insertAdjacentHTML( 'afterbegin', this.sanitizeHTML( html ) );
	},

	removeLoading: function ( element ) {
		const formWrapper = element.closest( '.pollify-poll-form' );

		// Remove the loading html from the form.
		formWrapper.querySelector( '.loader-wrapper' )?.remove();

		// Remove style css opacity from .wp-block-pollify-poll class wrapper.
		formWrapper.style.opacity = '1';
	},

	addError: function ( element, error ) {
		const formWrapper = element.closest( '.pollify-poll-form' );
		const html = `<div class="errors">
			<div class="message">${ error }</div>
			<div class="close">&#x2715;</div>
		</div>`;

		// Remove the existing errors html from the form.
		formWrapper.querySelector( '.errors' )?.remove();

		// Insert the errors html into the form.
		formWrapper.insertAdjacentHTML( 'afterbegin', this.sanitizeHTML( html ) );

		// Add event listener to close the error message.
		formWrapper.querySelector( '.errors .close' ).addEventListener( 'click', () => {
			formWrapper.querySelector( '.errors' ).remove();
		} );
	},

	addResonseMessage: function ( element, message ) {
		const mainWrapper = element.closest( '.pollify-poll-form' );
		const html = `<div class="response-message">${ message }</div>`;

		// Remove the existing response html from the form.
		mainWrapper.querySelector( '.submit-button-wrapper' )?.remove();

		// Insert the response html into the form.
		mainWrapper.insertAdjacentHTML( 'beforeend', this.sanitizeHTML( html ) );
	},

	/**
	 * Submit form handler.
	 *
	 * @param {Object} event The event object.
	 */
	submit: function ( event ) {
		// Handle form submission using formData.
		event.preventDefault();

		const formData = new FormData( event.target );
		const pollId = formData.get( 'poll-client-id' );

		// Check if the poll id is valid postive int no.
		if ( ! pollId || parseInt( pollId ) <= 0 ) {
			return;
		}

		// Check if anonymous voting and duplicate prevention are enabled.
		const form = event.target;
		const anonymousVoting = form.getAttribute( 'data-anonymous-voting' ) === '1';
		const allowDuplicatePrevention = form.getAttribute( 'data-allow-duplicate-prevention' ) === '1';
		const votingMethod = form.getAttribute( 'data-voting-method' ) || 'localStorage';

		// If anonymous voting AND duplicate prevention are enabled, check if user has already voted.
		if ( anonymousVoting && allowDuplicatePrevention && Poll.hasVoted( pollId, votingMethod ) ) {
			Poll.addError( event.target, __( 'You have already voted.', 'poll-creator' ) );
			return;
		}

		// Check if the poll-option is checkbox or radio.
		// Depending on type get the form values.
		let pollOptions = [];

		if ( formData.get( 'poll-option' ) ) {
			pollOptions = formData.getAll( 'poll-option' );
		} else if ( formData.get( 'poll-option[]' ) ) {
			pollOptions = formData.getAll( 'poll-option[]' );
		}

		Poll.startLoading( event.target );

		// Need to send API request to vote.
		apiFetch( {
			path: `/pollify/v1/vote/${ pollId }`,
			method: 'POST',
			data: {
				options: pollOptions,
				nonce: pollify.nonce
			}
		} ).then( ( response ) => {
			const element = event.target;

			Poll.removeLoading( event.target );

			// If anonymous voting AND duplicate prevention are enabled, mark user as voted.
			if ( anonymousVoting && allowDuplicatePrevention ) {
				Poll.markAsVoted( pollId, votingMethod );
			}

			// Check the the resultTemplate is define and not empty.
			if ( response.resultTemplate ) {
				const wrapper = element.closest( 'form.poll-form' );
				wrapper.innerHTML = Poll.sanitizeHTML( response.resultTemplate );
			} else {
				Poll.addResonseMessage( element, response.settings.confirmationMessage );
			}

		} ).catch( ( error ) => {
			// Remove the loading html from the content.
			Poll.removeLoading( event.target );

			// Add error message to the content.
			Poll.addError( event.target, error.message );
		} );
	}
};

document.addEventListener( 'DOMContentLoaded', () => {
	Poll.init();
} );