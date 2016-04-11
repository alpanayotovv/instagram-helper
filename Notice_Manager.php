<?php 
namespace Notice_Manager;

class Notice_Manager {

	private $transient_name = 'insta_admin_notice';
	private $notice_data;
	
	function __construct(){

		$this->notice_data = get_transient( $this->transient_name );

		if ( $this->notice_data ){
			add_action( 'admin_notices', array( $this, 'notice' ) );
			delete_transient( $this->transient_name );
		}

	}

	public function add_notice( $type, $text ){
		$data = array(
			'type' => $type,
			'text' => $text,
		);

		set_transient( $this->transient_name, $data, 60 );
	}

	public function notice(){
		?>
		<div class="notice notice-<?php echo $this->notice_data[ 'type' ] ?>">
			<?php echo wpautop( $this->notice_data[ 'text' ] ); ?>
		</div>
		<?php	
	}
}