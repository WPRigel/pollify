<?php
/**
 * Main plugin class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use WP_Error;
use wpRigel\Pollify\Model\Poll;
use wpRigel\Pollify\Traits\Singleton;

/**
 * Class FeedbackFactory.
 *
 * @package wpRigel\Pollify
 */
class FeedbackFactory {
	use Singleton;

	/**
	 * Feedback object.
	 *
	 * @var object
	 */
	private $feedback;

	/**
	 * Feedbacks class map.
	 *
	 * @var array
	 */
	protected static $class_map = [
		'poll' => Poll::class, // Default free feature.
	];

	/**
	 * Cconstructor. where we pass the feedback arrat or object
	 * and depending on the type we create the object.
	 *
	 * @param array|object $feedback Feedback array or object.
	 *
	 * @throws WP_Error If feedback is not valid.
	 *
	 * @return void
	 */
	public function __construct( $feedback ) {
		if ( is_array( $feedback ) ) {
			$feedback = (object) $feedback;
		}

		if ( ! is_object( $feedback ) ) {
			throw new WP_Error( 'invalid-feedback', esc_html__( 'Invalid feedback.', 'poll-creator' ), [ 'status' => 400 ] );
		}

		$this->feedback = $feedback;
	}

	/**
	 * Get feedback object.
	 *
	 * @return object
	 */
	public function get(): object {

		// Check if feedback type is set or not.
		if ( empty( $this->feedback->type ) ) {
			return new WP_Error( 'invalid-feedback-type', __( 'Invalid feedback type.', 'poll-creator' ), [ 'status' => 400 ] );
		}

		/**
		 * Filter the feedback classes map.
		 *
		 * @param array $class_map Feedback classes map.
		 * @param object $feedback Feedback object.
		 *
		 * @return array
		 */
		self::$class_map = apply_filters( 'pollify_map_feedback_classes', self::$class_map, $this->feedback );

		// Check if feedback type is valid or not.
		if ( ! array_key_exists( $this->feedback->type, self::$class_map ) ) {
			return new WP_Error( 'invalid-feedback-type', __( 'Invalid feedback type.', 'poll-creator' ), [ 'status' => 400 ] );
		}

		// Get feedback class.
		$class = self::$class_map[ $this->feedback->type ];

		// Create feedback object.
		return new $class( (array) $this->feedback );
	}
}
