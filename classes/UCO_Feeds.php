<?php

use \easier_rss\Feeds as Feeds;

class UCO_Feeds extends Feeds {
	/**
 	* Retrieve feed
 	* 
 	* @return obj Object representing the feed data
 	*/
	protected function get_document()
	{
		$context_options=array(
		    "ssl"=>array(
		        "verify_peer"      => false,
		        "verify_peer_name" => false,
		    ),
		    'http'=>array(
				'header'=> "User-Agent: UCO Easier RSS\r\n"
			)
		); 

		if ( $content = @file_get_contents( $this->feed_url, false, stream_context_create( $context_options ) ) )
		{
			// Master calendar xmlns (www.dea.com/MasterCalendar/RSS/1.0) is broken. Here's a fun hack to get around it!
			if(false !== strpos($this->feed_url, "calendar.uco.edu"))

				$content = str_replace("mc:", "", $content);

			$doc = @simplexml_load_string( $content );

			return $doc;
		}
		else
		{
			die("Unable to retrieve feed!");
		}
	}
}