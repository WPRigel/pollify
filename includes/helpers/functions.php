<?php
/**
 * Load common functions.
 *
 * @package pollify
 */

declare(strict_types=1);

if ( ! function_exists( 'pollify_load_template' ) ) {

	/**
	 * Load template from specific directory.
	 *
	 * @param string $template_name Template name.
	 * @param bool   $load_once     Load once or not.
	 * @param array  $args          Arguments to pass.
	 *
	 * @return void
	 */
	function pollify_load_template( $template_name, $load_once = true, $args = [] ): void {
		$template_path = apply_filters(
			'pollify_load_template_path',
			wp_sprintf( '%s/templates/%s', POLLIFY_PATH, $template_name ),
			$template_name,
			$args
		);

		// Checking if file exist or not.
		if ( file_exists( $template_path ) ) {
			// extract args.
			if ( ! empty( $args ) ) {
				/**
				 * This is used becuase of no alternative way to extract variables
				 * for loading the templates.
				 */
				// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
				extract( $args );
			}

			if ( $load_once ) {
				require_once $template_path;
			} else {
				require $template_path;
			}
		}
	}
}


/**
 * This method is an improved version of PHP's filter_input() for
 * sanitizing input data from various sources.
 *
 * @param int    $type          One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
 * @param string $variable_name Name of a variable to get.
 * @param int    $filter        The ID of the filter to apply.
 * @param mixed  $options       filter to apply.
 *
 * @return mixed
 */
function pollify_filter_input( $type, $variable_name, $filter = FILTER_DEFAULT, $options = 0 ) {
	switch ( $filter ) {
		case POLLIFY_FILTER_SANITIZE_STRING:
			$sanitized_variable = sanitize_text_field( filter_input( $type, $variable_name, FILTER_UNSAFE_RAW, $options ) );
			break;
		default:
			$sanitized_variable = filter_input( $type, $variable_name, $filter, $options );
			break;
	}

	return $sanitized_variable;
}

/**
 * Get country code and name array.
 *
 * @param array $country_code Country code.
 *
 * @return string
 */
function pollify_get_country_name( $country_code ) {

	$countries = [
		'AF' => 'Afghanistan',
		'AX' => 'Åland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua And Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BA' => 'Bosnia And Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory',
		'BN' => 'Brunei Darussalam',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo',
		'CD' => 'Congo, Democratic Republic',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'CI' => 'Côte D\'Ivoire',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands (Malvinas)',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard Island & Mcdonald Islands',
		'VA' => 'Holy See (Vatican City State)',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran, Islamic Republic Of',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle Of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KR' => 'Korea',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Lao People\'s Democratic Republic',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libyan Arab Jamahiriya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MK' => 'Macedonia',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia, Federated States Of',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'AN' => 'Netherlands Antilles',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PW' => 'Palau',
		'PS' => 'Palestinian Territory, Occupied',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barthélemy',
		'SH' => 'Saint Helena',
		'KN' => 'Saint Kitts And Nevis',
		'LC' => 'Saint Lucia',
		'MF' => 'Saint Martin',
		'PM' => 'Saint Pierre And Miquelon',
		'VC' => 'Saint Vincent And Grenadines',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'Sao Tome And Principe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia And Sandwich Isl.',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard And Jan Mayen',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syrian Arab Republic',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad And Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks And Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		'UM' => 'United States Outlying Islands',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VE' => 'Venezuela',
		'VN' => 'Viet Nam',
		'VG' => 'Virgin Islands, British',
		'VI' => 'Virgin Islands, U.S.',
		'WF' => 'Wallis And Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
	];

	return $countries[ $country_code ] ?? '';
}

/**
 * Get formatted country votes.
 *
 * @param array $data Data array.
 *
 * @return array
 */
function pollify_get_formatted_country_votes( $data ) {
	$geo_data_array = [];

	if ( ! empty( $data ) ) {
		foreach ( $data as $location_vote ) {
			$geo_data_array[] = [ pollify_get_country_name( $location_vote['location'] ), $location_vote['votes'] ];
		}
	}

	return $geo_data_array;
}

/**
 * Get admin results nav.
 *
 * @param null|object $poll Poll object.
 *
 * @return array
 */
