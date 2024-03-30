<?php
/**
 * Template for displaying all polls with actions
 *
 * @package pollify
 */

declare( strict_types = 1 );

$nav_tab = pollify_filter_input( INPUT_GET, 'tab', POLLIFY_FILTER_SANITIZE_STRING ) ?: 'overview';

$navigations = pollify_poll_results_page_nav();
?>

<div class="wrap pollify-poll-details-wrap">
	<div class="heading-wrap">
		<h1 class="wp-heading-inline">
			<span class="dashicons dashicons-chart-bar"></span>
			<span><?php echo wp_kses_post( $poll->get_title() ); ?></span>
		</h1>
		<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'pollify' ], admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'Back to list', 'pollify' ); ?>
		</a>
	</div>

	<div class="navigation">
		<ul>
			<?php foreach ( $navigations as $navigation ) : ?>
			<li <?php echo $navigation['slug'] === $nav_tab ? 'class="active"' : '' ?>>
				<a href="<?php echo esc_url( $navigation['link'] ); ?>">
					<span class="icon dashicons <?php echo esc_attr( $navigation['icon'] ); ?>"></span>
					<span class="text"><?php echo wp_kses_post( $navigation['title'] ) ?></span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="details-content">
		<?php if ( 'overview' === $nav_tab ) : ?>
		<div class="meta-cards">
			<div class="meta-card-column">
				<div class="result-overview meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'At a glance', 'pollify' ); ?></h2>
					</div>

					<div class="meta-card-content">
						<div class="horizointal-bar-chart">
							<?php $poll_results = $poll->get_results(); ?>

							<?php if ( ! empty( $poll_results ) ) : ?>
								<?php foreach ( $poll_results['options'] as $result_option ) : ?>
								<div class="horizointal-bar-chart__bar">
									<div class="horizointal-bar-chart__bar-label">
										<span class="text"><?php echo wp_kses_post( $result_option['option'] ?? '' ); ?></span>
										<span class="count"><?php echo esc_html( wp_sprintf( __( '%s votes', 'pollify' ), $result_option['votes'] ) ); ?></span>
										<span class="percentage"><?php echo esc_html( wp_sprintf( __( '%s%', 'pollify' ), $result_option['percentage'] ) ); ?></span>
									</div>
									<div class="horizointal-bar-chart__bar-indicator">
										<div class="bar-fill" style="width:<?php echo esc_html( wp_sprintf( __( '%s%', 'pollify' ), $result_option['percentage'] ) ); ?>"></div>
									</div>
								</div>
								<?php endforeach; ?>
								<div class="horizointal-bar-chart__total-count">
									<span class="count"><?php echo esc_html( wp_sprintf( __( 'Total votes: %s', 'pollify' ), $poll_results['total_votes'] ) ); ?></span>
								</div>
							<?php else : ?>
								<p><?php esc_html_e( 'No results found for this poll', 'pollify' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="popular-location meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'Popular Location', 'pollify' ); ?></h2>
					</div>

					<div class="meta-card-content location-data">
						<?php
						$location_votes = $poll->get_ip_votes(
							[
								'per_page' => 20,
								'orderby' => 'votes'
							]
						);
						?>
						<div class="location-map">
							<div id="geo-chart-map" class="geo-chart-map"></div>
							<script type="text/javascript">
								function drawRegionsMap() {
									var data = google.visualization.arrayToDataTable( [
									['Country', 'Votes'],
									<?php foreach ( $location_votes as $geo_data ) : ?>
										['<?php echo esc_js( pollify_get_country_name( $geo_data['location'] ) ); ?>', <?php echo esc_js( $geo_data['votes'] ); ?>],
									<?php endforeach; ?>
									] );

									var options = {
										colorAxis: {colors: [ '#91cdff', '#2271b1' ]},
										magnifyingGlass: {enable: true, zoomFactor: 15}
									};

									var chart = new google.visualization.GeoChart(document.getElementById( 'geo-chart-map' ) );

									chart.draw( data, options );
								}

								google.charts.load('current', {
									'packages':['geochart'],
								});

								google.charts.setOnLoadCallback(drawRegionsMap);
							</script>
						</div>
						<div class="location-list">
							<?php if ( ! empty( $location_votes ) ) : ?>
								<?php foreach ( $location_votes as $location_vote ) : ?>
									<div class="location">
										<div class="country">
											<span class="flag-icon fi fi-<?php echo esc_html( strtolower( $location_vote['location'] ) ); ?> fib"></span>
											<span class="country-name"><?php echo wp_kses_post( pollify_get_country_name( $location_vote['location'] ) ); ?></span>
										</div>
										<div class="count"><?php echo esc_html( $location_vote['votes'] ); ?></div>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="no-data-text"><?php esc_html_e( 'No location data found for this poll', 'pollify' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="ip-details meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'IP overviews', 'pollify' ); ?></h2>
					</div>

					<div class="meta-card-content ip-overview">
						<?php
						$location_votes = $poll->get_ip_votes(
							[
								'per_page' => 5,
							]
						);
						?>
						<div class="ip-data-list">
							<?php if ( ! empty( $location_votes ) ) : ?>
								<table class="ips-table wp-list-table widefat table-view-list">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Location', 'pollify' ); ?></th>
											<th><?php esc_html_e( 'IP Address', 'pollify' ); ?></th>
											<th><?php esc_html_e( 'Vote count', 'pollify' ); ?></th>
										</tr>
									</thead>
									<?php foreach ( $location_votes as $location_vote ) : ?>
										<tr>
											<td class="country">
												<span class="flag-icon fi fi-<?php echo esc_html( strtolower( $location_vote['location'] ) ); ?> fib"></span>
												<span class="country-name"><?php echo wp_kses_post( pollify_get_country_name( $location_vote['location'] ) ); ?></span>
											</td>
											<td class="ip-address"><?php echo $location_vote['ip']; ?></td>
											<td class="count"><?php echo esc_html( $location_vote['votes'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</table>

								<div class="see-more-link">
									<a href="<?php echo add_query_arg( [ 'page' => 'pollify', 'action' => 'view_results', 'tab' => 'ip-details', 'poll_id' => $poll->get_client_id() ], admin_url( 'admin.php' ) ); ?>"><?php esc_html_e( 'See all IP\'s', 'pollify' ); ?> &#8594;</a>
								</div>
							<?php else : ?>
								<p class="no-data-text"><?php esc_html_e( 'No location data found for this poll', 'pollify' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
			<div class="meta-card-column secondary">
				<div class="latest-votes meta-card">
					<div class="heading">
						<h2><?php esc_html_e( 'Recent votes', 'pollify' ); ?></h2>
					</div>

					<div class="meta-card-content recent-votes">
						<?php
						$recent_votes = $poll->get_votes();
						?>
						<?php if ( ! empty( $recent_votes ) ) : ?>
						<ul class="vote-list">
							<?php foreach ( $recent_votes as $recent_vote ) {
								?>
								<li>
									<div class="vote-info">
										<?php
										$user_id = $recent_vote['user_id'] ?? 0;
										if ( $user_id ) {
											$user = get_user_by( 'ID', $user_id );
										}
										?>
										<?php if ( ! empty( $user ) ) : ?>
											<div class="user-name"><?php echo esc_html( $user->display_name ); ?></div>
										<?php else : ?>
											<div class="user-name"><?php esc_html_e( 'Guest', 'pollify' ); ?></div>
										<?php endif; ?>

										<div class="other-details">
											<span class="flag-icon fi fi-<?php echo esc_html( strtolower( $recent_vote['user_location'] ) ); ?> fib"></span>
											<span class="user-ip"><?php echo esc_html( $recent_vote['user_ip'] ); ?></span>
										</div>
									</div>
									<div class="vote-details">
										<span class="option"><?php echo wp_kses_post( $recent_vote['option'] ); ?></span>
										<span class="time"><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $recent_vote['created_at'] ) ); ?></span>
									</div>
								</li>
								<?php
							} ?>

							<li class="see-more-link">
								<a href="<?php echo add_query_arg( [ 'page' => 'pollify', 'action' => 'view_results', 'tab' => 'votes', 'poll_id' => $poll->get_client_id() ], admin_url( 'admin.php' ) ); ?>"><?php esc_html_e( 'See all votes', 'pollify' ); ?> &#8594;</a>
							</li>
						</ul>
						<?php else : ?>
							<p class="no-data-text"><?php esc_html_e( 'No recent votes found for this poll', 'pollify' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php elseif ( 'votes' === $nav_tab ) : ?>
			<div class="votes-table">
				<?php
					$table = new \UnderDev\Pollify\Admin\VotesListTable( $poll );
					// $table->views();

					echo '<form method="post">';

					// Prepare table
					$table->prepare_items();

					// Search form
					$table->search_box( __( 'Search by IP', 'pollify' ), 'pollify_vote_search_id' );

					// Display table
					$table->display();

					echo '</form>';
					?>
			</div>
		<?php elseif ( 'ip-details' === $nav_tab ) : ?>
			<div class="ips-table">
				<?php
					$table = new \UnderDev\Pollify\Admin\IPsListTable( $poll );

					echo '<form method="post">';

					// Prepare table
					$table->prepare_items();

					// Search form
					$table->search_box( __( 'Search by IP', 'pollify' ), 'pollify_ip_search_id' );

					// Display table
					$table->display();

					echo '</form>';
					?>
			</div>
		<?php else : ?>
			<?php
				/**
				 * Load the results navigation content.
				 *
				 * @param string $nav_tab Navigation tab.
				 * @param array  $navigations Navigations.
				 */
				do_action( 'pollify_load_results_nav_content', $nav_tab, $navigations );
			?>
		<?php endif; ?>
	</div>
</div>