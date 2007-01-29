/**
 * Original Source: Book: DHTML Utopia: Modern Web Design Using JavaScript & DOM
 * Also: http://www.sitepoint.com/article/dhtml-utopia-modern-web-design/2
 */

/**
 * Automagically set up rollover event handlers for all IMGs within links with the rollover class
 *
 * This is executed once after page load
 *
 * @todo preload rollover images?
 */
function setupRollovers()
{
	if(!document.getElementsByTagName)
	{ // We are NOT in a DOM-supporting browser:
		return;
	}

	// Get ALL the links in the current document:
	var all_links = document.getElementsByTagName('a');

	// Go through all the links:
	for(var i = 0; i < all_links.length; i++)
	{
		var link = all_links[i];
		if(link.className && (' ' + link.className + ' ').indexOf(' rollover ') != -1)
		{ // The link has the rollover class (among potentially other classes):
			// Set up event handlers:
			link.onmouseover = mouseover;
			link.onmouseout = mouseout;
		}
	}
}


/**
 * caters for the differences between Internet Explorer and fully DOM-supporting browsers
 */
function findTarget(e)
{
 var target;

 if (window.event && window.event.srcElement)
   target = window.event.srcElement;
 else if (e && e.target)
   target = e.target;
 if (!target)
   return null;

 while (target != document.body &&
     target.nodeName.toLowerCase() != 'a')
   target = target.parentNode;

 if (target.nodeName.toLowerCase() != 'a')
   return null;

 return target;
}


/**
 * MouseOver event handler
 */
function mouseover(e)
{
	var target = findTarget(e);
	if (!target) return;

	var img_tag = target.childNodes[0];
	if( img_tag.nodeName.toLowerCase() != 'img')
	{
		img_tag = target.childNodes[1];
	}

	// Take the "src", which names an image called "something.ext",
	// Make it point to "something_over.ext"
	img_tag.src = img_tag.src.replace(/(\.[^.]+)$/, '_over$1');
}


/**
 * MouseOut event handler
 */
function mouseout(e)
{
 var target = findTarget(e);
 if (!target) return;

	var img_tag = target.childNodes[0];
	if( img_tag.nodeName.toLowerCase() != 'img')
	{
		img_tag = target.childNodes[1];
	}

 // Take the "src", which names an image as "something_over.ext",
 // Make it point to "something.ext"
 img_tag.src = img_tag.src.replace(/_over(\.[^.]+)$/, '$1');
}


/**
 * When the page loads, set up the rollovers
 */
addEvent( window, 'load', setupRollovers, false );