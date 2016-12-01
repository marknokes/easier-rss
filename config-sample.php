<?php

// Make a connection to your database here if you like
include "db-sample.php";

// Store your database settings in an .ini file somewhere
$db_config = parse_ini_file( "db-config-sample.ini", true );

// Instantiate the database object
$db = new db( $db_config['ini_section'] );

// Be sure to comment out or remove the caching method you don't need!
$config = array(
	"no_content_message" => "There are currently no items to show.",
	"cache_age"			 => "25 minutes", // Optional string in minutes, up to 59. Default is "15 minutes"
	"allowed_domains" 	 => array(
		'scripts.localhost'
	),

	/* This is the default format for feeds. Alternately, you may change the format here, or give your feed a data-feed-name and write a corresponding custom callback for truely custom output  */
	"items_wrap" 		 => array(
		"container" 	 => "<ul class='%1\$s' cached='%2\$s'>%3\$s</ul>",
		"item_wrapper" 	 => "</li>" // Only specify end tag here
	),

	/* For database caching */
	"db_connection" 	 => $db->connection, // See db-sample.php

	/* For file caching */
	"cache_path" 		 => "C:\\diff\\path\\from\\default", // Default is "C:\\tmp\\"
	"cache_prefix" 		 => "my-prefix-", // Only applies to file caching. Default is "feed-cache-"

	/* For use with wincache, if you have it */
	"wincache"			 => true // Default is false
);

/**
* Custom callback functions. The function name must match the data-feed-name attrubute on the HTML div container,
* otherwise, the feed_default method from the Feeds class will be used
* 
* @param obj $doc Document object returned by simplexml_load_string()
* @param int $max_num The maximum number of results to display. This is set on the HTML div container as the data-max-num attribute. Default is 0
* @return str sprintf( $Feeds->items_wrap["container"], $Feeds->custom_class, $Feeds->cache_message, $content );
*/
function some_custom_callback( $Feeds )
{
	if( !is_object( $doc ) ) return "";

	$content = "";

	$num = 0;

	foreach ( $doc->channel->item as $child )
	{
		if ( $Feeds->max_num !== 0 && $num === $Feeds->max_num )

			break;

		$content .= $Feeds->wrap_item( $child->description );

		$num += 1;
	}

	return $this->add_items_to_container( $content );
}