=== Nike+ iPod Stats ===
Contributors: mjar81
Donate link: http://www.ear-fung.us/apps/nikeplus/
Tags: nike, nikeplus, running, ipod
Requires at least: 2.0.5
Tested up to: 2.8
Stable tag: 1.4.4

Allows you to display Nike+ iPod running data on your wordpress blog.

== Description ==

The Nike+ iPod Stats plugin for Wordpress is a great way to display to the world how you’re doing at your workouts. It uses Nike’s public API (the same one used for the official Nike widgets) to retrieve your personal data and formats it to display correctly on your Wordpress blog.

Requirements:

* A wordpress blog running on a server with PHP ? 4.3.0 compiled with curl support.
* A Nike+ login ID (used to access http://www.nike.com/nikeplus/)
* Minimal knowledge of Wordpress to install the plugin

What it does:

* Gets your personal running data from Nike.com in XML format
* Caches the data locally so your site load time is not dependent on the speed of the Nike website.
* Displays your data in an easy to read way.
* Easy to use configuration in your Wordpress administration panel
* Ability to enable or disable the display of your "Power Song"
* Ability to display all your goals under the general run data.
* Ability to link your power song to the artist on the iTunes Music Store
* Ability to show your most recent runs
* Graphs your runs

== Installation ==

Installation Instructions:

1. Upload the nikePlus.php file to your Wordpress plugins directory ex: /wp-content/plugins/)
2. Enable the plugin in Wordpress administration.
3. Go to the "Options" tab in Wordpress administration and select the "Nike+" tab.
4. Enter your Nike+ username
5. Enter your Nike+ password
6. Choose your display options
7. Enter a local caching path that is writeable.
	* The path to your /wp-content/ directory should already be filled in.
	* This directory must be writeable or it will not work!
8. Insert this code into your sidebar.php file in your template folder:
`<?php
	if(function_exists(’get_nikePLUS’)){
	echo '<h2>My Nike+ Stats</h2>';
   	get_nikePLUS();
	}
?>` 

== Frequently Asked Questions ==

Coming soon!

== Screenshots ==

Coming soon!

== Changelog ==

= 1.4.4 =
	(June 25, 2009)

* Changes that resulted from folder name differences.
* Integrated graph smoothing. Thanks, James! http://www.jamesfrost.co.uk/
* Added image class for graph pictures, allows you to use your own CSS
    
= 1.4.3 =
	(June 25, 2009)

* Updated Nike+ API locations: fixes what Nike’s changes broke

= 1.4.2 =
	(May 19, 207)
    
* Updated Nike+ API locations: fixes what Nike’s changes broke

= 1.4.1 =
	(April 1, 2007)
	
* Changed Global variable schemes to be less generic, this should fix most users’ problems with the plugin not working
* Updated path to Sparkline.php so it is automatically calculated… should work now for blogs in a subdirectory
* Fixed a typo in admin

= 1.4 =
	(March 20, 2007)

* Regoranized code into multiple functions, simplifying the logic
* Added "Personal Best" display
* Added challenge and challenge detail
* Integrated Sparklines graphs. Distributed under the BSD license. http://www.sparkline.org/
* Changed package to be installed in a directory in the plugins folder
* Changed caching directory to /wp-content/plugins/nikePlus/cache/ because we are now generating so many cached files
* Added button in admin to manually get an update the next time the plugin is called (next page load), overriding the cache time
* Saving options in admin now automatically reloads cache on next page load
* Added ability to plot the powersong and user clicks in graphs. Commented out code because it produced fugly graphs
* The code for plotting powersong initializations and user audible feedback clicks are still there and work, but it’s unfortunate that the graphs were so ugly
* Added "My Records" support and admin checkbox
* Changed caching method to only get the requested number of recent runs, not the whole list of files. This greatly reduces the initial cache time
* Fixed bug where certain SSL certificates on II6 would not validate to pull secure data form nike.com (Thanks Bryan! – http://www.thebirdwells.net/)
* Added note to GoDaddy users on how to use the web proxy option in admin

= 1.3 =
	( January 19, 2007 )

* Added number formatting to the calories and miles numbers
* Added warning message to admin if chosen local path is not writable
* Fixed bug where version notification was messed up in the admin
* Fixed a kilometer vs. mile issue where it was not converted correctly for general run data ( http://www.ear-fung.us/apps/nikeplus/#comment-3071 )
* Tried to resolve goal distance issues, the data displayed is now *CLOSER* to what is actually on the Nike website
* MAJOR – Implemented "Recent Runs" with admin options
* Cleaned up admin options page a bit
* Now only displays non-completed goals
* Increased textbox for localpath so most users will be able to see the whole path
* Added GPL license

= 1.2.2 =
	( Jan 16, 2007 )

* Fixed a caching bug that made the program never get new information. Sorry guys :(

= 1.2.1 = 
	( Jan 16, 2007 )

* Stupid mis-labeling on my part made the plugin appear in the wp-admin as still version 1.1
* Supressed error message is the function file_get_contents() is disables by the host
* Changed Goals list to more accurately reflect the sidebar.php unordered list structure so that It might be formatted correctly automatically

= 1.2 = 
	( Jan 16, 2007 )

* Fixed caching issue where the caching was not consistent and was incorrectly reported in the html as retrieving the data from Nike
* Added notification of new versions to the admin panel
* Made it so that the power song would not display if Nike.com didn’t return any info
* Added Goals list and ability to turn them on and off

= 1.1 =
	( Jan 15, 2007 )

* Added ability to link your power song to the iTunes Store

= 1.0 =
	( Jan 15, 2007 )

* Initial Version
    
== TO DO ==

1. Display power song album artwork somehow (cached of course) Any suggestions on an album artwork search API? Send me a hint here: mjar81+nike@gmail.com
2. Store data in mysql
3. Generate "cool challenge status graphic" (thanks Greg: http://www.zinkwazi.com)
4. Enable local storage of challenge participants' names  and/or URL
5. Manual entry of runs/miles for non-nike+ users (maybe but not likely)

== Known Bugs ==

*Recent Runs does not display correctly unless you’ve run at least twice and synced those runs with the nikeplus website.