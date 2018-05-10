<?php

namespace easier_rss;

class Feeds
{
	/**
    * atom or rss
    *
    * @var str
    */
    public $feed_type = "";

    /**
    * Title of feed
    *
    * @var str
    */
    public $feed_title = "";

    /**
    * Link of feed
    *
    * @var str
    */
    public $feed_link = "";

    /**
    * Items
    *
    * @var array
    */
    public $children = array();

    /**
    * Timestamp representing the current run time
    *
    * @var int
    */
    protected $time = 0;

    /**
    * PHP date() format string
    *
    * @var str
    */
    public $date_format = "F j, Y";

    /**
    * Timestamp representing the last time the feed was retrieved. This value is stored in the cache with the content
    *
    * @var int
    */
    protected $last_run = 0;

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
    protected $cache_type = "";

    /**
    * Should the feed be cached? This option should be set in the feed HTML as a data parameter, i.e., no-cache="true"
    *
    * @var bool 
    */
    protected $no_cache = false;

	/**
	* The full path to the cache file, if sql server is not used for persistance. This option may be overridden in config.php
	*
	* @var str
	*/
	protected $cache_path = "C:\\tmp\\";

	/**
	* The age of the cache data set as human readable time string in minutes, up to 59. For example: 25 minutes". This option may be overridden in config.php
	*
	* @var str
	*/
    protected $cache_age = "15 minutes";

    /**
	* Prefix to be prepended to the cache filename. This option may be overridden in config.php
	*
	* @var str
	*/
    protected $cache_prefix = "feed-cache-";

    /**
	* The full path to the cache file including the cache filename, if sql server is not used for persistance. Configured in $this->init() and includes options from config.php
	*
	* @var str
	*/
	protected $cache_file = "";

	/**
	* If sql server is used for persistance, the database id of the cache data. Configured in $this->init() as an md5 hash
	*
	* @var str
	*/
	protected $id = "";

    /**
    * Instance of the database connection object if sql server persistance is used
    *
    * @var obj
    */
	protected $db_connection;

	/**
    * Use wincache for persistance?
    *
    * @var bool
    */
	protected $wincache = false;

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
	protected $queries = array(
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
    protected $callback = "feed_default";

	/**
    * Create a configured instance to use the class.
    *
    * @return null
    */
	public function __construct()
	{
    }

    /**
    * Split custom attributes and set class properties
    *
    * @param str String containing property name and value seperated by pipe character, e.g. my_property|my_value
    * @return null The method sets $this->my_property to the specified value
    */
    protected function set_class_props_from_custom_atts( $pipe_seperated_atts )
    {
    	$custom_attr_parts = explode( "|", $pipe_seperated_atts );

    	if( 2 == sizeof( $custom_attr_parts ) )
    	{
	    	$custom_attr_key = $custom_attr_parts[0];

			$custom_attr_val = $custom_attr_parts[1];

			$this->$custom_attr_key = "false" === $custom_attr_val ? false : $custom_attr_val;
    	}

    	return;
    }

 	/**
 	* Initialize class arguments based on config.php and other pre-determined things
 	*
 	* @param array $config See __construct()
 	* @return obj $this
 	*/
    public function init( $config )
    {
    	if ( empty( $_POST['feed_url'] ) )
    	{
			die("Missing feed URL!");
    	}
		else
		{
			foreach( $config as $key => $value )

				$this->$key = $value;

			$this->time = time();

			$this->feed_url = trim( $_POST['feed_url'] );

			$this->domain = isset( $_POST['domain'] ) && "false" !== $_POST['domain'] ? trim( $_POST['domain'] ) : "";

			$this->max_num = isset( $_POST['max_num'] ) && "false" !== $_POST['max_num'] ? (int)$_POST['max_num'] : 0;

			$this->css_class_list = isset( $_POST['css_class_list'] ) ? trim( $_POST['css_class_list'] ) : "";

			$this->show_desc = isset( $_POST['show_desc'] ) && "false" !== $_POST['show_desc'] ? (int)$_POST['show_desc'] : 0;

			$this->no_cache = isset( $_POST['no_cache'] ) && "true" === $_POST['no_cache'];

			$this->force_update_cache = isset( $_POST['force_update_cache'] ) && "true" === $_POST['force_update_cache'];

			$this->display_images = isset( $_POST['display_images'] ) && "false" !== $_POST['display_images'];

			$this->display_title = isset( $_POST['display_title'] ) && "false" !== $_POST['display_title'];

			$this->display_author = false;

			$custom_attr = isset( $_POST['custom_attr'] ) && "false" !== $_POST['custom_attr'] ? $_POST['custom_attr']: false;

			$feed_name = trim( $_POST['feed_name'] );

			$this->callback = !empty( $feed_name ) && function_exists( $feed_name ) ? $feed_name : $this->callback;

			if( false !== $custom_attr )
			{
				// Add ability to pass multiple custom attributes while making backwards compatible
				if( false !== strpos( $custom_attr, ",") )
				{
					foreach ( explode( ",", $custom_attr ) as $pipe_seperated_atts )

						$this->set_class_props_from_custom_atts( $pipe_seperated_atts );
				}
				elseif( false !== strpos( $custom_attr, "|") )

					$this->set_class_props_from_custom_atts( $custom_attr );
			}

			// If a feed_id is present, use it for caching, otherwise, generate an md5 hash of properties to distinguish the feeds if the same url is used in different callback functions.
			$to_hash = sprintf( "%s:%s:%s", $this->callback, $this->feed_url, (string)$this->max_num );

			$hash = isset( $this->feed_id ) ? $this->feed_id : hash( 'md5', $to_hash );

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

				$this->cache_file = $this->cache_path . $this->cache_prefix . $hash;
			}

			$this->cache_message = !$this->no_cache ? date("Y-m-d h:i A", $this->time): "";
			
			return $this;
		}
    }

