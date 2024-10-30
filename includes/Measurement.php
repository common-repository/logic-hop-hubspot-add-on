<?php

if (!defined('ABSPATH')) die;

/**
 * Setup
 *
 * @since      1.0.0
 * @package    LogicHop
 */

class LHHS_Measurement {


	private static $instances = [];

	protected function __construct() { }
	protected function __clone() { }
	public function __wakeup()
	{
		throw new \Exception("Cannot unserialize a singleton.");
	}

	public static function getInstance(): LHHS_Measurement
	{
		$cls = static::class;
		if (!isset(self::$instances[$cls])) {
			self::$instances[$cls] = new static();
		}
		return self::$instances[$cls];
	}
	public function measure ( $category, $action ) {
		if ( is_admin() ) {
			$payload        = array();
			$options = get_option( LOGICHOP_SETTINGS );
			$api_key ="na";
			if ( isset( $options['api_key'] ) ) {
				$api_key= $options['api_key'];
			}
			$instanceId=get_option(LOGICHOP_INSTANCE,"na");
			$server =isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
			$url ="https://plugin.logichop.com/measure?a=".$action ."&lk=".$api_key ."&cat=" .$category . "&cid=". $instanceId ."&ver=1.0.2&domain=".$server ;

			$post_args = array (
				'timeout' => 2,
				'headers' => array ( 'Content-Type'=>'application/json' ),
				'body' => json_encode($payload)
			);
			$response = wp_remote_post( $url ,$post_args );
		}
	}
}
