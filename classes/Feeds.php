<?php

namespace easier_rss;

class Feeds
{
    /**
    * Timestamp representing the current run time
    *
    * @var int
    */
    private $time = 0;

    /**
    * Timestamp representing the last time the feed was retrieved. This value is stored in the cache with the content
    *
    * @var int
    */
    private $last_run = 0;

    /**
    * This currently only applies to feed_default, although, custom callbacks could easily use it.
    * Max number of feed items to list.
    *
    * @var int
    */
    public $max_num = 0;

    /**
    * The content to deliver. This will be echo'd from ajax.php
    *
    * @var str
    */
    public $content = "";

    /**
    * Options are db and file. If a sql server db_connection object is passed into config.php, the $cache_type will be set to "db",
    * otherwise "file" will be used
    *
    * @var str
    */
    private $cache_type = "";

    /**
    * Should the feed be cached? This option should be set in the feed HTML as a data parameter, i.e., no-cache="true"
    *
    * @var bool 
    */
	private $no_cache = false;

	/**
	* The full path to the cache file, if sql server is not used for persistance. This option may be overridden in config.php
	*
	* @var str
	*/
	private $cache_path = "";

	/**
	* The age of the cache data set as human readable time string in minutes, up to 59. For example: 25 minutes". This option may be overridden in config.php
	*
	* @var str
	*/
    private $cache_age = "15 minutes";

    /**
	* Prefix to be prepended to the cache filename. This option may be overridden in config.php
	*
	* @var str
	*/
    private $cache_prefix = "feed-cache-";

    /**
	* The full path to the cache file including the cache filename, if sql server is not used for persistance. Configured in $this->init() and includes options from config.php
	*
	* @var str
	*/
	private $cache_file = "";

	/**
	* If sql server is used for persistance, the database id of the cache data. Configured in $this->init() as an md5 hash
	*
	* @var str
	*/
	private $id = "";

    /**
    * Instance of the database connection object if sql server persistance is used
    *
    * @var obj
    */
	private $db_connection;

	/**
    * Use wincache for persistance?
    *
    * @var bool
    */
	private $wincache = false;

	/**
	* The list of supplied CSS classes from the container
	*
	* @var str
	*/
	public $css_class_list = "";

	/**
	* Database query formats
	*
	* @var array
	*/
	private $queries = array(
		"get" => "SELECT * FROM cache_data WHERE id = '%1\$s'",
		"set" => "IF EXISTS (SELECT id FROM cache_data WHERE id = '%1\$s')
					  BEGIN
					     UPDATE cache_data SET last_run = %2\$d, cache_content = '%3\$s' WHERE id = '%1\$s'
					  END
				  ELSE
					  BEGIN
					     INSERT INTO cache_data (id, last_run, cache_content)
					     VALUES ('%1\$s', %2\$d, '%3\$s')
					  END"
	);

	/**
	* Default return format for content
	*
	* @var array
	*/
	public $items_wrap = array(
		"container" 	=> "<ul class='%1\$s' data-cached='%2\$s'>%3\$s</ul>",
		"item_wrapper" 	=> "</li>",
		"title_wrapper"	=> "</h3>"
	);

	/**
	* Should images be displayed in the feed?
	*
	* @var str
	*/
	public $display_images = true;

	/**
	* Should the feed title be displayed?
	*
	* @var str
	*/
	public $display_title = true;

    /**
	* The callback will be set based on the feed name retrieved from the feed-name data attribute on the HTML container. If no feed-name attribute is found,
	* feed_default will be used. For custom formatted feeds, build a function that corresponds with the feed_name.
	*
	* @var str
	*/
    private $callback = "feed_default";

	/**
    * Create a configured instance to use the class.
    *
    * @param array $config An array of options.
    * @return null
    */
	public function __construct( $config )
	{
		$this->init( $config )->run();
    }

