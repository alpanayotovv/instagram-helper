# Instagram Helper
##### WordPress based library for retrieving and saving data from Instragram.

#### Requirements:
  * PHP 5 >= 5.3.0
  * WordPress
  * Instagram Client Secret and Client Id :)

##### Important!  
If Instagram does not return anything after proper configuration of the code, please read this -- https://www.instagram.com/developer/review/

#### Capabilities:
  * Fetch user feed by username.
  * Fetch Instagram posts by hashtag.
  * Save the data in a custom post type ( on demand ). 

#### How To Use:
Include the `instagram-helper.php` file in your functions.php.
At this point, you need to create a `$client` using the `Factory` class. The `$client` takes care of the authentication process:  
`$client  = Instagram_Helper\Factory::create( 'client', $configuration );`  

The `$configuration` array should look like this:  
```
$configuration = array(  
    'user_name'     => 'instagram_user_name',  
    'client_id'     => 'instagram_client_id',  
    'client_secret' => 'instagram_client_secret',  
`);
```

The `$client` object has the following methods:  
  * `get_redirect_uri()` -- Returns the redirection URL that should be placed in the Instagram Client `Valid redirect URIs` field.
  * `generate_authentication_url()` -- Returns the URL that should be visited in order to start the authentication process.
  * `get_access_token()` -- Handles the retrieval of the access token. Should not be used directly.
  
Once the authentication is completed, you can pull posts from Instagram. To do so, you need to create a `$data_manager` object, using the `Factory`.  

`$data_manager  = Instagram_Helper\Factory::create( 'data_manager', $manager_config );`  

The `$manager_config` array is optional. It may, however, contain the `limit` for the request -- `$manager_config = array( 'limit' => 5 ) `. This will force default limit of 5 entries for all queries.

The `$data_manager` has the following methods:
  * `fetch_user_feed( array( 'limit'  => 5 ) )` -- Fetches the last 5 posts from the feed of the user, used in the configuration of the `$client`.
  * `fetch_hashtag_feed( array( 'limit'  => 5, 'hashtag' => 'tag' ) )` -- Fetches the last 5 posts with that hashtag
  * `get_feed_data()` -- Returns the data fetched by the `fetch_user_feed` or `fetch_hashtag_feed` methods.
  * `clear_feed_data()` -- Delete the data fetched by the `fetch_user_feed` or `fetch_hashtag_feed` methods
  * `flush_cache()` -- Deletes the data transients ( by default, the data is stored in transients ).
  * `get_transient_lifetime()` -- Return the lifetime of the used transients.
  * `set_transient_lifetime()` -- Sets the lifetime of the transients ( this defaults to 300 seconds ).

---
#### Example:
```
$client_config = array(
    'user_name'     => 'instagram_user_name',  
    'client_id'     => 'instagram_client_id',  
    'client_secret' => 'instagram_client_secret',
);

$client  = Instagram_Helper\Factory::create( 'client', $client_config );
$data_manager  = Instagram_Helper\Factory::create( 'data_manager', array( 'limit' => 5 ) );

$data_manager->fetch_user_feed();
$feed = $data_manager->get_feed_data(); //the data from instagram in now available in $feed
```
---
#### Additional Capabilities:
##### Posts Storage
Sometimes, you would need to save the data from Instagram for later. In order to do this, you can use a `posts_store` obejct and it's `save()` method.
```
$storer = Instagram_Helper\Factory::create( 'posts_store', array( 'update_count' => 2 ) );
$storer->save( $feed );
```
The `update_count` specifies how many posts, of the already existing posts, should be updated with new information from Instagram ( this one is still work in progress :) )

##### Carbon Fields Integration
If you are using the `Carbon Fields` library, you can take advantage of the `Carbon_Helper.php` class. It must be used in conjunction with the `$client` object. 
```
$client  = Instagram_Helper\Factory::create( 'client', array( 'enable_carbon_support' => true ) );
$carbon_helper = Instagram_Helper\Factory::create( 'carbon_helper', $client );
$carbon_helper->create_options_page();
```
The `create_options_page()` method creates a page in the back end, containing fields for all the information needed by the `$client`. The `$client` fetches this information on its own, you do not need to do anything.
