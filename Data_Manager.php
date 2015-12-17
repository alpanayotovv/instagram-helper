<?php 
namespace Data_Manager;

class Data_Manager {
	
	private $feed_data          = array();
	private $request_data       = array();
	private $limit              = 4;
	private $constructor_limit  = '';
	private $transient_lifetime = 300;
	private $access_token;
	private $user_id;

	function __construct( $user_config = array() ){
		$this->access_token = get_option( 'crb_instagram_access_token' );
		$this->user_id      = get_option( 'crb_instagram_user_id' );
		
		if ( ! $this->access_token || ! $this->user_id ) {
			return;
		}

		if ( isset( $user_config[ 'limit' ] ) ) {
			$this->set_limit( $user_config[ 'limit' ] );
			$this->constructor_limit = $user_config[ 'limit' ];
		}
	}
	
	public function fetch_user_feed( $call_params = array() ) {

		$parameters = array(
			'initial_url'      => $this->build_user_request_url( $call_params[ 'limit' ] ),
			'request_url'      => isset( $call_params[ 'request_url' ] ) ? $call_params[ 'request_url' ] : '',
			'request_function' => 'fetch_user_feed',
		);

		$this->prepare_request( $parameters );
	}

	public function fetch_hashtag_feed( $call_params = array() ){

		$parameters = array(
			'initial_url'      => $this->build_hashtag_request_url( $call_params[ 'hashtag' ], $call_params[ 'limit' ] ),
			'request_url'      => isset( $call_params[ 'request_url' ] ) ? $call_params[ 'request_url' ] : '',
			'request_function' => 'fetch_hashtag_feed',
		);

		$this->prepare_request( $parameters );
	}

	public function get_feed_data(){
		return $this->feed_data;
	}

	public function clear_feed_data(){
		$this->feed_data = array();
	}

	public function flush_cache(){
		global $wpdb;
		
		$this->clear_feed_data();

		$transients = $wpdb->get_results( 
			"SELECT option_name AS name
			FROM $wpdb->options
			WHERE option_name 
			LIKE '%instagram::%'"
		);

		if ( empty( $transients ) ) {
			return;
		}

		foreach ( $transients as $transient ) {
			delete_option( $transient->name );
		}
	}

	public function get_transient_lifetime(){
		return $this->transient_lifetime;
	}

	public function set_transient_lifetime( $lifetime ){
		if ( ! is_int( $lifetime ) ) {
			return;
		}

		$this->transient_lifetime = $lifetime;
	}

	private function prepare_request( $parameters ){
		if ( ! $this->access_token || ! $this->user_id ) {
			return;
		}

		$cache = false;

		if ( ! $parameters[ 'request_url' ] ) {
			$parameters[ 'request_url' ] = $parameters[ 'initial_url' ];
			$cache                       = $this->check_for_cache( $parameters[ 'request_url' ] );
		}

		if ( ! $cache ) {
			$this->execute_request( $parameters[ 'request_url' ], $parameters[ 'request_function' ] );
		} else {
			$this->feed_data = $cache;
		}
	}

	private function build_user_request_url( $limit = '' ){ 
		if ( $limit ) {
			$this->set_limit( $limit );
		}

		$base   = 'https://api.instagram.com/v1/users/'. $this->user_id .'/media/recent/';
		$params = array(
			'access_token' => $this->access_token,
			'count'        => $this->get_limit(),
		);

		return add_query_arg( $params, $base );
	}

	private function build_hashtag_request_url( $hashtag, $limit = '' ){
		if ( $limit ) {
			$this->set_limit( $limit );
		}

		$base   = 'https://api.instagram.com/v1/tags/' . $hashtag . '/media/recent/';
		$params = array(
			'access_token' => $this->access_token,
			'count'        => $this->get_limit(),
		);

		return add_query_arg( $params, $base );
	}

	private function execute_request( $request_url, $calling_function ) {

		$request = wp_remote_get( $request_url );
		if ( is_wp_error( $request ) || ( $request[ 'response' ][ 'code' ] !== 200 ) )  { 
			return;
		}

		$response = json_decode( $request[ 'body' ] );

		if ( isset( $response->error_type ) ) {
			if ( $response->error_type === 'OAuthAccessTokenError' ) {
				delete_option( 'crb_instagram_access_token' );
				return;
			}
		}
		
		$this->request_data = array_merge( $this->request_data, $response->data );
		$request_data_count = count( $this->request_data );
		$transient_key      = 'instagram::' . md5( $request_url );

		if ( isset( $response->pagination->next_url ) && ( $request_data_count < $this->get_limit() ) ) {
			$call_params = array(
				'limit'       => '',
				'hashtag'     => '',
				'request_url' => $response->pagination->next_url,
			);

			$this->$calling_function( $call_params );
		} else {

			if ( $request_data_count > $this->get_limit() ) {
				$chunks             = array_chunk( $this->request_data, $this->get_limit() );
				$this->request_data = $chunks[ 0 ];
			}

			$this->set_feed_data( $this->request_data );
			$this->set_cache( $transient_key );
			$this->request_data = array();
			$this->set_limit( $this->constructor_limit );
		}

	}

	private function set_feed_data( $data ) {
		$this->feed_data = array_merge( $data, $this->feed_data );
	}

	private function set_cache( $transient_key ) {
		set_transient( $transient_key, $this->feed_data, $this->get_transient_lifetime() );
	}

	private function check_for_cache( $request_url ){
		$cache_key = 'instagram::' . md5( $request_url );

		$cached = get_transient( $cache_key );
		
		if ( $cached ) {
			return $cached;
		} else {
			return false;
		}
	}

	private function set_limit( $limit ) {
		if ( ! is_int( $limit ) ) {
			return;
		}

		$this->limit = $limit;
	}

	private function get_limit() {
		return $this->limit;
	}
}