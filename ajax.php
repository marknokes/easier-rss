<?php
include "./classes/Feeds.php";

include "./config.php";

/* Access Control */
if( !isset( $_SERVER['HTTP_ORIGIN'] ) )

         die("Unable to set access control");

$allowed = array( 
    ".subdomain.com",
    "domain.com"
);

// Get scheme, host, port, etc.
$url_parts = parse_url( $_SERVER['HTTP_ORIGIN'] );

// If the hostname is available, set a variable without the port for the next "if" check
$origin_sans_port = $url_parts["host"] ?? $_SERVER['HTTP_ORIGIN'];

/*
* Since the Access-Control-Allow-Origin header must contain hostname:port, but
* we are only interested in checking that the hostname ends in .subdomain.com, we must check the
* "non-ported" version of HTTP_ORIGIN, but then set the header to match HTTP_ORIGIN (with port)
*/
foreach( $allowed as $allow )
{
	if( substr( $origin_sans_port, -strlen( $allow ) ) === $allow )
	{
		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN'] );

	 	header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
	}
}
/* End Access Control */

$feed = new easier_rss\Feeds();

$feed->init( $config )->set_content();

die( $feed->content );

/* Example using Cache: https://github.com/marknokes/cache

include "path/to/Cache.php";

$no_cache = isset( $_POST['no_cache'] ) && "true" === $_POST['no_cache'];

$force_update_cache = isset( $_POST['force_update_cache'] ) && "true" === $_POST['force_update_cache'];

$feed = new easier_rss\Feeds();

$feed->init();

$cache = new Cache( array( 
	"cache_age"  => "30 minutes",
	"cache_key"  => $feed->id,
	"cache_type" => "apcu"
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

die( $content );

*/