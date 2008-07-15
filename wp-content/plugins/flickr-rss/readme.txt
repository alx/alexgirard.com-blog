=== flickrRSS ===
Contributors: eightface
Tags: flickr, photos, images, sidebar, widget, rss
Requires at least: 2.5
Tested up to: 2.5
Stable tag: 4.0

Allows you to integrate Flickr photos into your site. It supports user, set, favorite, group and community photostreams.


== Description ==

This plugin allows you to easily display Flickr photos on your site. It supports user, set, favorite, group and community photostreams. The plugin is relatively easy to setup and configure via an options panel. It also has support for an image cache located on your server.


== Installation ==

1. Put flickrrss.php in your plugins directory
2. If you want to cache images, create a directory and make it writable
3. Activate the plugin
4. Configure your settings via the panel in Options
5. Add `<?php get_flickrRSS(); ?>` somewhere in your templates


== Frequently Asked Questions ==

= Can I get random images from my stream? =
No, it's a limitation of using the RSS feed (it only contains the most recent photos)	

= How do I refresh the photos manually? =
Good question. The plugin uses built-in WordPress functions to update the feed, I haven't figured out how to force an update.

= When I use multiple tags, why does nothing shows up? =
The feed will only pull in photos that have both tags, not one or the other.

= When I enable cache, why do just a bunch of random characters show up? =
You've probably specified the full path wrong. Double check with your host to make sure you've got it right. If you're still having troulbes, check the forum.

= How do a I get borders between photos? =
You need to edit your CSS file. There are plenty of tutorials online, you may find some help in the forum.

= Why aren't any photos showing up? =
Sometimes it can take a little while to kick in, have patience. Flickr may possibly have been down. Also, make sure it works without the cache first.

= Will it work with video? =
Yes, videos will be displayed as a thumbnail image. You'll need to click through to flickr to play it though.

== Advanced ==

The plugin also supports a number of parameters, allowing you to have multiple instances across your site.


1.	`$num_items` - how many photos you want to appear
2.	`$type` - specify *user, set, favorite, group or community* photosream
3.	`$tags` - a comma separated list of tags (with no spaces)
4.	`$imagesize` - *square, thumbnail, medium or large*
5.	`$before_image` - html appearing before each photo
6.	`$after_image` - html appearing after each photo
7.	`$id_number` - specify a user or group id
8.	`$set_id` - specify the set id (found in set url)


**Example 1**

`<?php get_flickrRSS(10, "community", "london,people"); ?>`
This would show the 10 most recent community photos tagged with london and people. It won't
show photos with only one of the tags.

**Example 2**

`<?php get_flickrRSS(20, "set", "", "thumbnail", "<li>", "</li>", "44124462494@N01", "72157601681097311"); ?>`

This would show the 20 most recent thumbnail sized photos from the specified user's set.

**Example 3**

`<?php get_flickrRSS(5, "group", "", "thumbnail", "<li>", "</li>", "34427469792@N01"); ?>`

This would show the 5 most recent thumbnail sized photos from the Flickr central group, and each photo would be wrapped in list tags.


== Feedback and Support ==

Visit the [Eightface forum](http://eightface.com/forum/) for help getting the plugin working and styling the photos. I'll do my best to respond, but sometimes I'm slow.


== Plugin History ==

**Latest Release:** April 9, 2008

* 4.0 - New interface for WP 2.5, support for sets and favorites, a few widget tweaks, some cleanup in the source
* 3.5 - Co-released with 4.0, bringing support for sets and favorites to WP 2.3
* 3.2.1 - Minor interface tweaks to avoid confusion
* 3.2 - Updated for WordPress 2.1
* 3.1.2 - Flickr altered the address of static photo urls, affected people using cache
* 3.1.1 - Minor update to add support for Flickr servers with three digits
* 3.1 - Flickr changed the RSS url, support for 20 images in admin panel, a few minor tweaks to display text
* 3.0.3 - added basic support for the WordPress Widgets plugin
* 3.0.2 - fixed before/after image bug, config panel back to options instead of presentation, put group option back in panel
* 3.0.1 - attempt to fix cache bug (wasn't working), fix for command parameter $type error
* 3.0 - Rewrote large parts of the plugin, proper flickr image size, parameters make more sense
* 2.3 - Flickr changed rss feed structure (permanent location of picture)
* 2.2 - should no longer display an error message if flickr times out.
* 2.1 - cURL (uses fopen if not found), empty cache no longer hardcoded, should work for multiple ids/tags, can now use quotes in the before/after tags, bugfixes
* 2.0.2 - bug fixes
* 2.0.1 - fixed new cache bug, flickr added a 10th photo server, breaking the script
* 2.0a - cURL instead of fopen, uses built in rss-functions instead of MagpieRSS, cleaned up options panel
* 1.2 - added thumbnail size
* 1.1 - bug fixes
* 1.0 - Options panel implemented
* 0.7 - Initial release