 	/**
 	* Initialize class arguments based on config.php and other pre-determined things
 	*
 	* @param array $config See __construct()
 	* @return obj $this
 	*/
    private function init( $config )
    {
    	if ( empty( $_REQUEST['feed_url'] ) )
    	{
			die("Missing feed URL!");
    	}
		else
		{
			foreach( $config as $key => $value )

				$this->$key = $value;

			$this->time = time();

			$this->feed_url = trim( $_REQUEST['feed_url'] );

			$this->domain = isset( $_REQUEST['domain'] ) && "false" !== $_REQUEST['domain'] ? trim( $_REQUEST['domain'] ) : "";

			$this->max_num = isset( $_REQUEST['max_num'] ) && "false" !== $_REQUEST['max_num'] ? (int)$_REQUEST['max_num'] : 0;

			$this->css_class_list = isset( $_REQUEST['css_class_list'] ) ? trim( $_REQUEST['css_class_list'] ) : "";

			$this->show_desc = isset( $_REQUEST['show_desc'] ) && "false" !== $_REQUEST['show_desc'] ? (int)$_REQUEST['show_desc'] : 0;

			$this->no_cache = isset( $_REQUEST['no_cache'] ) && "true" === $_REQUEST['no_cache'];

			$this->force_update_cache = isset( $_REQUEST['force_update_cache'] ) && "true" === $_REQUEST['force_update_cache'];

			$this->display_images = isset( $_REQUEST['display_images'] ) && "false" !== $_REQUEST['display_images'];

			$this->display_title = isset( $_REQUEST['display_title'] ) && "false" !== $_REQUEST['display_title'];

			$feed_name = trim( $_REQUEST['feed_name'] );

			$this->callback = !empty( $feed_name ) && function_exists( $feed_name ) ? $feed_name : $this->callback;

			$hash = hash( 'md5', $this->feed_url );

			if( isset( $this->db_connection ) )
			{
				$this->cache_type = 'db';

				$this->id = $hash;
			}
			elseif( true === $this->wincache )
			{
				$this->cache_type = 'wincache';

				$this->id = $hash;
			}
			else
			{
				$this->cache_type = 'file';

				$this->cache_path = "" !== $this->cache_path ? $this->cache_path : sys_get_temp_dir();

				$this->cache_file = $this->cache_path . "\\" . $this->cache_prefix . $hash;
			}

			$this->cache_message = !$this->no_cache ? date("Y-m-d h:i A", $this->time) . " using " . $this->cache_type . " storage" : "";
			
			return $this;
		}
    }

    /**
 	* Retrieve content from file cache
 	* 
 	* @return bool true if cache file exists and properly json decoded, false otherwise
 	*/
	private function from_file_cache()
    {
    	return file_exists( $this->cache_file ) ? json_decode( file_get_contents( $this->cache_file ) ) : false;
    }

    /**
 	* Retrieve content from wincache
 	* 
 	* @return bool true if cache file exists and properly json decoded, false otherwise
 	*/
	private function from_wincache()
    {
    	return wincache_ucache_exists( $this->id ) ? json_decode( wincache_ucache_get( $this->id ) ) : false;
    }

    /**
 	* Query the database
 	* 
 	* @param str $query The SQL query
 	* @return obj Object representing the data, false if query fails
 	*/
    private function do_query( $query )
    {
		$stmt = sqlsrv_prepare( $this->db_connection, $query );

		if( !$stmt )

		    die( print_r( sqlsrv_errors(), true) );

		$result = sqlsrv_execute( $stmt );

		if( $result === false )

		  die( print_r( sqlsrv_errors(), true) );

		$obj = sqlsrv_fetch_object( $stmt );

		return is_object( $obj ) ? $obj : false;
    }

    /**
 	* Determine if it's time to run based on cache age
 	* 
 	* @return bool True if $this->last_run is false or if the current time is greater than the timestamp + the cache age, otherwise false
 	*/
    private function do_run()
	{
		if( 0 === $this->last_run || $this->no_cache || $this->force_update_cache )

			return true;

		else

			return $this->time >= strtotime( "+" . $this->cache_age, $this->last_run );
	}

