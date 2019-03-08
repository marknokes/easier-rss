<?php
include "./classes/Feeds.php";

include "./config.php";

/*****************************
* Access Control (optional)  *
*****************************/
/*
if( !isset( $_SERVER['HTTP_ORIGIN'] ) )

         die("Unable to set access control");

$allowed = array( 
    ".subdomain.com",
    "domain.com"
);

$url_parts = parse_url( $_SERVER['HTTP_ORIGIN'] );

$origin_sans_port = $url_parts["host"] ?? $_SERVER['HTTP_ORIGIN'];

foreach( $allowed as $allow )
{
	if( substr( $origin_sans_port, -strlen( $allow ) ) === $allow )
	{
		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN'] );

	 	header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
	}
}
*/

// // Instantiate the Feeds class
// $feed = new easier_rss\Feeds();

// // Initialize class properties and set content
// $feed->init()->set_content();

// // Output the content
// die( $feed->content );

/***********************************************************
* Example using Cache: https://github.com/marknokes/cache  *
***********************************************************/

/*
include "path/to/Cache.php";

// Include optional database connection info here
include "db-sample_mysqli.php";
$db_config = parse_ini_file( "db-config-sample.ini", true );
$db = new db( $db_config['ini_section'] );

// Your app may send these optional post values
$no_cache = isset( $_POST['no_cache'] ) && "true" === $_POST['no_cache'];
$force_update_cache = isset( $_POST['force_update_cache'] ) && "true" === $_POST['force_update_cache'];

// Instantiate the Feeds class
$feed = new easier_rss\Feeds();

// Initialize the class properties giving access to the $feed->id. The id will be used as the cache key.
$feed->init();

// Set up the cache options
$cache = new Cache( array( 
	"cache_age"  	=> "30 minutes",
	"cache_key"  	=> $feed->id,
	"cache_type" 	=> "mysqli", // Options: file, wincache, apcu, mysqli, sqlsrv
	"db_connection" => $db->connection // Use with mysqli or sqlsrv cache_type only
) );

$content = $cache->get_cached_content();

if( !$content || $force_update_cache )
{
    $feed->set_content();

    $content = $feed->content;

    if( false === $no_cache ) {
		$cache->set_content( $content )->cache();
    }

}

$db->close_connection();
*/

// Output the content
die( $content );