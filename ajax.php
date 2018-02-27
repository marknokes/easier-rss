<?php
include "./classes/Feeds.php";

include "./config.php";

header('Access-Control-Allow-Origin: scripts.localhost' );

header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$feed = new easier_rss\Feeds();

$feed->init( $config )->run();

die( $feed->content );