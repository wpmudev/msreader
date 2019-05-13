# Reader

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations


## Usage

Reader turns your Multisite into a community by adding Tumblr, WordPress.com and Edublogs style "follow" features.

Complete with a beautiful and fully-featured dashboard reader experience that allows comments, RSS feeds and significant customization. 

### Follow Your Favorites

Reader adds a nifty "Follow" button to the admin bar on every site in your Multisite network. Users can click on the Follow button to add their favorite blogs to their Reader. Browse, search and engage with posts from the dashboard. 

### High-End Reader

We've combined the greatest features from Feedly, Tumblr and WordPress.com to give you a premium reader experience. Users can engage with sites across your network, without ever leaving their site.

### Simple Configuration

Setup can be completed in just a couple of seconds complete with a custom name a site exclusion. Tell reader what to include in your feed. Get a reader that runs the way you like it with powerful features and simple settings. 

### Stronger Sense of Community

You will be surprised by the impact this has on your users – receiving email notifications is great, but being able to browse and interact with comments can really set your network on fire.

### To Get Started

 _Important: Reader must be network-activated and requires the Post Indexer plugin to fetch your network content._

*   If you have not already installed & configured Post Indexer, and indexed your network, please do that first.

Once Reader is installed and network-activated, you'll see a new sub-menu item in your Network Settings menu: Reader.

### General Overview

Using the Reader is quite intuitive for the end-user, with tooltips for just about everything. But to help us understand all the features of Reader available to the network admin, let's first take a quick look at what your users see with all of them enabled. Then we'll explore all of the options & modules in greater detail, as well as what your users can do with them. 

1\. RSS Feed for selected display.  

2\. Post display area.  

3\. Search widget.  

4\. Main widget with filtering options.  

5\. Trending tags widget.

1\. _An RSS feed URL_ is generated for every display type. There are several ways to filter the display, and they're all discussed below... keep reading. :) 

