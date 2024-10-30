<?php

if (!defined('ABSPATH')) die;

/**
 * HubSpot
 *
 * Provides HubSpot functionality.
 *
 * @since      1.0.0
 * @package    LogicHop
 * @subpackage LogicHop/includes/services
 */
class LogicHop_HubSpot {

	/**
	 * Plugin basename
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $basename    Plugin basename
	 */
	private $basename;

	/**
	 * API URL
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $api    API URL
	 */
	private $api;

	/**
	 * Private Token
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $api    API Key
	 */
	private $privatetoken_app;

	/**
	 * API Key
	 *
	 * @since    1.1.0
	 * @access   private
	 * @var      string    $api    API Key
	 */
	private $api_key;

	/**
	 * Logic Hop
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $logichop    Logic Hop class
	 */
	private $logichop;

	/**
	 * Logic Hop
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $logic    Logic Hop Core class
	 */
	private $logic;

	/**
	 * Logic Hop Public class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $public    Logic Hop Public class
	 */
	private $public = null;

	/**
	 * Logic Hop Admin class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $admin    Logic Hop Admin class
	 */
	private $admin = null;

	/**
	 * HubSpot user token
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $utk    HubSpot user token
	 */
	public $utk = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    	1.1.0
	 */
	public function __construct( $plugin_name, $api ) {
		$this->basename = $plugin_name;
		$this->api = $api;

		$this->add_hooks_filters();
	}

