# Easier RSS

Add RSS feeds to your website. Supports SQL server, wincache, or file caching

To get started:

* Copy the javascript from the bottom of the index-sample.html and place in your common/main javascript file. Be sure that you have the latest jQuery library loaded first
* Go through the config-sample.php very carefully to determine the options you would like. 
* Rename config-sample.php to config.php
* If you're not using SQL server for persistance, remove the config options for the database. If you are, find a nice place for your db-config.ini and edit config.php accordingly.
* Lastly, if you're planning on using the default file caching method, be sure your server has permission to write to the cache file location

Happy RSS'ing!