2\. _The post display area_. This is where all the network posts display according to what post-types you have indexed with the [Post Indexer](https://premium.wpmudev.org/project/post-indexer/ "Post Indexer") plugin, and what filtering option has been selected. 

3\. _The search widget_ enables you and your users to search within all available content by title, author or tag, or any combination. 

4\. _The main widget_ contains a bunch of display filtering options, as well as a couple of handy links to stuff in your admin. 

5\. _The Trending Tags widget_ displays tags recently used in your network to make filtering for the hottest new content just too easy! Pretty cool, huh? We'll look into all that in more detail below. Oh, and be sure to read all the way through as some of the greatest interactive features of this plugin are detailed at the end! Now, let's take a closer look at the options available to you in the network admin, and see what effect they have on the Reader that appears on each site in your network.

### Brand the Reader for your network

The options panel in the network admin enables you to control the features and behavior of the Reader on all sites in your network. The first thing we want to do is decide where the Reader should appear in the admin menu of every site, and what it should be called in your network. 

1\. _Where should the Reader be?_ gives you 2 options. You can select to either:

*   add Reader as a new item under the Dashboard menu
*   replace the default Dashboard screen with Reader

Adding Reader as a new item does what it says on the tin: it adds a new Dashboard menu item that links to the Reader page in the admin. 
However, if you set it to replace the default Dashboard screen, your network users will instead be welcomed by your sexy new Reader when they log into their sites. Woot! 

2\. _Whats The Reader page name?_ enables you to customize the label that appears in the menu, so you can brand it to better fit your niche. 
Now let's get to all those cool features!

### Enable/Disable Features

Getting Reader up and running on your network couldn't be easier! All you really need to do is decide which features you want enabled. They are all enabled by default when you first install the plugin, but you can set 'em up the way you want 'em. !


##### Featured Posts

The _Featured Posts_ module enables you, as the network admin, to add any posts to a featured list. To do so, visit the Reader in the admin of your main site, and click the "Feature" button in any post that appears in the main content area. ![                        Reader Feature Post Any post that has been featured will have a little green star in the upper-right corner as a reminder, and the button will change to "Unfeature" so you can remove it from the featured list later if you want. Reader Featured Post Users on every site in your network can now filter what appears in the main content area of their Reader to show only featured posts by clicking the "Featured Posts" link in the main widget.  Note that the "Feature/Unfeature" button is only available to you as the network admin user, but will be visible to you on all sites in your network. Site admins and other users cannot add or remove posts from the featured list.

##### Filter by author or site

The _Filter by author or site_ module enables your users to filter the post list in the main content area of their Reader to show only posts from a specific site, or those from a specific author. To filter by author or site, simply click the author or site link that appears at the bottom of every post in the main content area of the Reader.  If the display is filtered to show only posts from a specific site, a "Visit site" button will also appear next to the title in the main content area.

##### Follow

The _Follow_ module enables your users to follow sites in your network and create a customized list of those that interest them. Before we look at how it works, there are some additional configuration settings that you can set in your network admin for this module. 

1\. Click to open the settings panel.  

2\. Default followed sites.  

3\. Show/hide toolbar button.

1\. Click the _Configure_ button next to the module to access the settings. 

2\. Enter the _IDs of sites followed by default_ in the Reader on all sites in your network.

*   For example, if you want the main site to be followed, enter the ID of your main site (usually _1_).
*   If you want multiple sites to be followed by default, enter a comma-separated list of IDs like so: _1,16,37_
*   If you don't want any sites to followed by default, simply leave the field blank.

3\. Select where to _Display follow button on_ the toolbar of all sites in your network. You can select to show the "Follow" button on

*   Both the frontend and backend (wp-admin) of all sites.
*   Only the frontend.
*   Only the backend.

Now your users can follow sites that interest them simply by clicking the "Follow" button at the bottom of any post in their Reader.  When a site is followed, the button will change to "Following" with a nice little red heart.  Also, depending on what you have set in the network configuration for the module, the toolbar will show the site is followed when the user visits the site. The main display can be filtered to show only posts from sites that the user is currently following simply by clicking the link in the main widget.  The "Following" link at the top of the main widget will also indicate the number of sites the user is following.  When that link is clicked, the main content area will display a list of all the sites the user is currently following. 

*   Clicking the site name on the left will display all posts from that site in the main content area.
*   Clicking the site URL will redirect the user to that site.
*   Clicking the "x" at the far right of any row will remove that site from all displays of followed sites.

##### My Posts

The _My Posts_ module enables your users to instantly view all posts that they have authored in your network. Enabling this module creates a "My Posts" link in the main widget.  When this link is clicked, the display in the main content area will be filtered to show only posts authored by the logged-in user viewing the Reader. Note that this will display all posts from the same author on all sites, regardless of which site the user is viewing. In other words, if the logged-in user has authored posts on multiple sites, they will all display.

##### My Sites

The _My Sites_ module creates a link in the main widget of the Reader enabling your users to filter the display to show only posts from sites where they are listed as users.  Note that this is a site filter, and it will display posts from all sites where the user is actually a user, regardless of the role they have on each site. Also note that the "My Sites" link which appears above the filter list and next to the "Following" link simply redirects to the standard _My Sites_ screen in the admin, and does not affect the Reader display.

##### Popular Posts

The _Popular Posts_ module also has an additional configuration option that you can set in your network admin. 


1\. Click to open the settings panel.  

2\. Set minimum number of comments for filter.

1\. Click the _Configure_ button next to the module to access the settings.

2\. Enter the _Minimum number of comments to the post to treat it as popular_ to determine which posts will display when the "Popular Posts" filter is used. When this module is enabled, it creates a link in the main widget that your users can click to filter the post display and view only those posts that have the minimum number of comments you just set.

##### Recent Posts

The Recent Posts module simply adds another filter to the main widget that, when clicked, displays the most recent posts from across your entire network. 

##### RSS Feeds

The _RSS Feeds_ module creates a special RSS feed for each of the above filters that you have enabled. 

*   To get the RSS Feed URL for the currently selected filter, click the RSS icon next to the title.
*   As an example, if the user is currently filtering & viewing only Featured Posts, the RSS Feed URL that appears can be used to display your network's featured posts.
*   You can use these RSS feeds in widgets on your site, on any other site, or even in your favorite feed reader on your mobile device to get instant notification of new stuff in your network!

Each feed URL also contains a private key. This can be very handy if, for example, someone unauthorized or unscrupulous gets hold of the URL and uses it to scrape content from your network.

*   You can reset ALL private keys for your feeds by clicking the link at the bottom of the RSS feed box.

##### Search

The _Search_ module enables a search box above the main widget so that your users can easily find the content they want.

*   You can search for any network content by title, tag or author, or any combination.


##### Trending Tags

The _Trending Tags_ module is another that has a few configuration options.

1\. Click to open the settings panel.  

2\. Set the number of links to display.  

3\. Select how many network tags to check.

1\. Click the _Configure_ button next to the module to access the settings.

2\. The _Number of links in "Trending Tags" widget_ is just that. Simply select how many you want displayed in the widget.

3\. The _Number of recently added tags to check_ setting enables you to select how many tags should be checked throughout your network to find those that are truly trending. Once enabled, this module creates a new widget in the Reader on each site.  Users need only click any tag in the widget to filter the display and show only posts containing that tag.

*   Please note that there is currently no dedicated RSS feed for tags.

##### User Info

The _User Info_ module is what displays data for the user currently viewing the Reader at the top of the main widget. 

*   The username link next to the avatar redirects to the user's profile in the admin.
*   The "My Posts" link, as detailed above, filters the post display.
*   The "My Sites" link, as mentioned above, redirects the user to their "My Sites" screen in the admin.
*   The "Following" link, as detailed above, will replace the content in the main post area with a display of all sites being followed.

If this module is disabled, the user can still view and manage the list of sites being followed by clicking the little gear icon that appears at the top-right of the widget. The "My Posts" link is also added to the filter list if that module is enabled. 

##### Default Feature

The final option in the network settings is the default feature.  You can select which of the features you have enabled above that should be the default display when users visit their Reader. For example, if you have enabled the "Recent Posts" module, you can set it as the default so your network's most recent content is shown to users first.

### The Reader

Oh yeah, the Reader has a reader, and this final feature is just too cool! You surely noticed a "Read More" button in previous images. That button does not redirect to the post in the site where it was published. Rather, it launches a modal window where your users can read the whole post & comment on it right in their dashboard! 

1\. Navigation & editor features.  

2\. Post display area.  

3\. Comments area.

1\. The _navigation bar_ of the Reader modal contains several features:

*   Easy navigation between posts in the currently selected filter.
*   Click "View Original" to be redirected to the post on the site where it is published.
*   The network admin, and users with the required capabilities, will see an "Edit" link so they can go directly to the post and edit it if required.
*   The network admin will also see a "Feature" link on all posts that are not currently featured. If the post is already featured, that link will show "Unfeature" instead.

2\. The _post display area_ enables your users to scroll through the entire post they're viewing so they don't even need to leave their own site!

*   The post meta at the top of each post shows the site where it is published and the author's name. Clicking either will close the modal reader and filter the main display to show all posts from that site or author.
*   Users can follow or unfollow the site right there too.

3\. The _comments area_ works just as if the user were commenting on the author's site.

*   Your users can comment on posts right in their reader, and even reply to other comments.
*   The network admin, site admins and users with the required capabilities can approve/unapprove, spam & trash comments.

### Responsiveness & Mobile Devices

On a final note, it's worth mentioning that the whole thing is fully responsive. It looks fabulous & works perfectly on mobile devices.  
