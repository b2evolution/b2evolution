// Use this file only together with _file_quick_upload.view.php and quickupload.php
onload = function()
{
	var xhr = new XMLHttpRequest;
	if( !xhr )
	{
		alert("Your browser does not support XMLHttpRequest technology!");
		return;
	}
	var file_queue = new Array();
    var bars = new Array();
    var divs = new Array();
    var results = new Array();
    var uploading = false;

    function size( bytes )
    { // simple function to show a friendly size
        var i = 0;
        while( 1023 < bytes )
        {
            bytes /= 1024;
            ++i;
        };
        return  i ? bytes.toFixed( 2 ) + ["", " KB", " MB", " GB", " TB"][i] : bytes + " bytes";
    };

    /*
     * Start uploading the next file, if the queue is not empty
     */
    function upload_next()
    {
    	if( file_queue.length == 0 )
    	{
    		uploading = false;
    		return;
    	}
    	uploading = true;
    	var curr_file = new Array();
    	curr_file.push( file_queue.shift() );
    	var bar = bars.shift();
    	var div = divs.shift();
    	var result_span = results.shift();
    	var filename = uploading_text + " \"" + curr_file[0].name + "\" ...";

    	sendMultipleFiles({

            // list of files to upload
            files:curr_file,

            upload_url:url,

            max_size: maxsize,

            // clear the container 
            onloadstart:function()
            {
        		div.innerHTML = filename;
                bar.style.width = "0px";
            },

            // do something during upload ...
            onprogress:function(rpe)
            {
                bar.style.width = (((this.sent + rpe.loaded) * 200 / this.total) >> 0) + "px";
            },

            // fired when last file has been uploaded
            onload:function(rpe, xhr)
            {
            	var result_code = xhr.responseText.charAt(0);
            	if( result_code !== '0' && result_code !== '1' )
            	{ // Unsuccessful upload
            		div.innerHTML += xhr.responseText;
            		bar.style.width = "0px";
            		upload_next();
            	}
            	else
            	{ // Successful upload
            		if( result_code == '1' )
            		{ // file name was changed, show submit button
            			submit.setAttribute("type", "submit");
            			div.innerHTML += xhr.responseText.substr(1);
            		}
            		result_span.innerHTML = ok_text;
                    bar.style.width = "200px";
                    upload_next();
                }
            },

            // if something is wrong ... (from native instance or because of size)
            onerror:function(){
            	var message = size_error.replace( "%1", size(this.file.fileSize) );
            	message = message.replace( "%2", size(maxsize) );
                div.innerHTML = message;//sprintf( size_error, size(this.file.fileSize), size(maxsize) );//"The file " + this.file.fileName + " is too big [" + size(this.file.fileSize) + "]";
                upload_next();
            }
        });
    }

    var input = document.getElementById( "quickupload" );
    var upload_queue = document.getElementById( "upload_queue" );
    var submit = document.getElementById( "saveBtn" );

    // auto upload on files change
    //input.addEventListener("change", function(){
    input.onchange = function() {

		var i = 0;
		if( ! input.files ) {
			alert(incompatible_browser);
			return;
		}

		while( i < input.files.length )
		{
			// create new upload node
			file_queue.push(input.files[i]);
			var newupload = upload_queue.appendChild(document.createElement("div"));
			newupload.id = "uploadblock";
			var div = newupload.appendChild(document.createElement("div"));
			var bar = newupload.appendChild(document.createElement("div")).appendChild(document.createElement("span"));
			var result_span = newupload.appendChild(document.createElement("span"));
			var separator = newupload.appendChild(document.createElement("div"));
			separator.id = "upload_separator";
			bar.id = "bar"
			bar.parentNode.id = "progress";
			result_span.id = "result_success";
			bars.push(bar);
			divs.push(div);
			results.push(result_span);
			i++;
		}
		if( !uploading )
		{
			upload_next();
		}
		input.value = "";

    };//, false);
};
