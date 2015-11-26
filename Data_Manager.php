<?php 
namespace Data_Manager;

use Transient_Store;

class Data_Manager {
	
	private $feed_data          = array();
	private $limit              = 4;
	private $transient_lifetime = 300;
	private $access_token;
	private $user_id;
	
	function __construct( $params=array() ){
		$this->access_token = get_option( 'crb_instagram_access_token' );
		$this->user_id      = get_option( 'crb_instagram_user_id' );
		
		if ( ! $this->access_token || ! $this->user_id ) {
			return;
		}

		if ( isset( $params[ 'limit' ] ) ) {
			$this->set_limit( $params[ 'limit' ] );
		}
	}
	
	public function fetch_user_feed( $limit = '', $use_transient = true, $request_url = '' ) {
		if ( ! $this->access_token || ! $this->user_id ) {
			return;
		}

		$cache = false;

		if ( $limit ) {
			$this->set_limit();
		}
		
		if ( ! $request_url ) {
			
			$request_url = $this->build_user_request_url();
			$cache = $this->check_for_cache( $request_url );
		}

		if ( ! $cache ) {
			$this->handle_request( $request_url, 'fetch_user_feed' );
		} else {
			$this->feed_data = $cache;
		}
	}

	public function fetch_hashtag_feed( $hashtag, $limit = '', $use_transient = true, $request_url = '' ){
		if ( ! $this->access_token || ! $this->user_id ) {
			return;
		}

		$cache = false;

		if ( $limit ) {
			$this->set_limit( $limit );
		}
		
		if ( ! $request_url ) {
			$request_url = $this->build_hashtag_request_url();
			$cache       = $this->check_for_cache( $request_url );
		}

		if ( ! $cache ) {
			$this->handle_request( $request_url, 'fetch_hashtag_feed' );
		} else {
			$this->feed_data = $cache;
		}
	}

	public function set_limit( $limit ) {
		if ( ! is_int( $limit ) ) {
			return;
		}

		$this->limit = $limit;
	}

	public function get_limit() {
		return $this->limit;
	}

	public function get_feed_data(){
		return $this->feed_data;
	}

	public function clear_feed_data(){
		$this->feed_data = array();
	}

	public function flush_cache(){
		$this->clear_feed_data();

		$user_cache    = 'instagram::' . md5( $this->build_user_request_url() );
		$hashtag_cache = 'instagram::' . md5( $this->build_hashtag_request_url() );

		delete_transient( $user_cache );
		delete_transient( $hashtag_cache );
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

	private function build_user_request_url(){ 
		$base   = 'https://api.instagram.com/v1/users/'. $this->user_id .'/media/recent/';
		$params = array(
			'access_token' => $this->access_token,
			'count'        => $this->get_limit(),
		);

		return add_query_arg( $params, $base );
	}

	private function build_hashtag_request_url(){
		$base   = 'https://api.instagram.com/v1/tags/' . $hashtag . '/media/recent/';
		$params = array(
			'access_token' => $this->access_token,
			'count'        => $this->get_limit(),
		);

		return add_query_arg( $params, $base );
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

	private function handle_request( $request_url, $calling_function ) {
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
		
		$this->feed_data = array_merge( $this->feed_data, $response->data );
		$transient_key = 'instagram::' . md5( $request_url );

		set_transient( $transient_key, $this->feed_data, $this->get_transient_lifetime() );

		$last_element = end( $this->feed_data );
		$last_id      = $last_element->id;

		if ( isset( $response->pagination->next_url ) && ( $response->pagination->next_max_id !== $last_id ) ) {
			if ( $response->pagination->next_url !== '' ) {
				$this->$calling_function( '', $response->pagination->next_url );
			} 
		}
	}
}