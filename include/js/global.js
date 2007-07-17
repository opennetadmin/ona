// 
// These javascript functions get loaded for all web pages.
// Add and modify functions with care!
//


// Add a "trim" function to Javascript's string object type
String.prototype.trim = function() {
    // Strip leading and trailing white-space
    return this.replace(/^\s*|\s*$/g, "");
}

// flip the IP that is passed and return it
function flipip(value) {
   var octet = value.split(".");
   var text = octet[3] + '.' + octet[2] + '.' + octet[1] + '.' + octet[0];
   return text;
}



// Update the info container for the dns record form
// Used to display to the user what the dns record would look like that they are building in the edit record form
function updatednsinfo(window_name) {
    // If it is the A type
    if (el('record_type_select').value=='A') {
        el('info_'+window_name).innerHTML = el('set_hostname_'+window_name).value + '.' + el('set_domain_'+window_name).value + ' ' + el('set_ttl').value + ' IN ' + el('record_type_select').value + ' ' + el('set_ip_'+window_name).value;
        // If it is a ptr update
        if (el('set_auto_ptr').checked==true) {
            el('ptr_info_'+window_name).innerHTML = flipip(el('set_ip_'+window_name).value) +'IN-ADDR.ARPA ' + el('set_ttl').value + ' IN PTR ' + el('set_hostname_'+window_name).value + '.' + el('set_domain_'+window_name).value;}
        else { el('ptr_info_'+window_name).innerHTML = ''; }
    }
    if (el('record_type_select').value=='CNAME') {
        el('info_'+window_name).innerHTML = el('set_hostname_'+window_name).value + '.' + el('set_domain_'+window_name).value + ' ' + el('set_ttl').value + ' IN ' + el('record_type_select').value + ' ' + el('set_a_record_'+window_name).value;
    }
}




//
// Hide or display an object
//
function toggleBox(id) {
    if(document.layers) {    //NN4+
        if (document.layers[id].visibility == "show")
            document.layers[id].visibility = "hide";
        else
            document.layers[id].visibility = "show";
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



// Synonym for document.getElementById()
function el(id) {
    // NN4+
    if(document.layers)
        return document.layers[id];
    // Gecko (NN6, Firefox, IE 5+)
    else if (document.getElementById)
        return document.getElementById(id);
    // IE 4
    else if(document.all)
        return document.all[id];
    // ?
    else if (window[id])
        return window[id];
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

// Internal function for setcookie
function getexpirydate(nodays){
    var UTCstring;
    var Today = new Date();
    var nomilli=Date.parse(Today);
    Today.setTime(nomilli+nodays*24*60*60*1000);
    return Today.toUTCString();
}

// Set a cookie
function setcookie(name,value,duration_days) {
    var cookiestring=name+"="+escape(value)+";EXPIRES="+getexpirydate(duration_days);
    document.cookie=cookiestring;
}

