<?php 
namespace Instagram_Factory;

use Client;
use Data_Manager;
use Carbon_Helper;

/**
* 
*/
class Instagram_Factory {
	
	private static $classes = array(
		'Client\Client'               => 'client',       
		'Data_Manager\Data_Manager'   => 'data_manager' ,
		'Carbon_Helper\Carbon_Helper' => 'carbon_helper',
	);

	public static function create( $class, $params=array() ){
	
		$instance = array_search( $class, self::$classes );

		if ( ! $instance ) {
			// error handler

		} 

		return new $instance( $params );
	}


}