function pollify_poll_results_page_nav( $poll = null ) {

	// check if the poll is null or wp_error. then try to get the poll id from the request.
	if ( is_null( $poll ) || is_wp_error( $poll ) ) {
		// Get the poll ID from the request.
		$poll_id = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );

		if ( empty( $poll_id ) ) {
			return [];
		}
	} else {
		// Get the poll ID from the poll object.
		$poll_id = $poll->get_client_id();
	}

	$nav = [
		'overview'   => [
			'title' => __( 'Overview', 'poll-creator' ),
			'slug'  => 'overview',
			'icon'  => 'dashicons-dashboard',
			'link'  => add_query_arg(
				[
					'page'    => 'pollify',
					'action'  => 'view_results',
					'poll_id' => $poll_id,
				],
				admin_url( 'admin.php' )
			),
		],
		'votes'      => [
			'title' => __( 'Votes', 'poll-creator' ),
			'slug'  => 'votes',
			'icon'  => 'dashicons-thumbs-up',
			'link'  => add_query_arg(
				[
					'page'    => 'pollify',
					'action'  => 'view_results',
					'tab'     => 'votes',
					'poll_id' => $poll_id,
				],
				admin_url( 'admin.php' )
			),
		],
		'ip-details' => [
			'title' => __( 'IP Details', 'poll-creator' ),
			'slug'  => 'ip-details',
			'icon'  => 'dashicons-chart-area',
			'link'  => add_query_arg(
				[
					'page'    => 'pollify',
					'action'  => 'view_results',
					'tab'     => 'ip-details',
					'poll_id' => $poll_id,
				],
				admin_url( 'admin.php' )
			),
		],
	];

	return apply_filters( 'pollify_poll_results_page_nav', $nav, $poll );
}

/**
 * Generate shorthand CSS styles.
 *
 * @param string $type Block shorthand name.
 * @param string $property The CSS property (e.g., 'border', 'padding', 'margin', 'border-radius').
 * @param array  $values The values for the property, either as a flat value or detailed object.
 *
 * @return string The generated shorthand CSS style.
 */
function pollify_generate_shorthand_styles( $type, $property, $values ) {
	if ( isset( $values['top'] ) || isset( $values['right'] ) || isset( $values['bottom'] ) || isset( $values['left'] ) ) {
		// Detailed object: collect values from each side.
		$top_value    = isset( $values['top'] ) ? $values['top'] : '0';
		$right_value  = isset( $values['right'] ) ? $values['right'] : '0';
		$bottom_value = isset( $values['bottom'] ) ? $values['bottom'] : '0';
		$left_value   = isset( $values['left'] ) ? $values['left'] : '0';

		$all_value = "$top_value $right_value $bottom_value $left_value";
		return "--pollify-$type-$property: $all_value;";
	} elseif ( isset( $values['value'] ) ) {
		// Flat object: apply the same value for all sides.
		return "--pollify-$type-$property: {$values['value']};";
	}

	return '';
}

/**
 * Generate shorthand CSS styles for border properties.
 *
 * @param string $type Block shorthand name for defining types of variables.
 * @param array  $border The border properties, either as a flat value or detailed object.
 *
 * @return string The generated shorthand CSS style.
 */
function pollify_generate_shorthand_border_styles( $type, $border ) {
	if ( isset( $border['top'] ) || isset( $border['right'] ) || isset( $border['bottom'] ) || isset( $border['left'] ) ) {
		// Detailed object: collect values from each side.
		$border_color = ( isset( $border['top']['color'] ) ? $border['top']['color'] : 'transparent' ) . ' ' .
						( isset( $border['right']['color'] ) ? $border['right']['color'] : 'transparent' ) . ' ' .
						( isset( $border['bottom']['color'] ) ? $border['bottom']['color'] : 'transparent' ) . ' ' .
						( isset( $border['left']['color'] ) ? $border['left']['color'] : 'transparent' );

		$border_style = ( isset( $border['top']['style'] ) ? $border['top']['style'] : 'none' ) . ' ' .
						( isset( $border['right']['style'] ) ? $border['right']['style'] : 'none' ) . ' ' .
						( isset( $border['bottom']['style'] ) ? $border['bottom']['style'] : 'none' ) . ' ' .
						( isset( $border['left']['style'] ) ? $border['left']['style'] : 'none' );

		$border_width = ( isset( $border['top']['width'] ) ? $border['top']['width'] : '0' ) . ' ' .
						( isset( $border['right']['width'] ) ? $border['right']['width'] : '0' ) . ' ' .
						( isset( $border['bottom']['width'] ) ? $border['bottom']['width'] : '0' ) . ' ' .
						( isset( $border['left']['width'] ) ? $border['left']['width'] : '0' );

		return "--pollify-$type-bordercolor: $border_color; --pollify-$type-borderstyle: $border_style; --pollify-$type-borderwidth: $border_width;";
	} elseif ( isset( $border['color'] ) && isset( $border['style'] ) && isset( $border['width'] ) ) {
		// Flat object: apply the same value for all sides.
		return "--pollify-$type-bordercolor: {$border['color']}; --pollify-$type-borderstyle: {$border['style']}; --pollify-$type-borderwidth: {$border['width']};";
	}

	return '';
}

