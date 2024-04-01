<?php
/**
 * Poll template for frontend rendering.
 *
 * This template can be overridden by copying it to yourtheme/pollify/poll.php.
 *
 * @var array $attributes
 *
 * @package UnderDev\Pollify
 *
 * @since 1.0.0
 */

declare(strict_types=1);

$attributes = ! empty( $attributes ) ? $attributes : [];

$styles = '';

if ( ! empty( $attributes['submitButtonBgColor'] ) ) {
	$styles .= '--pollify-submit-button-bg-color: ' . $attributes['submitButtonBgColor'] . ';';
}

if ( ! empty( $attributes['submitButtonBgColor'] ) ) {
	$styles .= '--pollify-submit-button-bg-color: ' . $attributes['submitButtonBgColor'] . ';';
}

if ( ! empty( $attributes['submitButtonTextColor'] ) ) {
	$styles .= '--pollify-submit-button-text-color: ' . $attributes['submitButtonTextColor'] . ';';
}

if ( ! empty( $attributes['submitButtonHoverTextColor'] ) ) {
	$styles .= '--pollify-submit-button-hover-text-color: ' . $attributes['submitButtonHoverTextColor'] . ';';
}

if ( ! empty( $attributes['submitButtonHoverBgColor'] ) ) {
	$styles .= '--pollify-submit-button-hover-bg-color: ' . $attributes['submitButtonHoverBgColor'] . ';';
}

// Filter poll options from attribute which value is empty.
$poll_options = array_filter(
	$attributes['options'],
	function ( $option ) {
		return ! empty( $option['option'] );
	}
);

$voter            = new \UnderDev\Pollify\Model\Voter();
$results          = \UnderDev\Pollify\Votes::get_instance()->get_results( $attributes['pollClientId'] );
$is_already_voted = ( ! empty( $attributes['allowedPerComputerResponse'] ) && $voter->is_already_voted( $attributes['pollClientId'] ) );
?>
<div
<?php
echo wp_kses(
	get_block_wrapper_attributes( [ 'style' => esc_attr( $styles ) ] ),
	array(
		'class' => array(),
		'style' => array(),
	)
);
?>
>
	<div class='pollify-poll-form'>
		<h4 class="poll-title rich-text"><?php echo wp_kses_post( $attributes['title'] ); ?></h4>
		<?php if ( ! empty( $attributes['description'] ) ) : ?>
			<p class="poll-description rich-text"><?php echo esc_html( $attributes['description'] ); ?></p>
		<?php endif; ?>

		<?php if ( $is_already_voted && ! empty( $attributes['confirmationMessageType'] ) && 'view-result' === $attributes['confirmationMessageType'] ) : ?>
			<?php
				pollify_load_template(
					'results/horizointal-bar-chart.php',
					false,
					[
						'data' => $results,
					]
				)
			?>
		<?php else : ?>
			<?php if ( ! $is_already_voted ) : ?>
			<form action="post" class="poll-form">
				<?php if ( ! empty( $poll_options ) ) : ?>
					<div class="poll-options-wrapper">
						<?php foreach ( $poll_options as $option ) : ?>
							<div class="option">
								<div class="option-selector">
									<!-- If optionType is radio then input radio otherwise checkbox -->
									<?php if ( 'radio' === $attributes['optionType'] ) : ?>
										<input type="radio" name="poll-option" class="radio" id="option-<?php echo esc_attr( $option['option_id'] ); ?>" value="<?php echo esc_attr( $option['option_id'] ); ?>">
									<?php else : ?>
										<input type="checkbox" name="poll-option[]" class="checkbox" id="option-<?php echo esc_attr( $option['option_id'] ); ?>" value="<?php echo esc_attr( $option['option_id'] ); ?>" >
									<?php endif; ?>
								</div>
								<label class="option-label" for="option-<?php echo esc_attr( $option['option_id'] ); ?>">
									<?php echo wp_kses_post( $option['option'] ); ?>
								</label>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="wp-block-button poll-block-button align-<?php echo esc_attr( $attributes['submitButtonAlign'] ); ?>">
						<div class="submit-button-wrapper has-custom-width wp-block-button-width-<?php echo esc_attr( $attributes['submitButtonWidth'] ); ?>"">
							<input type="hidden" name="poll-client-id" value="<?php echo esc_attr( $attributes['pollClientId'] ); ?>">
							<input type="submit" class="wp-block-button__link submit-button" value="<?php echo esc_html( $attributes['submitButtonLabel'] ); ?>" />
						</div>
				</div>
			</form>
			<?php else : ?>
				<div class="poll-options-wrapper">
					<?php foreach ( $poll_options as $key => $option ) : ?>
						<div class="option">
							<div class="option-selector">
								<!-- If optionType is radio then input radio otherwise checkbox -->
								<?php if ( 'radio' === $attributes['optionType'] ) : ?>
									<input type="radio" name="poll-option" class="radio" id="option-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $option['option_id'] ); ?>">
								<?php else : ?>
									<input type="checkbox" name="poll-option[]" class="checkbox" id="option-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $option['option_id'] ); ?>" >
								<?php endif; ?>
							</div>
							<label class="option-label" for="option-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $option['option'] ); ?></label>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $is_already_voted ) : ?>
			<div class="response-message">
				<?php
					echo (
						! empty( $attributes['confirmationMessageType'] )
						&& 'view-message' === $attributes['confirmationMessageType']
					) ? esc_html( $attributes['confirmationMessage'] ) : esc_html__( 'Thank you for voting!', 'poll-creator' );
				?>
			</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>