	/**
 	* Retrieve feed
 	* 
 	* @return obj Object representing the feed data
 	*/
	private function get_document()
	{
		if ( $content = @file_get_contents( $this->feed_url ) )
		{
			$doc = @simplexml_load_string( $content );

			return $doc;
		}
		else
		{
			die("Unable to retrieve feed!");
		}
	}

	/**
 	* Create a URL to an image
 	*
 	* @param str $rel_path The relative path to the image 
 	* @return str If the domain is set, full URL path, otherwise relative path only
 	*/
	public function create_resource_url( $rel_path )
	{
		$rel_path = trim( $rel_path );

		return isset( $this->domain ) ? $this->domain . $rel_path : $rel_path;
	}

	/**
	* Truncate based on the length of $this->show_desc. (0=none, 1=full, >1=truncated)
	*
	* @param str $content The content to optionally truncate
	* @return str The content. I know!
	*/
	public function truncate( $content = "" )
	{
		if( $this->show_desc === 1 )
			
			return $content;

		elseif( $this->show_desc > 1 )

			return substr( $content , 0, $this->show_desc ) . "&hellip;";
	}

	public function wrap_item( $content = "" )
	{
		$item_start = str_replace( "/", "", $this->items_wrap["item_wrapper"] );

		return $item_start . $content . $this->items_wrap["item_wrapper"];
	}

	/**
	* $content is wrapped by default in $this->wrap_item. This just adds the content to the container supplied by $this->items_wrap['container']
	*
	* @param str $content The content to add to the container
	* @return str The content. I know, right?!?!
	*/
	public function add_items_to_container( $content )
	{
		$title_start = str_replace( "/", "", $this->items_wrap["title_wrapper"] );

		$return = "";

		$return .= $this->display_title && isset( $this->feed_title ) ? $title_start . $this->feed_title . $this->items_wrap["title_wrapper"] : "";

		$return .= sprintf( $this->items_wrap["container"], $this->css_class_list, $this->cache_message, $content );

		return $return;
	}

	/**
 	* The default callback method. Formats a feed as an unordered list with the title wrapped in an anchor
 	* 
 	* @param obj $doc Document object returned by $this->get_document()
 	* @return bool false if $doc is empty string or true otherwise
 	*/
	private function feed_default( $obj = null )
	{
		if( !is_object( $this->doc ) )

			return false;

		$content = "";

		$num = 0;

		$this->feed_title = isset( $this->doc->channel->title ) ? $this->doc->channel->title : "";

		foreach ( $this->doc->channel->item as $child )
		{
			if ( $this->max_num !== 0 && $num === $this->max_num )

				break;

			// Check that the enclosure contains type attribute
			$enclosure_type = isset( $child->enclosure ) ? (string)$child->enclosure->attributes()->type : false;
			// Let's be sure that the enclosure type is that of an image
			$enclosure_is_img = $enclosure_type && false !== stripos( $enclosure_type, "image" );
			// If we have an image type, get the url
			$img_src = $enclosure_is_img ? (string)$child->enclosure->attributes()->url : false;

			$has_link = ( !empty( $child->link ) );

			$item = "";

			$item .= $has_link ? "<a href='". $child->link ."' target='_blank'>" : "";

			$item .= $this->display_images && $img_src ? "<img src='".$img_src."' />" : "";

			$item .= $child->title;

			$item .= $has_link ? "</a>" : "";

			$item .= $this->show_desc ? "<span class='broncho-feed-description' style='display:block;margin:5px 0 15px;'>" . $this->truncate( $child->description ) . "</span>" : "";

			$content .= $this->wrap_item( $item );

			$num += 1;
		}

		return $this->add_items_to_container( $content );
	}

