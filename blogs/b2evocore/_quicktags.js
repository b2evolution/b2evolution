// b2 quick tags
// - authorized adaptation of the 'bbCode control code' by subBlue design ( www.subBlue.com )

// Define the quick tags
bbcode = new Array();
// fplanque: customized tags:
bbtags = new Array('<strong>','</strong>','<em>','</em>','<ins>','</ins>','<del>','</del>','<blockquote>\n','</blockquote>\n','<p>','</p>\n','  <li>','</li>\n','<img src="" border="0" alt="" />','','<a href="">','</a>','<ul>\n','</ul>\n','<code>','</code>');
imageTag = false;

// Replacement for arrayname.length property
function getarraysize(thearray) {
	for (i = 0; i < thearray.length; i++) {
		if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
			return i;
		}
	return thearray.length;
}

// Replacement for arrayname.push(value) not implemented in IE until version 5.5
// Appends element to the array
function arraypush(thearray,value) {
	thearray[ getarraysize(thearray) ] = value;
}

// Replacement for arrayname.pop() not implemented in IE until version 5.5
// Removes and returns the last element of an array
function arraypop(thearray) {
	thearraysize = getarraysize(thearray);
	retval = thearray[thearraysize - 1];
	delete thearray[thearraysize - 1];
	return retval;
}


function checkForm(formObj) 
{
	formErrors = false;

	if (formObj.content.value.length < 2) {
		formErrors = "You must enter a message!";
	}

	if (formErrors) {
		alert(formErrors);
		return false;
	} else {
		bbstyle(formObj, -1);
		//formObj.preview.disabled = true;
		//formObj.submit.disabled = true;
		return true;
	}
}





function bbstyle(formObj, bbnumber) 
{
	donotinsert = false;
	theSelection = false;
	bblast = 0;

	if (bbnumber == -1) { // Close all open tags & default button names
		while (bbcode[0]) {
			butnumber = arraypop(bbcode) - 1;
			formObj.content.value += bbtags[butnumber + 1];
			buttext = eval('formObj.addbbcode' + butnumber + '.value');
			eval('formObj.addbbcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
		}
		formObj.content.focus();
		return;
	}

	if ((parseInt(navigator.appVersion) >= 4) && (navigator.appName == "Microsoft Internet Explorer"))
		theSelection = document.selection.createRange().text; // Get text selection

	if (theSelection) {
		// Add tags around selection
		document.selection.createRange().text = bbtags[bbnumber] + theSelection + bbtags[bbnumber+1];
		formObj.content.focus();
		theSelection = '';
		return;
	}

	// Find last occurance of an open tag the same as the one just clicked
	for (i = 0; i < bbcode.length; i++) {
		if (bbcode[i] == bbnumber+1) {
			bblast = i;
			donotinsert = true;
		}
	}

	if (donotinsert) {		// Close all open tags up to the one just clicked & default button names
		while (bbcode[bblast]) {
				butnumber = arraypop(bbcode) - 1;
				formObj.content.value += bbtags[butnumber + 1];
				buttext = eval('formObj.addbbcode' + butnumber + '.value');
				eval('formObj.addbbcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
				imageTag = false;
			}
			formObj.content.focus();
			return;
	} 
	else 
	{ // Open tags

		if (imageTag && (bbnumber != 14)) {		// Close image tag before adding another
			formObj.content.value += bbtags[15];
			lastValue = arraypop(bbcode) - 1;	// Remove the close image tag from the list
			formObj.addbbcode14.value = "image";	// Return button back to normal state
			imageTag = false;
		}

		// Open tag
		formObj.content.value += bbtags[bbnumber];
		if ((bbnumber == 14) && (imageTag == false)) imageTag = 1; // Check to stop additional tags after an unclosed image tag
		arraypush(bbcode,bbnumber+1);
		eval('formObj.addbbcode'+bbnumber+'.value += "*"');
		formObj.content.focus();
		return;
	}

}

function bbinsert(formObj, strIns, strInsClose ) 
{	// fplanque: added function

	theSelection = false;

	if (document.selection)
	{
		formObj.content.focus();
		theSelection = document.selection.createRange().text; // Get text selection
		// Add tags around selection
		document.selection.createRange().text = strIns + theSelection + strInsClose;
		formObj.content.focus();
		theSelection = false;
		return;
	}

	formObj.content.value += strIns + strInsClose;
	formObj.content.focus();
	return;

}



// swirlee's bblink hack, slightly corrected
// fplanque: modified
function bblink(formObj, bbtype ) 
{
	current_url = prompt("URL:","http://");
	if(current_url == null) 
	{
		current_url = "";
		return;
	}
	var re = new RegExp ('http%3A//', 'gi') ;
	var current_url = current_url.replace(re, 'http://') ;
	if(current_url == "http://")
	{
		current_url = "";
		return;
	}

	if( bbtype == 'img' )
	{	// IMAGE
		current_alt = prompt("ALTernate text:","ALT");
		if((current_alt == null) || (current_alt == "") || (current_alt == "ALT")) {
			alttag = ' alt=""';
		} else {
			alttag = ' alt="' + current_alt + '"';
		}
	}

	current_title = prompt("Title:","External - English");
	if((current_title == null) || (current_title == "") ) 
	{
		title = '';
	} else {
		title = unescape(current_title);
	}
	
	if( bbtype == 'a' )
	{
		final_link = '<a href="' + current_url + '" title="' + title + '">';
		bbinsert( formObj, final_link, '</a>' );
	}
	else
	{
		final_img = '<img src="' + current_url + '"' + alttag + ' title="' + title + '" />';
		bbinsert( formObj, '', final_img );
	}
	
}