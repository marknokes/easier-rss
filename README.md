# Easier RSS

Add RSS/Atom feeds to your website. Supports SQL server, wincache, or file caching

## To get started:

* Copy the javascript from the bottom of the index-sample.html and place in your common/main javascript file. Be sure that you have the latest jQuery library loaded first
* Go through the config-sample.php very carefully to determine the options you would like. 
* Rename config-sample.php to config.php
* Caching options available using https://github.com/marknokes/cache, or some other user cache. See ajax.php for example usage.

## Example
```html
<div class="easier-rss-feed some-custom-class"
		 data-feed-url="https://somedomain.com/rss/"
		 data-domain=""
		 data-no-cache="false"
		 data-force-update-cache="false"
		 data-max-num="3"
		 data-show-description="1"
		 data-custom-attr="property|value,property_2|value_2"
		 data-display-images="true"></div>
```
* For the data-custom-attr field, use atom_content|content or atom_content|summary to change the retrieved description. The default is summary.

Happy RSS'ing!