/**
 * Display IP address with actions.
 *
 * @param string $ip   The IP address to display.
 * @param object $poll The poll object.
 *
 * @return void
 */
function pollify_display_ip_with_actions( $ip, $poll ) {
	$tab = pollify_filter_input( INPUT_GET, 'tab', POLLIFY_FILTER_SANITIZE_STRING ) ?: '';

	$remove_url = wp_nonce_url(
		add_query_arg(
			[
				'action'       => 'pollify_remove_ip',
				'poll_id'      => $poll->get_client_id(),
				'ip_address'   => rawurlencode( $ip ),
				'redirect_url' => rawurlencode(
					add_query_arg(
						[
							'page'    => 'pollify',
							'action'  => 'view_results',
							'poll_id' => $poll->get_client_id(),
							'tab'     => $tab,
						],
						admin_url( 'admin.php' )
					)
				),
			]
		),
		'pollify_remove_ip_' . $ip,
		'_nonce'
	);

	$row_actions = apply_filters(
		'pollify_ip_view_row_actions',
		[
			'remove' => [
				'url'     => $remove_url,
				'class'   => 'pollify-remove-ip',
				'label'   => __( 'Remove', 'poll-creator' ),
				'style'   => 'color: red;margin-left: 8px;',
				'onclick' => sprintf(
					"return confirm('%s');",
					esc_js( __( 'Are you sure you want to remove all votes from this IP? This operation cannot be undone. Just make sure before proceed', 'poll-creator' ) )
				),
			],
		],
		$ip,
		$poll
	);
	?>
	<div class="ip-address-data">
		<span><?php echo esc_html( $ip ); ?></span>
		<div class="ip-actions">
			<?php foreach ( $row_actions as $action ) : ?>
				<a
					href="<?php echo esc_url( $action['url'] ); ?>"
					class="<?php echo esc_attr( $action['class'] ); ?>"
					<?php
					if ( ! empty( $action['style'] ) ) :
						?>
						style="<?php echo esc_attr( $action['style'] ); ?>"<?php endif; ?>
					<?php
					if ( ! empty( $action['onclick'] ) ) :
						?>
						onclick="<?php echo esc_attr( $action['onclick'] ); ?>"<?php endif; ?>
					<?php
					// Add data attributes if provided.
					if ( ! empty( $action['data'] ) && is_array( $action['data'] ) ) :
						foreach ( $action['data'] as $data_key => $data_value ) :
							printf( ' data-%s="%s"', esc_attr( $data_key ), esc_attr( $data_value ) );
						endforeach;
					endif;
					?>
				>
					<?php echo esc_html( $action['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Recursively filter allowed blocks.
 *
 * Supports:
 * - Exact matches: ['pollify/poll']
 * - Wildcard prefix matches using *: ['pollify/*'] (matches any block starting with pollify/)
 *
 * @param array $blocks         The blocks to filter.
 * @param array $allowed_blocks The allowed block names or wildcard prefixes.
 *
 * @return array Filtered blocks.
 */
function pollify_filter_allowed_blocks_recursive( array $blocks, array $allowed_blocks ): array {
	$found = [];

	foreach ( $blocks as $block ) {
		// Ensure blockName is always a string.
		$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

		if ( ! empty( $block_name ) ) {
			foreach ( $allowed_blocks as $allowed ) {
				if ( ! empty( $allowed ) && '*' === substr( $allowed, -1 ) ) {
					// Handle wildcard prefix, e.g., "pollify/*".
					$prefix = substr( $allowed, 0, -1 );

					if ( strpos( $block_name, $prefix ) === 0 ) {
						$found[] = $block;
						break;
					}
				} elseif ( $block_name === $allowed ) {
					// Handle exact match.
					$found[] = $block;
					break;
				}
			}
		}

		// Recursively check inner blocks if they exist.
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$child_blocks = pollify_filter_allowed_blocks_recursive( $block['innerBlocks'], $allowed_blocks );

			if ( ! empty( $child_blocks ) ) {
				array_push( $found, ...$child_blocks );
			}
		}
	}

	return $found;
}
