<?php
/**
 * Poll template for frontend rendering.
 *
 * This template can be overridden by copying it to yourtheme/pollify/poll.php.
 *
 * @var array $attributes
 *
 * @package wpRigel\Pollify
 *
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attributes = ! empty( $attributes ) ? $attributes : [];

// Validate that the poll still exists in the database.
if ( ! empty( $attributes['pollClientId'] ) ) {
	$poll_check = \wpRigel\Pollify\FeedbackManager::get_instance()->get( $attributes['pollClientId'] );
	// If poll doesn't exist (deleted or invalid), don't render anything.
	if ( is_wp_error( $poll_check ) ) {
		return;
	}
}

$require_login        = ! empty( $attributes['requireLogin'] );
$require_login_action = $attributes['requireLoginAction'] ?? 'hide';
$user_not_logged_in   = $require_login && ! is_user_logged_in();
$custom_login_url     = ! empty( $attributes['requireLoginUrl'] ) ? $attributes['requireLoginUrl'] : '';
$login_url            = $custom_login_url
	? add_query_arg( 'redirect_to', rawurlencode( get_permalink() ), $custom_login_url )
	: wp_login_url( get_permalink() );

if ( $user_not_logged_in && 'popup' !== $require_login_action ) {
	?>
	<div
	<?php
	echo wp_kses(
		get_block_wrapper_attributes(),
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

			<p class="pollify-login-required-message"><?php echo wp_kses_post( $attributes['requireLoginMessage'] ?? __( 'Please log in to vote', 'poll-creator' ) ); ?> <a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Login', 'poll-creator' ); ?></a></p>
		</div>
	</div>
	<?php
	return;
}

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

if ( ! empty( $attributes['closingBannerBgColor'] ) ) {
	$styles .= '--pollify-closing-banner-bg-color: ' . $attributes['closingBannerBgColor'] . ';';
}

if ( ! empty( $attributes['closingBannerTextColor'] ) ) {
	$styles .= '--pollify-closing-banner-text-color: ' . $attributes['closingBannerTextColor'] . ';';
}

// Check if the poll status is draft and close poll status is `hide-poll` then return.
if ( 'draft' === $attributes['status'] && 'hide-poll' === $attributes['closePollState'] ) {
	return;
}

// If poll status is schedule and $attribute['endDate'] is less than to current time and close poll status is hide-poll then return.
if ( 'schedule' === $attributes['status'] && strtotime( $attributes['endDate'] ) < time() && 'hide-poll' === $attributes['closePollState'] ) {
	return;
}

$is_draft_with_show_results    = ( 'draft' === $attributes['status'] && 'show-result' === $attributes['closePollState'] );
$is_schedule_with_show_results = ( 'schedule' === $attributes['status'] && strtotime( $attributes['endDate'] ) < time() && 'show-result' === $attributes['closePollState'] );

$is_draft_with_show_close_banner    = ( 'draft' === $attributes['status'] && 'show-message' === $attributes['closePollState'] );
$is_schedule_with_show_close_banner = ( 'schedule' === $attributes['status'] && strtotime( $attributes['endDate'] ) < time() && 'show-message' === $attributes['closePollState'] );

$voter        = new \wpRigel\Pollify\Model\Voter();
$results      = \wpRigel\Pollify\Votes::get_instance()->get_results( $attributes['pollClientId'] );
$is_anonymous = ! empty( $attributes['anonymousVoting'] );

// When requireLogin is on, always check server-side by user_id (even if anonymous).
// When requireLogin is off, only check server-side if NOT anonymous.
if ( $require_login && ! empty( $attributes['allowedPerComputerResponse'] ) ) {
	$is_already_voted = $voter->is_already_voted( $attributes['pollClientId'] );
} else {
	$is_already_voted = ( ! $is_anonymous && ! empty( $attributes['allowedPerComputerResponse'] ) && $voter->is_already_voted( $attributes['pollClientId'] ) );
}
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

		<?php if ( $is_draft_with_show_results || $is_schedule_with_show_results ) : ?>
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
			<?php if ( ! ( $is_already_voted || $is_draft_with_show_close_banner || $is_schedule_with_show_close_banner ) ) : ?>
			<form action="post" class="poll-form"
				data-anonymous-voting="<?php echo ! empty( $attributes['anonymousVoting'] ) ? '1' : '0'; ?>"
				data-allow-duplicate-prevention="<?php echo ! empty( $attributes['allowedPerComputerResponse'] ) ? '1' : '0'; ?>"
				data-voting-method="<?php echo esc_attr( $attributes['anonymousVotingMethod'] ?? 'localStorage' ); ?>"
				data-require-login="<?php echo $require_login ? '1' : '0'; ?>"
				<?php if ( $user_not_logged_in ) : ?>
				data-login-url="<?php echo esc_attr( $login_url ); ?>"
				data-login-message="<?php echo esc_attr( $attributes['requireLoginMessage'] ?? '' ); ?>"
				<?php endif; ?>>
				<?php
					pollify_load_template(
						'poll/options.php',
						false,
						[
							'attributes' => $attributes,
						]
					)
				?>

				<div class="wp-block-button poll-block-button align-<?php echo esc_attr( $attributes['submitButtonAlign'] ); ?>">
					<div class="submit-button-wrapper has-custom-width wp-block-button-width-<?php echo esc_attr( $attributes['submitButtonWidth'] ); ?>"">
						<input type="hidden" name="poll-client-id" value="<?php echo esc_attr( $attributes['pollClientId'] ); ?>">
						<input type="submit" class="wp-block-button__link submit-button" value="<?php echo esc_html( $attributes['submitButtonLabel'] ); ?>" />
					</div>
				</div>
			</form>
			<?php else : ?>
				<?php
				if ( ! ( $is_draft_with_show_close_banner || $is_schedule_with_show_close_banner ) && ( $is_already_voted && ! empty( $attributes['confirmationMessageType'] ) && 'view-result' === $attributes['confirmationMessageType'] ) ) {
					pollify_load_template(
						'results/horizointal-bar-chart.php',
						false,
						[
							'data' => $results,
						]
					);
				} else {
					pollify_load_template(
						'poll/options.php',
						false,
						[
							'attributes' => $attributes,
						]
					);
				}
				?>
				<?php if ( $is_draft_with_show_close_banner || $is_schedule_with_show_close_banner ) : ?>
					<div class="closing-banner">
						<p>
							<?php
								echo wp_kses_post( $attributes['closePollmessage'] ?? __( 'This poll is closed', 'poll-creator' ) );
							?>
						</p>
					</div>
				<?php else : ?>
					<?php if ( $is_already_voted ) : ?>
						<div class="response-message">
							<?php
							if ( ! empty( $attributes['confirmationMessageType'] ) && 'view-result' === $attributes['confirmationMessageType'] ) {
								echo wp_kses_post( $attributes['viewResultconfirmationMessage'] );
							} elseif ( ! empty( $attributes['confirmationMessageType'] ) && 'view-message' === $attributes['confirmationMessageType'] ) {
								echo wp_kses_post( $attributes['confirmationMessage'] );
							}
							?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
