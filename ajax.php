<?php
include "./classes/Feeds.php";

include "./config.php";

easier_rss\Feeds::set_access_control_header( $config["allowed_domains"] );

$feed = new easier_rss\Feeds( $config );

die( $feed->content );