	/**
	 * Add actions
	 *
	 * @since    	1.0.0
	 */
	public function add_hooks_filters () {
		add_action( 'logichop_after_plugin_init', array( $this, 'logichop_plugin_init' ) );
		add_action( 'logichop_after_admin_hooks', array( $this, 'logichop_admin' ), 10, 1 );
		add_action( 'logichop_after_public_hooks', array( $this, 'logichop_public' ), 10, 1 );
		add_action( 'logichop_integration_init', array( $this, 'integration_init' ) );

		add_filter( 'logichop_data_object_create', array( $this, 'create_data_object' ) );
		add_filter( 'logichop_client_meta_integrations', array( $this, 'client_meta_data' ) );
		add_filter( 'logichop_condition_default_get', array( $this, 'default_conditions' ) );
		add_filter( 'logichop_editor_shortcode_variables', array( $this, 'editor_variables' ) );

		add_filter( 'logichop_settings_register', array( $this, 'register_settings' ) );
		add_filter( 'logichop_settings_validate', array( $this, 'validate_settings' ), 10, 3);

		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'plugin_action_links' ) );

		add_action( 'logichop_initialize_core_data_check', array( $this, 'logichop_data_check' ) );
	}

	/**
	 * Logic Hop plugin init complete
	 *
	 * @since    	1.0.0
	 */
	public function logichop_plugin_init ( $logichop ) {
		$this->logichop = $logichop;
	}

	/**
	 * Initialize functionality
	 *
	 * @since    1.0.0
	 */
	public function integration_init ( $logic ) {
		$this->logic = $logic;
		$this->api_key = $this->logichop->logic->get_option( 'hubspot_api_key' );
		$this->privatetoken_app = $this->logichop->logic->get_option( 'hubspot_private_token' );

		$utk = $this->logic->data_factory->get_value( 'HubSpotUTK' );
		if ( ! is_null( $utk ) ) {
			$this->utk = $utk;
		}
	}

	/**
	 * Logic Hop Public init complete
	 *
	 * @since    	1.0.0
	 */
	public function logichop_admin ( $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Logic Hop Public init complete
	 *
	 * @since    	1.0.0
	 */
	public function logichop_public ( $public ) {
		$this->public = $public;
	}

	/**
	 * Register admin notices
	 *
	 * @since    1.0.0
	 */
	public function admin_notice () {
		$message = '';
		if ( ! $this->logichop->logic->addon_active('hubspot') ) {
			$message = sprintf(__('HubSpot for Logic Hop requires a <a href="%s" target="_blank">Logic Hop Business Plan</a> or higher.', 'logichop'),
							'https://logichop.com/get-started/?ref=addon-hubspot'
						);
		}

		if ( $message ) {
			printf('<div class="notice notice-warning is-dismissible">
						<p>
							%s
						</p>
					</div>',
					$message
				);
		}
	}

	/**
	 * Plugin page links
	 *
	 * @since    1.0.0
	 * @param    array		$links			Plugin links
	 * @return   array  	$new_links 		Plugin links
	 */
	public function plugin_action_links ( $links ) {
		$new_links = array();
        $new_links['settings'] = sprintf( '<a href="%s" target="_blank">%s</a>',
																			'https://logichop.com/docs/hubspot-add-on',
																			'Instructions'
																	);
 		$new_links['deactivate'] = $links['deactivate'];
 		return $new_links;
	}

	/**
	 * Add settings
	 *
	 * @since    1.0.0
	 * @param    array		$settings	Settings parameters
	 * @return   array    	$settings	Settings parameters
	 */
	public function register_settings ( $settings ) {

		$settings['hubspot_api_key'] = array (
								'name' 	=> __('HubSpot API Key', 'logichop'),
								'meta' 	=> __('Enables HubSpot integration. (Deprectead on 30 november 2022) <a href="https://developers.hubspot.com/changelog/upcoming-api-key-sunset" target="_blank">Learn More</a>.', 'logichop'),
								'type' 	=> 'text',
								'label' => '',
								'opts'  => null
							);

		$settings['hubspot_private_token'] = array (
			'name' 	=> __('HubSpot Private App Token Key', 'logichop'),
			'meta' 	=> __('Enables Private Apps Token HubSpot integration. <a href="https://developers.hubspot.com/docs/api/migrate-an-api-key-integration-to-a-private-app" target="_blank">Learn More</a>.', 'logichop'),
			'type' 	=> 'text',
			'label' => '',
			'opts'  => null
		);
		return $settings;
	}

	/**
	 * Validate settings
	 *
	 * @since    1.0.0
	 * @return   string    	$validation		Validation array
	 * @param    string		$key		Settings key
	 * @param    string		$input		Settings array
	 * @return   string    	$validation		Validation array
	 */
	public function validate_settings ( $validation, $key, $input ) {

		if ( $key == 'hubspot_api_key' ) {
			$api_key = $input[$key];
			if ( $this->validate( $key, $api_key ) === false ) {
				$validation->error = true;
				$validation->error_msg = '<li>Invalid HubSpot API Key</li>';
			}
		}
		if ( $key == 'hubspot_private_token' ) {

			$api_key = $input[$key];
			if ( $this->validate( $key, $api_key ) === false ) {
				$validation->error = true;
				$validation->error_msg = '<li>Invalid private Token</li>';
			}
		}

		return $validation;
	}

	/**
	 * Validate API Key
	 *
	 * @since    1.0.0
	 * @return   string    	$api_key		API Key
	 * @return   boolean    	Valid API Key
	 */
	public function validate ( $key, $api_key ) {
		if ( !$api_key ) {
			return false;
		}
		if ( ! class_exists( 'LogicHop\LHHS_Measurement' ) ) {
			$dir= dirname( __FILE__ ) . '/Measurement.php';
			require_once($dir);
		}
		LHHS_Measurement::getInstance()->measure( "logihop_hubspot", sprintf("%s",$key));
		if ($key =='hubspot_private_token') {
			return $this->validate_token($api_key);
		}else {
			$url      = sprintf( '%s/integrations/v1/limit/daily?hapikey=%s', $this->api, $api_key );
			$args     = array();
			$response = wp_remote_get( $url, $args );

			if ( ! is_wp_error( $response ) ) {
				if ( isset( $response['body'] ) ) {
					$json = json_decode( $response['body'], false );
					if ( is_array( $json ) ) {
						$data = $json[0];
					}
					if ( isset( $data->name ) && $data->name == 'api-calls-daily' ) {
						$this->api_key = $api_key;

						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if API is active
	 *
	 * @since    	1.0.0
	 * @return      boolean     Active state
	 */
	public function active () {
		if ( $this->privatetoken_app || $this->api_key )  {
			return true;
		}
		return false;
	}

	/**
	 * Create default data obect
	 *
	 * @since    1.0.0
	 */
	public function create_data_object ( $data = null ) {
		if ( is_null( $data ) ) {
			$data = new stdclass;
		}
		$data->HubSpot = new stdclass();
		$data->HubSpotForms = array();
		$data->HubSpotUTK = '';
		return $data;
	}

	/**
	 * Generate default conditions
	 *
	 * @since    1.0.0
	 * @param    array		$conditions		Array of default conditions
	 * @return   array    	$conditions		Array of default conditions
	 */
	public function default_conditions ( $conditions ) {

		if ( ! $this->active() ) {
			return $conditions;
		}

		$conditions['hubspot'] = array (
				'title' => "HubSpot Data Is Available for User",
				'rule'	=> '{"==": [ {"var": "HubSpot.data" }, true ] }',
				'info'	=> "Is HubSpot data available for the current user."
			);

		return $conditions;
	}

	/**
	 * Add variables to editor
	 *
	 * @since    1.0.0
	 * @return   string    	Variables as datalist options
	 */
	public function editor_variables ( $datalist ) {

		return $datalist . $this->get_variables();
	}

	/**
	 * Get variables as options for tool palette
	 *
	 * @since    	1.0.0
	 * @return      string		Pptions
	 */
	public function get_variables () {
		$options = '';
		if ($data = $this->variables_data()) {
			foreach ($data as $k => $v) {
				$options .= sprintf('<option value="%s">%s</option>', $k, $v);
			}
		}
		return $options;
	}

	/**
	 * Get variables as array of options
	 *
	 * @since    	1.0.0
	 * @return      array		Variables
	 */
	public function variables_data ( $invert = false ) {
		$vars = array (
			'HubSpot.#var#' => 'HubSpot Variable'
		);

		if ($invert) {
			$inverted = array();
			foreach ($vars as $k => $v) $inverted[$v] = $k;
			return $inverted;
		}

		return $vars;
	}

	/**
	 * Generate client meta data
	 *
	 * @since    1.0.0
	 * @param    array		$integrations	Integration names
	 * @return   array    	$integrations	Integration names
	 */
	public function client_meta_data ( $integrations ) {
		$integrations[] = 'hubspot';
		return $integrations;
	}

	/**
	 * Check for user data
	 *
	 * @since    1.0.0
	 */
	public function logichop_data_check () {
		$bypass = false;

		if ( isset( $this->logichop ) && $this->active() ) {
			$utk = $this->logic->data_factory->get_value( 'HubSpotUTK' );
			$hubspotutk = ( ! is_null( $utk ) ) ? $utk : false;
			$force_load = ( $hubspotutk && isset( $_REQUEST['hubspot'] ) ) ? true : false;
			if ( isset( $_COOKIE['hubspotutk'] ) && ! $hubspotutk || $force_load ) {
				if ( ! $hubspotutk ) {
					$this->utk = $_COOKIE['hubspotutk'];
				}else {
					$this->utk =$hubspotutk;
				}
				$this->data_retrieve();
			}
		}

		return $bypass;
	}

	private  function get_default_headers() {
		return array( 'Content-Type' => 'application/json' );
	}

	/**
	 * Get the headers needed to authorise HTTP requests with an access token.
	 *
	 * @return array An associative array of HTTP headers for OAuth authorisation.
	 */
	private  function get_oauth_headers() {
		if ($this->privatetoken_app <> "") {
			return array( 'Authorization' => 'Bearer ' . $this->privatetoken_app );
		}else {
			return array();
		}
	}
	/**
	 * Retrieve Data
	 *
	 * @since    	1.0.0
	 * @return      boolean     If variables have been set
	 */
	public function data_retrieve () {
		if ( ! $this->active() ) {
			return false;
		}
		if ( $this->utk) {
			$endpoint =sprintf("%s/contacts/v1/contact/utk/%s/profile?propertyMode=value_only",$this->api, $this->utk);
			if ($this->api_key <> "") {
				$endpoint = $endpoint . "&hapikey=$this->api_key";
			}
			$headers = array_merge(
				self::get_default_headers(),
				$this->get_oauth_headers()
			);

            $args =array('headers' => $headers,);
			$response = wp_remote_get( $endpoint, $args );

			if ( !is_wp_error( $response ) ) {

				if ( isset( $response['response'] ) ){
					$resp =$response['response'] ;
					 if ( isset( $resp['code'] ) ) {
						 $code =  $resp['code'];
						 if ($code  <> 200) {
							 return false;
						 }
					 }
				}

				if ( isset( $response['body'] ) ) {
					$data = json_decode( $response['body'], false );

					if ( isset( $data->vid ) ) {

						$this->logic->data_factory->set_value( 'HubSpotUTK', $this->utk );
						$hubspot = false;
						if ( isset( $data->properties ) ) {
							$hubspot = new stdclass;
							foreach ( $data->properties as $key => $prop ) {

								if ( strpos( $key, 'hs_analytics' ) === false ) {
									if ( isset( $prop->value ) ) {
										$hubspot->{$key} = $prop->value;
									}
								}
							}

							$company = false;
							if ( isset( $data->{'associated-company'} ) ) {
								if ( isset( $data->{'associated-company'}->properties ) ) {
									$company = new stdclass;
									foreach ( $data->{'associated-company'}->properties as $key => $prop ) {
										if ( isset( $prop->value ) ) {
											$company->{$key} = $prop->value;
										}
									}
								}
							}
							$hubspot->company = $company;
							$this->logic->data_factory->set_value( 'HubSpot', $hubspot, false );
							$forms = array();
							if ( isset( $data->{'form-submissions'} ) ) {
								foreach ( $data->{'form-submissions'} as $form ) {
									if ( isset( $form->title ) ) {
										$forms[] = $form->title;
									}
								}
							}
							$this->logic->data_factory->set_value( 'HubSpotForms', $forms, false );
							$this->logic->data_factory->transient_save();
							return true;
						}
					}
				}
			}
			return false;
		}
	}


	public function validate_token ($token) {
		$endpoint = sprintf( "%s/contacts/v1/contact/utk/%s/profile?propertyMode=value_only", $this->api, "XX" );
		$headers  = array_merge(
			self::get_default_headers(),
			array( 'Authorization' => 'Bearer ' . $token )
		);
		$args     = array( 'headers' => $headers, );
		$response = wp_remote_get( $endpoint, $args );
		if ( ! is_wp_error( $response ) ) {
			if ( isset( $response['response'] ) ) {
				$resp = $response['response'];
				if ( isset( $resp['code'] ) ) {
					$code = $resp['code'];
					if ( $code == 401 ) {
						return false;
					}
				}
			}
		}
		return true;
	}
}
