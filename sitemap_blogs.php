<?php
/**
 * Experimental Sitemap Stub FOR A BLOG AGGREGATION
 *
 * It seems that Google won't index a sitemap with a ? in it. So you need a stub.
 */
require_once dirname(__FILE__).'/conf/_config.php';

# First, select which blog you want to map
# You can find these numbers in the back-office under the Blogs section.
# You can also create new blogs over there. If you do, you may duplicate this file for the new blog.
$blog = 1;
# We want to include all posts, even those not primarily linked to blog #1
# i-e even those aggregated through an extra cat:
$cat_focus = 'wide';

# Let's force the use of the sitemap skin:
$tempskin = '_sitemap';

# Sitemap spec requires UTF-8, so let's force all outputs to UTF-8:
$force_io_charset_if_accepted = 'utf-8';

// We don't want _blog_main to try any extra path resolution
$resolve_extra_path = false;

require $inc_path.'_blog_main.inc.php';
?>