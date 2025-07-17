<?php
/**
 * Poll model class.
 *
 * @package wpRigel\Pollify
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Model;

use WP_Error;
use wpRigel\Pollify\Votes;

/**
 * Class Poll.
 *
 * Handle a single poll object with all its data.
 */
class Poll extends Feedback {

	/**
	 * Do vote.
	 *
	 * @param array $options Vote options.
	 * @param array $request Request object.
	 *
	 * @return array|WP_Error
	 */
	public function vote( array $options = [], $request = [] ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		// Use the shared validation method.
		$validation = $this->validate_vote_request( $options );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$settings = $this->get_settings();

		// Save the vote data.
		$vote = Votes::get_instance()->vote(
			[
				'client_id'  => $this->get_client_id(),
				'option_ids' => $options,
			]
		);

		if ( is_wp_error( $vote ) ) {
			return $vote;
		}

		// Reset all user related params before sending via REST.
		unset( $vote['user_id'], $vote['user_ip'], $vote['user_location'], $vote['user_agent'] );

		// Set vote return data.
		$data = [
			'success'  => true,
			'data'     => $vote,
			'settings' => $settings,
		];

		// Check if the settings is view-result then set the result data.
		if (
			! empty( $settings['confirmationMessageType'] )
			&& 'view-result' === $settings['confirmationMessageType']
		) {
			$results = $this->get_results();

			// Pass the result in result template file and return the resust with template.
			ob_start();
			pollify_load_template(
				'results/horizointal-bar-chart.php',
				false,
				[
					'data' => $results,
				]
			);
			$data['resultTemplate'] = ob_get_clean();
			$data['result']         = $results;
		}

		return $data;
	}
}
