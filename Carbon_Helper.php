<?php 
namespace Instagram_Helper;
use Carbon_Fields\Container\Container;
use Carbon_Fields\Field\Field;

/**
* 
*/
class Carbon_Helper {

	private $client;

	function __construct( $client ){
		$this->client = $client;
	}
	
	public function create_options_page( $page_parent = '' ){
		if ( ! class_exists( '\Carbon_Fields\Container\Container' ) ){ 
			return;
		}

		Container::make('theme_options', __( 'Instagram Settings', 'crb' ))
			->set_page_parent( $page_parent )
			->add_fields( array(
				Field::make('html', 'crb_instagram_settings_html')
					->set_html('
						<div style="position: relative; background: #fff; border: 1px solid #ccc; padding: 10px;">
							<h4><strong>' . __('Instagram API requires an Instagram client for communication with 3rd party sites. Here are the steps for creating and setting up an Instagram client:', 'crb') . '</strong></h4>
							<ol style="font-weight: normal; margin-left: 25px;">
								<li>' . sprintf(__('Go to <a href="%1$s" target="_blank">%1$s</a> and log in, if necessary.', 'crb'), 'https://instagram.com/developer/clients/register/') . '</li>
								<li>' . __('Supply the necessary required fields. <strong>Valid redirect URIs</strong> field must be filled with the value from the <strong>Redirect URI</strong> field below.', 'crb') . '</li>
								<li>' . __('Click the Register button.', 'crb') . '</li>
								<li>' . __('On the next screen, copy the following fields: <strong>Client ID, Client Secret</strong> to the below fields.', 'crb') . '</li>
								<li>' . __( 'Save the updates using the "Save Changes" button on this page', 'crb' ) . '</li>
								<li>' . __( 'Click the "Authenticate" button and follow the on screen instructions.', 'crb' ) . '</li>
							</ol>
						</div>
					'),
				Field::make('text', $this->client->carbon_config_fields[ 'user_name' ], __( 'Username', 'crb')),
				Field::make('textarea', $this->client->carbon_config_fields[ 'hashtags' ], __( 'Hashtags', 'crb'))
					->set_help_text( __( 'Separate hashtags with a comma.', 'crb' ) ),
				Field::make('text', $this->client->carbon_config_fields[ 'client_id' ], __( 'Client ID', 'crb'))
					->set_width( 50 ),
				Field::make('text', $this->client->carbon_config_fields[ 'client_secret' ], __( 'Client Secret', 'crb'))
					->set_width( 50 ),
				Field::make('text', $this->client->carbon_config_fields[ 'redirect_uri' ], __( 'Redirect URI', 'crb'))
					->set_default_value( $this->client->get_redirect_uri() ),
				Field::make('html', 'crb_instragram_authenticate' )
					->set_html( $this->generate_page_buttons() ),
			));
	}

	private function generate_page_buttons(){
		ob_start();

		$token = carbon_get_theme_option( 'crb_instagram_access_token' );

		if ( $token ) {
			$this->delete_token_button();
			return ob_get_clean();
		} 

		$client_id     = carbon_get_theme_option( $this->client->carbon_config_fields[ 'client_id' ] );
		$client_secret = carbon_get_theme_option( $this->client->carbon_config_fields[ 'client_secret' ] );

		if ( $client_id && $client_secret ) {
			$this->auth_button();
			return ob_get_clean();
		}
	}

	private function auth_button(){
		
		$url         = $this->client->generate_authentication_url();
		$button_text = __( 'Authenticate', 'crb' );

		$this->button_html( $url, $button_text );
	}

	private function delete_token_button() {
		$url         = $this->client->generate_delete_token_url();
		$button_text = __( 'Delete Token', 'crb' );

		echo $this->button_html( $url, $button_text );
	}

	private function button_html( $url, $button_text ) {
		?>
		<a class="button button-primary button-large" href="<?php echo $url ?>"><?php echo $button_text ?></a>
		<?php
	}
}