	/**
 	* Build an unordered list of feed content
 	* 
 	* @return obj $this
 	*/
	private function set_content()
	{
		$this->doc = $this->get_document();

		if( isset( $this->doc->channel->item ) && 0 === sizeof( (array)$this->doc->channel->item ) )

			$content = sprintf( $this->items_wrap["container"], $this->css_class_list, $this->cache_message, $this->wrap_item( $this->no_content_message ) );

		else
		{
			if( !function_exists( $this->callback ) )

				$content = call_user_func( array( $this, $this->callback ), $this );

			else

				$content = call_user_func( $this->callback, $this );
		}

		$this->content .= $content;

		return $this;
	}

	/**
 	* Escape content for sql server insert
 	* 
 	* @param str $data The string to escape
 	* @return str|int Empty string, integer if $data is numeric, or escaped string data
 	*/
	private function mssql_escape_string( $data )
	{
		if ( !isset( $data ) || empty( $data ) )

			return '';

		if ( is_numeric( $data ) )

			return $data;

		$non_displayables = array(
			'/%0[0-8bcef]/', // url encoded 00-08, 11, 12, 14, 15
			'/%1[0-9a-f]/',  // url encoded 16-31
			'/[\x00-\x08]/', // 00-08
			'/\x0b/',        // 11
			'/\x0c/',        // 12
			'/[\x0e-\x1f]/'  // 14-31
		);

		foreach ( $non_displayables as $regex )

			$data = preg_replace( $regex, '', $data );
		
		$data = str_replace("'", "''", $data );
		
		return $data;
	}

	/**
 	* Cache the data. Use database or file system depending on options
 	* 
 	* @return bool true if database query succeeds or cache file created successfully, false otherwise
 	*/
	private function cache()
	{
		if ( $this->no_cache )
		{
			return false;
		}
		elseif( "" !== $this->id && "db" === $this->cache_type )
		{
			return $this->do_query( sprintf(
				$this->queries["set"],
				$this->id,
				$this->time,
				$this->mssql_escape_string( $this->content )
			) );
		}
		elseif( "" !== $this->id && "wincache" === $this->cache_type )
		{
			$time = date( "00:i:s", strtotime( "+" . $this->cache_age, 0 ) );
			
			$seconds = strtotime("1970-01-01 $time UTC");
			
			return false !== wincache_ucache_set(
				$this->id,
				json_encode( array(
					"last_run" 		=> $this->time,
					"cache_content" => $this->content
				) ),
				$seconds
			);
		}
		elseif( "" !== $this->cache_file && "file" === $this->cache_type )
		{
			return false !== file_put_contents(
				$this->cache_file,
				json_encode( array(
					"last_run" 		=> $this->time,
					"cache_content" => $this->content
				) )
			);
		}
	}

	/**
 	* Retrieve feed data from database, file cache, or real time depending on configuration
 	* 
 	* @return null
 	*/
    private function run()
    {
    	switch( $this->cache_type )
    	{
    		case "db":

    			$feed_data = $this->do_query( sprintf( $this->queries["get"], $this->id ) );

    			break;

    		case "wincache":

    			$feed_data = $this->from_wincache();

    			break;

    		case "file":

				$feed_data = $this->from_file_cache();
    	}

    	$this->last_run = isset( $feed_data->last_run ) ? $feed_data->last_run : 0;

    	$cache_content = isset( $feed_data->cache_content ) ? $feed_data->cache_content : "Error retrieving content from cache";

    	if( $this->do_run() || !$feed_data )

    		$this->set_content()->cache();

    	else

    		$this->content = $cache_content;
    	
    	return;
    }

    /**
 	* Set access control for ajax requests
 	* 
 	* @return null
 	*/
    public static function set_access_control_header( $allowed_domains = array() )
    {
		$origin_parts = isset( $_SERVER['HTTP_ORIGIN'] ) ? parse_url( $_SERVER['HTTP_ORIGIN'] ) : array('host' => '');

		$origin_host = str_replace("www.", "", $origin_parts['host'] );

		if ( in_array( $origin_host, $allowed_domains ) )
		{
			header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN'] );

			header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
		}
	}
}