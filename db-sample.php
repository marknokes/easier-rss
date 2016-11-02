<?php

class db {

	public $connection;

	private $db_connection_info;

	public function __construct( $config = array() )
	{
		foreach( $config as $key => $value )

			$this->$key = $value;

		$this->connection = sqlsrv_connect( $this->Server, $this->db_connection_info );

		if( !$this->connection )

		     die( print_r( sqlsrv_errors(), true) );
	}
}