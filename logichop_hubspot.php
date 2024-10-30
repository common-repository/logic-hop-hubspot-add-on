<?php

	/*
		Plugin Name: Logic Hop HubSpot Add-on
		Plugin URI:	https://logichop.com/docs/hubspot-add-on
		Description: Enables HubSpot integration for Logic Hop now compatible with HUBSPOT PRIVATE APP SETTINGS, OFFERING MUCH MORE GRANULARITY to your rules.
		Author: Logic Hop
		Version: 1.0.2
		Author URI: https://logichop.com
	*/

	if (!defined('ABSPATH')) die;

	if ( is_admin() ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( ! is_plugin_active( 'logichop/logichop.php' ) && ! is_plugin_active( 'logic-hop/logichop.php' ) ) {
			add_action( 'admin_notices', 'logichop_hubspot_plugin_notice' );
		}

		add_action( 'admin_notices', 'logichop_hubspot_plugin_apikey_notice' );
	}

	function logichop_hubspot_plugin_apikey_notice () {
		$message = sprintf(__('Logic Hop HubSpot plugin is now compatible with <a href="%s" target="_blank">HUBSPOT PRIVATE APP SETTINGS</a>, OFFERING MUCH MORE GRANULARITY to your rules.'
				, 'logichop'),
			'https://developers.hubspot.com/docs/api/migrate-an-api-key-integration-to-a-private-app'
		);

		printf('<div class="notice notice-warning is-dismissible">
						<p>
							%s
						</p>
					</div>',
			$message
		);
	}



	function logichop_hubspot_plugin_notice () {
		$message = sprintf(__('HubSpot for Logic Hop requires the Logic Hop plugin. Please download and activate the <a href="%s" target="_blank">Logic Hop plugin</a>.'

			, 'logichop'),'http://wordpress.org/plugins/logic-hop/'
						);

		printf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
					$message
				);
	}

	/**
	 * Plugin activation/deactviation routine to clear Logic Hop transients
	 *
	 * @since    1.0.0
	 */
	function logichop_hubspot_activation () {
		delete_transient( 'logichop' );
  }
	register_activation_hook( __FILE__, 'logichop_hubspot_activation' );
	register_deactivation_hook( __FILE__, 'logichop_hubspot_activation' );

	if ( ! class_exists( 'LogicHop_HubSpot' ) ) {
		 require_once( 'includes/hubspot.php' );
    }

	new LogicHop_HubSpot( plugin_basename( __FILE__ ), 'https://api.hubapi.com');