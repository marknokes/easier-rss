<?php

/* Add additional config items here. See Feeds class for available variables */
$config = array(
	"no_content_message" => "There are currently no items to show.",

	/* This is the default format for feeds. Alternately, you may change the format here, or give your feed a data-feed-name and write a corresponding custom callback for truely custom output  */
	"items_wrap" 		 => array(
		"container" 	 => "<ul class='%1\$s' cached='%2\$s'>%3\$s</ul>",
		"item_wrapper" 	 => "</li>", // Only specify end tag here
		"title_wrapper"	=> "</h3>"
	)
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