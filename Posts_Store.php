<?php 
namespace Instagram_Helper;

/**
* 
*/
class Posts_Store {

	public static $post_type_name = 'crb_instagram_entry';
	
	private $config = array(
		'update_count'  => 2,
	);
	
	function __construct( $user_config = array() ) {
		if ( !empty( $user_config ) ) {
			$this->config = $user_config;
		}

	}

	public function save( $data ){

		if ( empty( $data ) ) {
			return;
		}

		foreach ( $data as $index => $entry ) {
			$instagram_id = $entry->id;

			$post_meta = array(
				'_crb_instagram_entry_id'         => $instagram_id,
				'_crb_instagram_entry_type'       => $entry->type,
				'_crb_instagram_entry_created_at' => $entry->created_time,
				'_crb_instagram_link'             => $entry->link,
				'_crb_instagram_likes'            => $entry->likes->count,
				'_crb_instagram_image_url'        => $entry->images->standard_resolution->url,
				'_crb_instagram_comments_count'   => $entry->comments->count,
				'_crb_instagram_user_name'        => $entry->user->username,
				'_crb_instagram_filters'          => $entry->filter,
				'_crb_instagram_entry_hashtags'	  => implode( ' ', $entry->tags ),
			);

			$post_for_update = get_posts( array(
				'posts_per_page' => 1,
				'post_type'      => self::$post_type_name,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_crb_instagram_entry_id',
						'value'   => $instagram_id,
						'compare' => '=',
					)
				)
			));

			if ( empty( $post_for_update )  ) {
				$post_title   = 'Instagram Entry ' . $instagram_id;
				$post_content = !empty( $entry->caption->text ) ? $entry->caption->text : '';
				
				$post_id = wp_insert_post( array(
					'post_type'    => self::$post_type_name,
					'post_title'   => $post_title,
					'post_content' => self::add_links( $post_content ),
					'post_status'  => 'publish',
				));

			} elseif ( is_int( $this->config[ 'update_count' ] ) && ( $index < intval( $this->config[ 'update_count' ] ) ) ){
				$post_id = $post_for_update[ 0 ];
			}

			foreach ( $post_meta as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	public static function register_post_type(){

		$labels = array(
			'name'               => __('Instagram Entries', 'crb'),
			'singular_name'      => __('Instagram Entry', 'crb'),
			'add_new'            => __('Add New', 'crb'),
			'add_new_item'       => __('Add new Instagram Entry', 'crb'),
			'view_item'          => __('View Instagram Entry', 'crb'),
			'edit_item'          => __('Edit Instagram Entry', 'crb'),
			'new_item'           => __('New Instagram Entry', 'crb'),
			'view_item'          => __('View Instagram Entry', 'crb'),
			'search_items'       => __('Search Instagram Entries', 'crb'),
			'not_found'          =>  __('No Instagram Entries found', 'crb'),
			'not_found_in_trash' => __('No Instagram Entries found in trash', 'crb'),
		);

		$post_type_settings = array(
			'public'              => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'_edit_link'          => 'post.php?post=%d',
			'rewrite'             => false,
			'query_var'           => true,
			'menu_icon'           => 'dashicons-images-alt',
			'supports'            => array( 'title', 'editor' )
		);

		
		self::$post_type_name = apply_filters( 'crb_entry_post_type_name', self::$post_type_name );
		$labels               = apply_filters( 'crb_instagram_entry_labels', $labels );
		$post_type_settings   = apply_filters( 'crb_instagram_entry_settings', $post_type_settings );

		register_post_type( self::$post_type_name, array_merge( array( 'labels' => $labels ), $post_type_settings ));
	}

	public static function add_links( $text ) {
		$text = str_replace(array(/*':', '/', */'%'), array(/*'<wbr></wbr>:', '<wbr></wbr>/', */'<wbr></wbr>%'), $text);
		$text = preg_replace('~(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)~', '<a href="$1" target="_blank">$1</a>', $text);
		$text = preg_replace('~[\s]+@([a-zA-Z0-9_]+)~', ' <a href="https://instagram.com/$1" rel="nofollow" target="_blank">@$1</a>', $text);
		$text = preg_replace('~#([a-zA-Z0-9]+)~', ' <a href="https://www.instagram.com/explore/tags/$1" rel="nofollow" target="_blank">#$1</a>', $text);
		return $text;
	}
}