    /**
 	* Retrieve content from file cache
 	* 
 	* @return bool true if cache file exists and properly json decoded, false otherwise
 	*/
	protected function from_file_cache()
    {
    	return file_exists( $this->cache_file ) ? json_decode( file_get_contents( $this->cache_file ) ) : false;
    }

    /**
 	* Retrieve content from wincache
 	* 
 	* @return bool true if cache file exists and properly json decoded, false otherwise
 	*/
	protected function from_wincache()
    {
    	return wincache_ucache_exists( $this->id ) ? json_decode( wincache_ucache_get( $this->id ) ) : false;
    }

    /**
 	* Query the database
 	* 
 	* @param str $query The SQL query
 	* @return obj Object representing the data, false if query fails
 	*/
    protected function do_query( $query )
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
    protected function do_run()
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
	protected function get_document()
	{
		$context_options=array(
		    "ssl"=>array(
		        "verify_peer"      => false,
		        "verify_peer_name" => false,
		    ),
		    'http'=>array(
				'header'=> "User-Agent: Easier RSS\r\n"
			)
		); 

		if ( $content = @file_get_contents( $this->feed_url, false, stream_context_create( $context_options ) ) )
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
	protected function feed_default( $obj = null )
	{
		if( !is_object( $this->doc ) )

			return false;

		$content = "";

		$num = 0;

		foreach ( $this->children as $child )
		{
			if ( $this->max_num !== 0 && $num === $this->max_num )

				break;

			$img_src = $this->get_image_src( $child );

			$link = $this->get_link( $child );

			$has_link = ( !empty( $link ) );

			$date = $this->get_date( $child );

			$author = $this->get_author( $child );

			$item = "";

			$item .= $has_link ? "<a href='". $link ."' target='_blank'>" : "";

			$item .= $this->display_images && $img_src ? "<img src='".$img_src."' />" : "";

			$item .= $this->get_item_title( $child );

			$item .= $has_link ? "</a><br />" : "<br />";

			if( $author && $this->display_author )
			{
				$item .= "<span class='author'>";

				$item .= $author["name"] ? "<span class='name'>". $author["name"] ."</span><br />": "";

				$item .= $author["uri"] ? "<span class='uri'>". $author["uri"] ."</span><br />": "";

				$item .= $author["email"] ? "<span class='email'>". $author["email"] ."</span><br />": "";

				$item .= "</span>";				
			}

			$item .= "<time datetime='" . $date . "'>" . $date . "</time><br />";

			$item .= $this->show_desc ? "<span class='feed-description'>" . $this->truncate( $this->get_item_description( $child ) ) . "</span>" : "";

			$content .= $this->wrap_item( $item );

			$num += 1;
		}

		return $this->add_items_to_container( $content );
	}

	protected function set_feed_atts()
	{
		if( isset( $this->doc->channel ) )

			$this->feed_type = "rss";

		elseif ( isset( $this->doc->entry ) )
			
			$this->feed_type = "atom";

		switch( $this->feed_type )
		{
			case "rss":

				$feed_link = $this->get_link( $this->doc->channel );

				$feed_title = $this->doc->channel->title;

				foreach ($this->doc->channel->item as $key => $value)
				{
					if( "item" === $key )

						$this->children[] = $value;
				}

				$this->content_node = "description";

				$this->img_enc_node = array(
					"node" 	=> "enclosure",
					"att"	=> "url"
				);

				$this->date_node = "pubDate";
				
				break;

			case "atom":

				$feed_link = $this->get_link( $this->doc );

				$feed_title = $this->doc->title;
				
				foreach ($this->doc->entry as $key => $value)
				{
					if( "entry" === $key )

						$this->children[] = $value;
				}

				$atom_content = array( "summary", "content" );
				
				$this->content_node = isset( $this->atom_content ) && in_array( $this->atom_content, $atom_content ) ? $this->atom_content : "summary";

				$this->img_enc_node = array(
					"node" 	=> "link",
					"att"	=> "href"
				);

				$this->date_node = "updated";
				
				break;
		}

		$this->feed_title = isset( $feed_link, $feed_title ) ? "<a href='". $feed_link ."'>" . $feed_title . "</a>" : "";
		
		return;
	}

	public function get_image_src( $child )
	{
		$size = sizeof( $child->{$this->img_enc_node["node"]} );
		
		if( 0 !== $size )
		{
			for( $i=0; $i < $size; $i++ )
			{
				// Check that the enclosure contains type attribute
				$type = (string)$child->{$this->img_enc_node["node"]}[$i]->attributes()->type;
				// Let's be sure that the enclosure type is that of an image
				$enclosure_is_img = isset( $type ) && false !== stripos( $type, "image" );
				// Set the url if it exists
				$url = (string)$child->{$this->img_enc_node["node"]}[$i][$this->img_enc_node["att"]];
				// If we have an image type and url, return url
				if( $enclosure_is_img && isset( $url ) )

					return $url;
			}
		}

		return false;
	}

	public function get_link( $node )
	{
		$link_node = $node->link[0];

		if( !$link_node)

			return "";

		switch( $this->feed_type )
		{
			case "rss":
				$link = (string)$link_node;
				break;
			case "atom":
				$link = (string)$link_node->attributes()->href;
				break;
			default:
				$link = "";
		}

		return $link;
	}

	public function encode( $string = "" )
	{
		return iconv( 'UTF-8', 'UTF-8//IGNORE', $string );
	}

	public function get_item_title( $child )
	{
		return $this->encode( $child->title );
	}

	public function get_item_description( $child )
	{
		$node = $child->{$this->content_node};

		if( !$node )

			return "";

		$content = !empty( (string)$node->children()->getName() ) ? $node->children()->asXML() : $node;

		return $this->encode( $content );
        
	}

	public function get_date( $child )
	{
		$pubDate = $child->{$this->date_node};

		if( !isset( $child->{$this->date_node} ) )

			return "";

		$time = strtotime( $child->{$this->date_node} );

		return date( $this->date_format, $time );
	}

	public function get_author( $child )
	{
		$author = array(
			"name" 	=> "",
			"uri"	=> "",
			"email" => ""
		);

		if( $child->author )
		{
			$children = $child->author->children();
		
			if( $children )
			{
				foreach( $children as $author_data )
				{	
					$key = (string)$author_data->getName();

					if( in_array( $key, array_keys( $author ) ) )

						$author[ $key ] = (string)$child->author->{ $key };
				}
			}
			else
			{
				$author_name = (string)$child->author;

				$author = $author_name ? array( "name" => $author_name ) : array();
			}
		}

		return $author;
	}

	/**
 	* Build feed content according to feed default layout, or user supplied callback
 	* 
 	* @return obj $this
 	*/
	protected function set_content()
	{
		$this->doc = $this->get_document();

		$this->set_feed_atts();

		if( 0 === sizeof( $this->children ) )
			// No content
			$content = sprintf( $this->items_wrap["container"], $this->css_class_list, $this->cache_message, $this->wrap_item( $this->no_content_message ) );

		else
		{
			if( !function_exists( $this->callback ) )
				// class default callback
				$content = call_user_func( array( $this, $this->callback ), $this );

			else
				// user supplied callback
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
	protected function mssql_escape_string( $data )
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
	protected function cache()
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
    public function run()
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
}