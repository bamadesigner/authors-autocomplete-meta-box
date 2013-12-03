=== Authors Autocomplete Meta Box ===
Contributors: bamadesigner
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=bamadesigner%40gmail%2ecom&lc=US&item_name=Rachel%20Carden%20%28Authors%20Autocomplete%20Meta%20Box%29&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: author, authors, autocomplete, auto, complete, metabox, meta, box, edit, post, page
Requires at least: 3.3
Tested up to: 3.6
Stable tag: 1.2

Replaces the default WordPress Author dropdown with a meta box that allows you to select the author via Autocomplete.

== Description ==

Replaces the default WordPress Author meta box (that has an author dropdown) with a meta box that allows you to select the post's, or page's, author via Autocomplete.

Can really come in handy if you have a lot of authors and are tired of scrolling through that long author dropdown.

== Credits ==

Big shoutout to [ereleases.com](http://www.ereleases.com) for commissioning this plugin and letting me share it with the community. Thanks, guys. You rock!

Lots of thanks to Andrew Kurtis from WebHostingHub Support for providing the Spanish translation.

== Filters ==

Filters can really come in handy to nail down specific customizations on a site by site basis. I am what you would consider a power user so I'm a big fan of actions and filters and try to incorporate them into my plugins as much as possible. Here are some pretty helpful filters to get your authors autocomplete meta box working just the way you like.

= authors_autocomplete_mb_allow_user_id =

This filter allows you to block users from the autocomplete results according to user id. Return *true* to allow and *false* to deny.

It passes the user id, along with the post ID and post type. **Don't forget:** when using a filter, you **MUST** return something. Here's an example to help you get started: 

`<?php

// return *true* to allow the user and *false* to deny the user from autocomplete results
add_filter( 'authors_autocomplete_mb_allow_user_id', 'filter_authors_autocomplete_mb_allow_user_id', 1, 4 );
function filter_authors_autocomplete_mb_allow_user_id( $allow_user_id, $user_id, $post_id, $post_type ) {
	if ( $user_id == 4 )
		return false;
	return $allow_user_id;
}
?>`

= authors_autocomplete_mb_allow_user_role =

This filter allows you to block users from the autocomplete results according to user role. Return *true* to allow and *false* to deny.

It passes the user role, along with the post ID and post type. **Don't forget:** when using a filter, you **MUST** return something. Here's an example to help you get started: 

`<?php

// return *true* to allow the user and *false* to deny the user from autocomplete results
add_filter( 'authors_autocomplete_mb_allow_user_role', 'filter_authors_autocomplete_mb_allow_user_role', 1, 4 );
function filter_authors_autocomplete_mb_allow_user_role( $allow_user_role, $user_role, $post_id, $post_type ) {
	if ( $user_role == 'administrator' )
		return false;
	return $allow_user_role;
}
?>`

= authors_autocomplete_mb_author_capability =

When checking to see if a user has author privileges, and should therefore be included in the autocomplete results, the plugin checks the user's capabilities. If the user is editing a page, then the user is added if they have the capability to *edit_pages*, otherwise the user is added if they have the capability to *edit_posts*.

If you would like to change the author privilege capability, then this filter is for you. It passes the default capability, along with the post ID and post type. **Don't forget:** when using a filter, you **MUST** return something. Here's an example to help you get started:

`<?php
// changing the author capability according to post type
add_filter( 'authors_autocomplete_mb_author_capability', 'filter_authors_autocomplete_mb_author_capability', 1, 3 );
function filter_authors_autocomplete_mb_author_capability( $author_capability, $post_id, $post_type ) {
	if ( $post_type == 'movies' )
		return 'edit_movies';
	return $author_capability;
}
?>`

= authors_autocomplete_mb_custom_user_search_user_ids =

Want the autocomplete box to search information besides the default user_login, display_name and user_email? This is the filter for you.

Use this filter to run whatever search you like and simply return the user IDs from your results.

It passes a blank array to get you started, the search term, post ID and post type. **Don't forget:** when using a filter, you **MUST** return something.

Here's an example from me helping a user search their [CIMY User Extra Fields](http://wordpress.org/plugins/cimy-user-extra-fields/):

`<?php
// search CIMY User Extra Fields with search term and return user IDs
add_filter( 'authors_autocomplete_mb_custom_user_search_user_ids', 'authors_autocomplete_custom_user_search', 1, 4 );
function authors_autocomplete_custom_user_search( $user_ids, $search_term, $post_id, $post_type ) {
   global $wpdb;
   return $wpdb->get_col( "SELECT users.ID, cimy_uef_data.VALUE FROM $wpdb->users users LEFT JOIN {$wpdb->prefix}cimy_uef_data cimy_uef_data ON cimy_uef_data.USER_ID = users.ID WHERE ( cimy_uef_data.VALUE LIKE '%$search_term%' OR users.user_login LIKE '%$search_term%' OR users.display_name LIKE '%$search_term%' OR users.user_email LIKE '%$search_term%' ) ORDER BY users.ID ASC" );
}
?>`

== Installation ==

1. Upload 'authors-autocomplete-meta-box' to the '/wp-content/plugins/' directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Start finding and selecting a post or page's author like a boss.

== Screenshots ==

1. The Authors Autocomplete shows the author's display name, login and email address to help you select the correct author. Once selected, the author's gravatar is displayed to the left.

== Changelog ==

= 1.2 =
* Added ability to run custom user search via filter.
* Added support for Internationalization.
* Added Spanish translation with help from Andrew Kurtis from WebHostingHub Support.
* Added support for custom post type author capability testing.
* Renamed the CSS and Javascript file.

= 1.1 =
* Updated author capability testing from user level to user capabilities.
* Added ability to change author capability via filter.
* Added ability to remove users from autocomplete results according to user id.
* Added ability to remove users from autocomplete results according to user role.

= 1.0 =
* Plugin launch!

== Upgrade Notice ==

= 1.2 =
* Added ability to run custom user search via filter.
* Added support for Internationalization.
* Added Spanish translation with help from Andrew Kurtis from WebHostingHub Support.
* Added support for custom post type author capability testing.
* Renamed the CSS and Javascript file.

= 1.1 =
* Updated author capability testing from user level to user capabilities.
* Added ability to change author capability via filter.
* Added ability to remove users from autocomplete results according to user id.
* Added ability to remove users from autocomplete results according to user role.