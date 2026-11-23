=== JUCCA LLC Site ===
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.2

Serves the JUCCA LLC website at / (English) and /es/ (Spanish).

== How it works ==
* The home page and /es/ are served from templates/en.php and templates/es.php.
* The WordPress theme is not used for those two addresses. wp-admin and all other WordPress pages keep working.
* Deactivating the plugin brings back the normal WordPress home page. Nothing is deleted.
* Contact form messages go to the address at the top of jucca-site.php, sent with WordPress mail.
* /sitemap.xml lists both language versions. /robots.txt points to it.
* Visitors who open any other WordPress address (sample posts, feeds, search) are sent to the home page. Logged-in users are not affected.
* Photos are in images/. Replace a file with one of the same name to change a photo.
