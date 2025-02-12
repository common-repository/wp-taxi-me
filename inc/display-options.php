<?php


/*

	Function: Enqueue the CSS & scripts associated with the plugins.

*/
	function wptaxime_enqueue_scipts() {
		wp_enqueue_style( 'taxime-css', plugins_url( 'css/taximebutton.css', __FILE__ ), array(), '0.5' );
	}
	add_action( 'wp_enqueue_scripts', 'wptaxime_enqueue_scipts' );










/*

	Function: Builds the button for WP Uber Me

*/
	function wptaxime_build_button( $args = array() ) {

		$options = get_option( 'wptaxime_options' );
		$args = wp_parse_args( $args, $options );

		$name = rawurlencode( $args['business_name'] );
		$address = $args['address_1'] . " " . $args['address_2'] . " " . $args['state'] . " " . $args['zip'];

		// Do a check to make sure that we have set the lat/lng. If not, get it.
		if ( !array_key_exists( 'lat', $args ) || !array_key_exists( 'lng', $args ) || ( array_key_exists( 'get_new_latlng', $args) && $args['get_new_latlng'] ) ) {

			$geoloc = wptaxime_get_latlng_address( $address, $options['access_token'], $options['apitouse'] );

			$args['lat'] = $geoloc['lat'];
			$args['lng'] = $geoloc['lng'];

		}

		$address = rawurlencode( $address );

		$echostring = '';

		if ( !array_key_exists( 'lat', $args ) || !array_key_exists( 'lng', $args ) ) {
			$extralatlngstring = '&dropoff[formatted_address]=' . $address;
		} else {
			$extralatlngstring = "";
		}

		if ( !empty($args['debug']) || ( $args['debug'] ) ) {
			
			$buttontext = apply_filters( 'wptaxime_button_text', __( 'Book A Taxi Here', 'wp-taxi-me' )  );
			$buttonatts = apply_filters( 'wptaxime_button_atts', '', 10, 2);
			$echostring = '<p class="taxibuttonwrapper"><a href="uber://?action=setPickup&pickup=my_location&dropoff[nickname]='. $name . $extralatlngstring . '&dropoff[latitude]='. $args['lat'] .'&dropoff[longitude]='. $args['lng'] .'" class="taximebutton" ' . $buttonatts . '>'. $buttontext . '</a></p>';
			
		} else {

			if ( wp_is_mobile() ) {

				$buttontext = apply_filters( 'wptaxime_button_text', __( 'Book A Taxi Here', 'wp-taxi-me' )  );
				$buttonlink = apply_filters( 'wptaxime_button_link', 'uber://?action=setPickup&pickup=my_location&dropoff[nickname]='. $name .'&dropoff[formatted_address]=' . $address . '&dropoff[latitude]='. $args['lat'] .'&dropoff[longitude]='. $args['lng'] . '&client_id=Su4EYtcPGRZw-KAz7Bvt7Qz8yreTqPRQ' );
				$buttonatts = apply_filters( 'wptaxime_button_atts', '', 10, 2);
				$echostring = '<p class="taxibuttonwrapper"><a href="uber://?action=setPickup&pickup=my_location&dropoff[nickname]='. $name .'&dropoff[formatted_address]=' . $address . '&dropoff[latitude]='. $args['lat'] .'&dropoff[longitude]='. $args['lng'] .'" class="taximebutton" ' . $buttonatts . '>'. $buttontext . '</a></p>';

			}

		}

		if ( $args['registration'] ) {

			if ( "everywhere" == $args['registration'] || ( "mobile" == $args['registration'] && wp_is_mobile() ) || ( "desktop" == $args['registration'] && !wp_is_mobile() ) ) {
				$afflink = apply_filters( 'wptaxime_change_afflink', 'https://m.uber.com/sign-up?client_id=Su4EYtcPGRZw-KAz7Bvt7Qz8yreTqPRQ' );

				$echostring .= '<p class="taxibuttonwrapper"><a href="'.$afflink.'" class="taximebutton">' . __( 'Register for Uber', 'wp-taxi-me' ) . '</a></p>';
			}
		}

		if ( $args['linkback'] ) {

			$echostring .= '<p class="taxibuttonwrapper"><a href="' . WP_TAXI_ME_PLUGIN_URL .'">WP Taxi Me</a> ' . __( 'by', 'wp-taxi-me' ) . ' <a href="https://winwar.co.uk/">Winwar Media</a>';

		}

		$echostring = apply_filters( 'wptaxime_change_whole_link', $echostring );

		return $echostring;

	}






/*

	Function: Get latitude & longitude for an address.

*/
	function wptaxime_get_latlng_address( $address, $accesstoken = '', $apitouse = 'mapbox', $skiptransient = FALSE ) {

		$prepAddr = str_replace( ' ', '+', $address );
		$options = get_option( 'wptaxime_options' );
		$debug = "";
		
		if ( isset ( $options['debug'] ) ) {
			$debug = $options['debug'];
		}

		if ( $accesstoken && 'mapbox' == $apitouse ) {
			$geocode = file_get_contents( 'https://api.mapbox.com/geocoding/v5/mapbox.places/'.$prepAddr.'.json?access_token=' . $accesstoken );
			$output = json_decode( $geocode );

			$returnarray = array(

				'lat' => $output->features[0]->geometry->coordinates[1],
				'lng' => $output->features[0]->geometry->coordinates[0]

			);

			if ( $debug ) {
				echo '<!-- RAW API RETURN, SWITCH DEBUG OFF TO HIDE THIS: ' . print_r( $output, true ) . '-->';
			}

			return $returnarray;

		} elseif ( $accesstoken && 'google' == $apitouse ) {

			$geocode = file_get_contents( 'https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false&key=' . $accesstoken );
			$output = json_decode( $geocode );

			$returnarray = array(

				'lat' => $output->results[0]->geometry->location->lat,
				'lng' => $output->results[0]->geometry->location->lng

			);

			if ( $debug ) {
				echo '<!-- RAW API RETURN, SWITCH DEBUG OFF TO HIDE THIS: ' . print_r( $output, true ) . '-->';
			}

			return $returnarray;
		}

		return array(
			'lat' => '',
			'lng' => ''
		);
	}






/*

	Function: Adds the shortcode "taxi-me", which adds the taxi button to the text.

*/
	function wptaxime_taxi_button_shortcode() {
		return wptaxime_build_button();
	}
	

	/**
	 * Move add shortcode to a function instead of natively, so we can overwrite it should we choose to.
	 * 
	 * @return void
	 */
	function wptaxime_free_init() {
		add_shortcode( 'taxi-me', 'wptaxime_taxi_button_shortcode' );
	} add_action( 'init', 'wptaxime_free_init', 10 );

	?>
