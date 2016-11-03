# Easier RSS

Add RSS feeds to your website. Supports SQL server, wincache, or file caching

## To get started:

* Copy the javascript from the bottom of the index-sample.html and place in your common/main javascript file. Be sure that you have the latest jQuery library loaded first
* Go through the config-sample.php very carefully to determine the options you would like. 
* Rename config-sample.php to config.php
* If you're not using SQL server for persistance, remove the config options for the database. If you are, find a nice place for your db-config.ini and edit config.php accordingly.
* If you're planning on using the default file caching method, be sure your server has permission to write to the cache file location. If you don't want to cache at all, set data-no-cache="true" on the container.

## Example
```html
<div class="easier-rss-feed"
		 data-feed-url="https://somedomain.com/rss/"
		 data-domain=""
		 data-no-cache="false"
		 data-force-update-cache="false"
		 data-max-num="3"
		 data-custom-class="my-class"
		 data-show-description="1"></div>
```

Happy RSS'ing!
