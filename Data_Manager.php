<?php 
namespace Data_Manager;

class Data_Manager {
	
	private $feed_data = array();
	private $limit     = 4;
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
	
	public function fetch_user_feed( $limit = '', $feed_url = '' ) {
		if ( ! $this->access_token || ! $this->user_id ) {
			return;
		}

		if ( $limit ) {
			$this->set_limit();
		}
		
		if ( ! $feed_url ) {
			$base   = 'https://api.instagram.com/v1/users/'. $this->user_id .'/media/recent/';
			$params = array(
				'access_token' => $this->access_token,
				'count'        => $this->get_limit(),
			);

			$feed_url = add_query_arg( $params, $base );
		}

		$feed_request = wp_remote_get( $feed_url );
		$this->handle_request( $feed_request, 'fetch_user_feed' );
	}

	public function fetch_hashtag_feed( $hashtag, $limit = '', $feed_url = '' ){
		if ( ! $this->access_token || ! $this->user_id ) {
			return;
		}

		if ( $limit ) {
			$this->set_limit( $limit );
		}
		
		if ( ! $feed_url ) {
			$base   = 'https://api.instagram.com/v1/tags/' . $hashtag . '/media/recent/';
			$params = array(
				'access_token' => $this->access_token,
				'count'        => $this->get_limit(),
			);

			$feed_url = add_query_arg( $params, $base );
		}

		$feed_request = wp_remote_get( $feed_url );
		$this->handle_request( $feed_request, 'fetch_hashtag_feed' );
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

	private function handle_request( $request, $calling_function ) {
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

		$last_element = end( $this->feed_data );
		$last_id      = $last_element->id;

		if ( isset( $response->pagination->next_url ) && ( $response->pagination->next_max_id !== $last_id ) ) {
			if ( $response->pagination->next_url !== '' ) {
				$this->$calling_function( '', $response->pagination->next_url );
			} 
		}
	}
}