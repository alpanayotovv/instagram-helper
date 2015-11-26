<?php 
namespace Client;

/**
* 
*/
class Client { 
	
	private $config = array(
		'user_name'     => '',
		'client_id'     => '',
		'client_secret' => '',
	);

	public $carbon_config_fields = array(
		'user_name'     => 'crb_instagram_username',
		'client_id'     => 'crb_instagram_client_id',
		'client_secret' => 'crb_instagram_client_secret',
		'redirect_uri'  => 'crb_instagram_redirect_uri',
	);

	function __construct( $config_fields ){

		if ( isset( $config_fields[ 'enable_carbon_support' ] ) && $config_fields[ 'enable_carbon_support' ] === true ) {
			$this->config = $this->carbon_config_fields;

			foreach ( $this->config as &$option ) {
				$option = get_option( $option );
			}

		} else {
			$this->config = $config_fields;
			$this->config[ 'redirect_uri' ] = $this->get_redirect_uri();
		}

		if ( empty( $this->config ) ) {
			return;
		}

		$this->get_user_id();
		
		add_action( 'wp_ajax_insta_code_detection', array( $this, 'get_access_token' ) );
	}

	public function get_redirect_uri(){
		$url = admin_url('/admin-ajax.php?action=insta_code_detection');
		return $url;
	}

	public function generate_authentication_url(){
		$base   = 'https://api.instagram.com/oauth/authorize/';
		$params = array(
			'client_id'     => $this->config[ 'client_id' ],
			'redirect_uri'  => $this->config[ 'redirect_uri' ],
			'response_type' => 'code',
		);

		$url = add_query_arg( $params, $base ); 
		
		return $url;
	}

	public function get_access_token() {
		if ( !isset( $_GET[ 'code' ] ) ) {
			return;
		}

		$code = $_GET[ 'code' ];
		$url = ' https://api.instagram.com/oauth/access_token';
		
		$params = array(
			'client_id'     => $this->config[ 'client_id' ],
			'client_secret' => $this->config[ 'client_secret' ],
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => $this->config[ 'redirect_uri' ],
		);

		$token_request = wp_remote_post( $url, array( 'body' => $params ) );

		if ( is_wp_error( $token_request ) || ( $token_request[ 'response' ][ 'code' ] !== 200 ) )  { 
			return;
		}

		$request_body = json_decode( $token_request[ 'body' ] );
		
		if ( isset ( $request_body->access_token ) ) {
			update_option( 'crb_instagram_access_token', $request_body->access_token );
			_e('Authentication completed!', 'crb');
		}
		exit;
	}

	private function get_user_id(){
		delete_option( 'crb_instagram_user_id' );
		
		$base   = 'https://api.instagram.com/v1/users/search';
		$params = array(
			'q'         => $this->config[ 'user_name' ],
			'client_id' => $this->config[ 'client_id' ],
		);

		$url = add_query_arg( $params, $base );
		
		$user_id_request = wp_remote_get( $url );
		
		if ( is_wp_error( $user_id_request ) || ( $user_id_request[ 'response' ][ 'code' ] !== 200 ) )  { 
			return;
		}
		
		$response_data = json_decode( $user_id_request[ 'body' ] );
		
		foreach ( $response_data->data as $entry ) {
			if ( $entry->username !== $this->config[ 'user_name' ] ) {
				continue;
			}
		
			update_option( 'crb_instagram_user_id', $entry->id );
			break;
		}
	}
}