
<a href="http://seo1s.com">SEO Check Online</a>
This WordPress component features a number of useful functions for WordPress SEO in 2015. It is designed to augment Yoast's WordPress SEO plugin, expanding its capabilities as well as reigning in some potentially unwanted admin panel bloat.

This component features no options panel and must be configured in functions.php or some equivalent. See configuration for more information.

Features

Add default meta descriptions even when post type templates aren't filled out.
Sanitize meta descriptions. (Processes shortcodes, uses wp_trim_words, adds an ellipsis to the end, etc.)
Output meta tags for all images associated with posts that have a featured image/post thumbnail. (Yoast only displays one!)
Output additional image-related meta tags (type, width, and height) for Open Graph and Twitter.
Pinterest author hack specific to Yoast (see below).
Open Graph/Twitter title cleaner; removes blog name/description (requires Ubik Title).
Optionally remove the Yoast "SEO" button from the admin bar.
Optionally remove the Yoast post analysis filter dropdown.
Designed and tested for Yoast WordPress SEO 2.3.2+ (but it might be backwards compatible to something around 1.6).
Note: hacks related to the photo/gallery cards have been removed as both were deprecated by Twitter.
General functionality (mostly from a previous iteration of this plugin when it was meant to be a standalone SEO plugin):

'ubik_seo_image_attachments()' returns all images attached to a post (with featured images/post thumbnails displayed first).
Meta description generator (based on code from Ubik Excerpt).
Twitter Card support for summary and summary_large_image.
A workaround for the minor Facebook and Pinterest article author meta tag conflict (still an issue in early 2015).
Part of the Ubik family of WordPress components. Requires Ubik Terms, Ubik Text, and Ubik Title for full functionality.
