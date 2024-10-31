=== Reuters Direct ===
Contributors: RNAGS
Donate link: http://thomsonreuters.com/en/products-services/reuters-news-agency.html
Tags: news_aggregator,Reuters,News,Reuters_Connect
Requires at least: 3.8
Tested up to: 5.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A full-featured news aggregator, powered by Reuters Connect: Web Services, which ingests Reuters content directly into a WordPress platform.

== Description ==

A full-featured news aggregator, powered by Reuters Connect: Web Services, which ingests Reuters news and picture content directly into a WordPress platform. Now categorize content directly into your own categories!

Reuters Direct uses our Reuters Web Services-API to ingest content into WordPress directly. Text wires are ingested as posts in draft status. Pictures are automatically ingested into the Media Gallery with appropriate descriptions and titles. Online Reports are ingested with attached pictures and can be set to publish status or draft depending on if a picture has been made available for it.

Configure ingestion of content via channel as well multiple image resolution and category codes.

*** In order to use this plugin, you must have a Reuters Connect: API user. This is a paid subscription and details for obtaining these credentials can be found in the FAQ. ***

== Installation ==

1. Activate the plugin through the 'Plugins' menu in WordPress
1. Select Reuters Direct from the Settings menu
1. Log in using your Reuters Connect: Web Services API username and password
1. Configure required channels, category codes, post author and media settings

** Please make sure plugin is deactivated/reactivated upon update **


== Frequently asked questions ==

= Is this an official Reuters released delivery method? =

Yes, is an official Reuters delivery method for Media delivery. See Donate Link or https://rmb.reuters.com/agency-api-demo/login.jsp for details.


= I don't have a Reuters Connect: Web Services username and password =

Select the Customer Service link, or under Help within the plugin and you will be provided a form which will submit a ticket to Reuters customer service. From there, an appropriate account representative will reach out to assist.

= Should I remain logged into the plugin? =

Yes, absolutely. If you log out of Reuters Direct, any polling and ingestion will stop. The Dashboard widget will also state "Not logged In" as well.

= What content will this ingest into Wordpress? =

Reuters  direct will ingest news and picture content as subscribed to in the entitlements of your account.
If any channel is missing in the Configuration page, please contact your Account Manager or select the Help drop-down to open a ticket with our Customer Service team.

= How do I identify content and their post status? =

Reuters Direct will set the Post status to either Publish or Draft status. You can also select which content gets which status. For example, Text Wire content will ALWAYS be set to Draft status. This is to allow Editors the change to review
the content prior to publish. Text wire content will be tagged with "REUTERS TXT" for easy filtering. Additionally, Online Reports can be set to Published only if there is an accompanying image. These stories are tagged "Reuters OLR-no image"
for easy filtering. Stories with recent updates are tagged "Updated" and will inherit its current publish status eg, if a post is in Publish state it will remain in Publish. Draft - in Draft. We recommend you remove or hide these tags upon publishing.

= How can I categorize my content into my site's predefined categories? =

Select 'User_Defined' as your category schema and then look for the Reuters News Feed vertical. To the right, enter in the category name which that content from that News Feed should be categorized in.

= I don't see any images with my Online Reports =

Reuters Direct will assign the first image in the package as the Featured Image of the article. It will also inline any images associated with the article at the bottom of the post. If you do not see the first image, please review your theme
to ensure it uses the Featured Image. You can also look at the Featured Image box at the bottom of the Edit Post screen to review the image.

= Will Reuters Direct ingest video? =

As of this writing, RD will not ingest video into Wordpress. This functionality is currently on our roadmap for inclusion.

= What is this Dashboard widget? =

RD will add a widget to your dashboard to communicate the back-end functionality and channel details. It will provide information on what's being updated, how many stories it's getting, cron jobs kicking off, etc.

= Gimmie the tech details!! =

RD uses our the Reuters Connect: Web Services API to make REST queries wrapped in cURL statements using the TLSv1.2 secure protocol.
Every three minutes, a cron job kicks off and pulls all available content from the API and written directly into the WordPress database. Any updates to stories are over-written in the databasebased on the GUID of the story. Story packages are ingested into Wordpress as Posts, and images directly into the Media Gallery.
Associated metadata is included with the images and category information is also carried into Wordpress at the time of ingestion. In order for content to have Reuters as the author, we create a Reuters contributor level user in your user database which has a randomized high-strength password. This allows us to post content as Reuters, but disallows anyone from knowing or logging in as that user.

= I have feedback on RD. What can I do? =

We are definitely open to any and all feedback. Please use the Help drop-down in the Configuration page to open a ticket with our Customer Service team and will be happy to look at your request. Please select Feedback as your Query Type in the form.

= Am I in the danger zone? =

Ask Lana.
...
....
LANA!

== Screenshots ==

1. Screenshot-1: Configuration screen of Reuters Direct
2. Screenshot-2: Configuration screen cont.
3. Screenshot-3: Configuration screen cont.
4. Screenshot-4: Posts screen in WordPress show the post tagging for Online Reports wo/ Images
5. Screenshot-5: Media gallery
6. Screenshot-6: Image detail screen showing attached metadata

== Changelog ==

= 3.0.0 =
* Bug fixes.
* Updated configs.

= 2.8.1 =
* Added Taxonmy configuration.
* Added Post Type configuration.
* Added Cron Interval configuration.

= 2.7.0 =
* Added purge functionality.
* Bug fixes.

= 2.6.2 =
* Bug fixes.

= 2.6.0 =
* Fixed cron job scheduling.
* Enhanced story update logic.
* Improved logging functionality.
* Increased download window per cron.

= 2.5.2 =
* Fixed image source redirect.

= 2.5.1 =
* Added analytics.

= 2.5.0 =
* Included User defined categories which allow for categorizing content to your predefined categories.
* Added Reuters as author of all Posts.
* Plugin will now update every three minutes instead of every 5 minutes.

= 2.4.3 =
* Fixed image directory naming.

= 2.4.2 =
* Fixed duplicate image bug.

= 2.4.1 =
* Release version

== Upgrade notice ==

= 3.0.0 =
* New version 3.0.0 is available for download.

