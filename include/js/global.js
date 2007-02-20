// 
// These javascript functions get loaded into ALL datacom web pages.
// Add and modify functions with care!
//

var ua = navigator.userAgent.toLowerCase();
var msIE = ((ua.indexOf('msie') != -1) && (ua.indexOf('opera') == -1) && (ua.indexOf('webtv') == -1)); 

// Add a "trim" function to Javascript's string object type
String.prototype.trim = function() {
    // Strip leading and trailing white-space
    return this.replace(/^\s*|\s*$/g, "");
}


//
// Hide or display an object
//
function toggleBox(id) {
    if(document.layers) {    //NN4+
        if (document.layers[id].visibility == "show") {
            document.layers[id].visibility = "hide";
        } else {
            document.layers[id].visibility = "show";
        }
    }
    else if(document.getElementById) {     //gecko(NN6) + IE 5+
        var obj = document.getElementById(id);
        if (obj.style.visibility == "visible" || obj.style.display == "block") {
            obj.style.visibility = "hidden";
            obj.style.display = "none";
        } else {
            obj.style.visibility = "visible";
            obj.style.display = "block";
        }
    }
    else if(document.all) {  // IE 4
        if (document.all[id].style.visibility == "visible" || document.all[id].style.display == "block") {
            document.all[id].style.visibility = "hidden";
            document.all[id].style.display = "none";
        } else {
            document.all[id].style.visibility = "visible";
            document.all[id].style.display = "block";
        }
    }
}


// Remove an element
function removeElement(name) {
    var remove = document.getElementById(name);
    if (remove) {
        var parent = remove.parentNode;
        parent.removeChild(remove);
    }
}



// Taken from mail.google.com and modified
function el(id) {
    if(document.layers) {    //NN4+
       return document.layers[id];
    }
    else if (document.getElementById) {  // Gecko (NN6, Firefox, IE 5+)
        return document.getElementById(id);
    }
    else if(document.all) {  // IE 4
        return document.all[id];
    }
    else if (window[id]) {
        return window[id];
    }
    return null;
}


// Read a cookie's value
function getcookie(cookiename) {
    var cookiestring=""+document.cookie;
    var index1=cookiestring.indexOf(cookiename);
    if (index1==-1 || cookiename=="") return ""; 
    var index2=cookiestring.indexOf(';',index1);
    if (index2==-1) index2=cookiestring.length; 
    return unescape(cookiestring.substring(index1+cookiename.length+1,index2));
}

