/** Basic upload manager for single or multiple files (Safari 4 Compatible)
 * @author  Andrea Giammarchi
 * @blog    WebReflection [webreflection.blogspot.com]
 * @license Mit Style License
 */

// function to upload a single file via handler
sendFile = (function(toString){
    var isFunction = function(Function){return  toString.call(Function) === "[object Function]";},
        split = "onabort.onerror.onloadstart.onprogress".split("."),
        length = split.length;
    return  function(handler){
        if(handler.max_size && handler.max_size < handler.file.fileSize){
            if(isFunction(handler.onerror))
                handler.onerror(xhr);
            return;
        };
        var xhr = new XMLHttpRequest,
            upload = xhr.upload;
        for(var
            xhr = new XMLHttpRequest,
            upload = xhr.upload,
            i = 0;
            i < length;
            i++
        )
            upload[split[i]] = (function(event){
                return  function(rpe){
                    if(isFunction(handler[event]))
                        handler[event].call(handler, rpe, xhr);
                };
            })(split[i]);
        upload.onload = function(rpe){
            if(handler.onreadystatechange === false){
                if(isFunction(handler.onload))
                    handler.onload(rpe, xhr);
            } else {
                setTimeout(function(){
                    if(xhr.readyState === 4){
                        if(isFunction(handler.onload))
                            handler.onload(rpe, xhr);
                    } else
                        setTimeout(arguments.callee, 15);
                }, 15);
            }
        };
		
		handler.file.fileName = handler.file.fileName || handler.file.name;
		handler.file.fileSize = handler.file.fileSize || handler.file.size;

        xhr.open("post", handler.upload_url, true);
        xhr.setRequestHeader("If-Modified-Since", "Mon, 26 Jul 1997 05:00:00 GMT");
        xhr.setRequestHeader("Cache-Control", "no-cache");
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("X-File-Name", handler.file.fileName);
        xhr.setRequestHeader("X-File-Size", handler.file.fileSize);
        xhr.setRequestHeader("Content-Type", "application/octet-stream");
        xhr.send(handler.file);
        return  handler;
    };
})(Object.prototype.toString);

// function to upload multiple files via handler
function sendMultipleFiles(handler){
    var length = handler.files.length,
        i = 0,
        onload = handler.onload;
    handler.current = 0;
    handler.total = 0;
    handler.sent = 0;
    while(handler.current < length)
        handler.total += handler.files[handler.current++].fileSize;
    handler.current = 0;
    if(length){
        handler.file = handler.files[handler.current];
        sendFile(handler).onload = function(rpe, xhr){
            if(++handler.current < length){
                handler.sent += handler.files[handler.current - 1].fileSize;
                handler.file = handler.files[handler.current];
                sendFile(handler).onload = arguments.callee;
            } else if(onload) {
                handler.onload = onload;
                handler.onload(rpe, xhr);
            }
        };
    };
    return  handler;
};
