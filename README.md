# wpic
Wordpress with Invision Community integration. Plugins replaces header, navigation menu and comments in Wordpress. IPS Community Suite is used to log in and store information about users. Comments are stored in Wordpress.

Installation:
- WP and IC database tables must be in the same database;
- In constants.php change \define( 'COOKIE_PATH', '/' ); to root folder;
- Change IC folder in bridge-ipb4-wp.php - public static $forumDir = 'forum';
- Activate Bridge plugin
- Add code to header.php from bridge-ipb4-wp/readme.txt
- Activate Comments plugin
- Install wpic-connect.tar to IC applications for sends emails about replies to comments in WP through the IC mail system
- Read readme files for additional steps

Known issues:
- it is necessary to exclude ips4_loggedIn cookies from the page cache or manually configure the cache for each block;
- you need to remove unused Invision Community styles and scripts on WordPress pages;
- full functionality degrades Google Pagespeed rating;
- WordPress and IC installation must be on the same domain.

Tested compatibility:
- Wordpress 5.8.1;
- IPS Community Suite 4.6.7.

The plugins are posted as is. Configuration required.
