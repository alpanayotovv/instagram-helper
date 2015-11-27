<?php 
namespace Instagram_Factory;

use Client;
use Data_Manager;
use Carbon_Helper;
use Posts_Store;

/**
* 
*/
class Instagram_Factory {
	
	private static $classes = array(
		'Client\Client'               => 'client',       
		'Data_Manager\Data_Manager'   => 'data_manager' ,
		'Carbon_Helper\Carbon_Helper' => 'carbon_helper',
		'Posts_Store\Posts_Store'     => 'posts_store',
	);

	public static function create( $class, $user_conig = array() ){
	
		$instance = array_search( $class, self::$classes );

		if ( ! $instance ) {
			return new \WP_Error( 'invalid_class_abbreviation', __( 'Please provide a valid class abbreviation for the factory. Possible values are "client", "data_manager", "carbon_helper", "post_store" ', 'crb' ));
		} 

		return new $instance( $user_conig );
	}
}