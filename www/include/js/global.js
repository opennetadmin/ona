//
// These javascript functions get loaded for all web pages.
// Add and modify functions with care!
//

// MP: I"ve turned this off as it is of course, annoying. one of these days I'll find a better solution
// Catch the back button(and window close) and stop the user.  only works in IE and Firefox for now.
//window.onbeforeunload = function() {
//    return("FYI, There is no need to use the back button, simply select from the trace bar.");
//}


// Toggle the display style for table rows of a given 'tablename'
// based on an attribute setting of 'type'
function toggle_table_rows(tablename,type) {
    mytable=el(tablename)
    tr=mytable.getElementsByTagName('tr')
    for (i=0;i<tr.length;i++){
      if (tr[i].getAttribute(type)){
        if (tr[i].style.display=='none'){tr[i].style.display = '';}
        else {tr[i].style.display = 'none';}
      }
    }
}


// over-ride the alert method only if this a newer browser.
// Older browser will see standard alerts
if(document.getElementById) {
    window.alert = function(txt) {
        createONAAlert(txt);
    }
}

function createONAAlert(txt) {
    // shortcut reference to the document object
    d = document;

    // if the alertContainer object already exists in the DOM, bail out.
    if(el('alertContainer')) return;

    // create the alertContainer div as a child of the BODY element
    mObj = d.getElementsByTagName("body")[0].appendChild(d.createElement("div"));
    mObj.id = "alertContainer";
    mObj.className = "alertContainer";
    mObj.style.height = document.documentElement.scrollHeight + "px";
    mObj.onclick = function() {
        removeElement("alertBox");
        removeElement("alertContainer");
        return false;
    }

    // create the DIV that will be the alert
    alertObj = d.getElementsByTagName("body")[0].appendChild(d.createElement("div"));
    alertObj.id = "alertBox";
    alertObj.className = "alertBox";
    // MSIE doesnt treat position:fixed correctly, so this compensates for positioning the alert
    if(d.all && !window.opera) alertObj.style.top = document.documentElement.scrollTop + "px";
    alertObj.style.left = (d.documentElement.scrollWidth - alertObj.offsetWidth)/2 + "px";

    h1 = alertObj.appendChild(d.createElement("h1"));
    h1.appendChild(d.createTextNode("OpenNetAdmin Alert!"));
    h1.onclick = function() {
        removeElement("alertBox");
        removeElement("alertContainer");
        return false;
    }

    msg = alertObj.appendChild(d.createElement("p"));
    msg.innerHTML = txt;

    btn = alertObj.appendChild(d.createElement("button"));
    btn.id = "closeBtn";
    btn.appendChild(d.createTextNode("Close"));
    btn.type = "button";
    btn.focus();
    btn.onclick = function() {
        removeElement("alertBox");
        removeElement("alertContainer");
        return false;
    }
}




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
            el('ptr_info_'+window_name).innerHTML = flipip(el('set_ip_'+window_name).value) +'.in-addr.arpa ' + el('set_ttl').value + ' IN PTR ' + el('set_hostname_'+window_name).value + '.' + el('set_domain_'+window_name).value;}
        else { el('ptr_info_'+window_name).innerHTML = ''; }
    }
    if (el('record_type_select').value=='CNAME') {
        el('info_'+window_name).innerHTML = el('set_hostname_'+window_name).value + '.' + el('set_domain_'+window_name).value + ' ' + el('set_ttl').value + ' IN ' + el('record_type_select').value + ' ' + el('set_a_record_'+window_name).value;
    }
    if (el('record_type_select').value=='NS') {
        el('info_'+window_name).innerHTML = el('set_domain_'+window_name).value + ' ' + el('set_ttl').value + ' IN ' + el('record_type_select').value + ' ' + el('set_a_record_'+window_name).value;
    }
}


// Found this on http://www.redips.net/
// Clear elements of a form that is passed in
function clearElements(el){
    var object = new Array();
    object[0] = document.getElementById(el).getElementsByTagName('input');
    object[1] = document.getElementById(el).getElementsByTagName('textarea');
    object[2] = document.getElementById(el).getElementsByTagName('select');
  var type = null;
  for (x=0; x<object.length; x++){
    for (y=0; y<object[x].length; y++){
      type = object[x][y].type
      switch(type){
        case "text":
        case "textarea":
        case "password":
          object[x][y].value = "";
          break;
        case "radio":
        case "checkbox":
          object[x][y].checked = "";
          break;
        case "select-one":
          object[x][y].options[0].selected = true;
          break;
        case "select-multiple":
          for (z=0; z<object[x][y].options.length; z++){
            object[x][y].options[z].selected = false;
          }
        break;
      }
    }
  }
}




/* Setup mouse handlers for the "Start" button 
   These are used by the ona main menu only */
function ona_menuTT(menu_name, menu_id) {
   var _button = el(menu_name);
    _button.onmouseover =
        function(ev) {
            if (!ev) ev = event;
            /* Get info about the button */
            var button_top    = calcOffset(el(menu_name+'_name'), 'offsetTop');
            var button_left   = calcOffset(el(menu_name+'_name'), 'offsetLeft');
            var button_height = el(menu_name+'_name').offsetHeight;
            el('trace_history').style.display = 'none';
            var wsname='FALSE';
            if (el('work_space')) {
                var wsname=el('work_space').getAttribute('wsname');
            }
            /* Create the tool-tip menu */
            wwTT(this, ev,
                 'id', menu_id,
                 'type', 'velcro',
                 'x', button_left,
                 'y', button_top + button_height + 1,
                 'width', 200,
                 'delay', 0,
                 'lifetime', 0,
                 'styleClass', 'wwTT_ona_menu',
                 'javascript', 'el(\''+menu_id+'\').style.visibility = \'hidden\'; xajax_window_submit(\'menu_control\', \'menu_name=>'+menu_name+',id=>'+menu_id+',wsname=>'+wsname+'\', \'menu\');'
            );
            el(menu_name+'_name').className='menu-title-highlight';
        };
}

// This will close down the menu bar and any remaining menu lists
function ona_menu_closedown() {
    el('menu-apps-item').style.paddingBottom='3px';
    el('menu_bar_top').style.display = 'none';
    el('trace_history').style.display = '';
    removeElement(wwTTobj.lastID);
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


/**
*
*  MD5 (Message-Digest Algorithm)
*  http://www.webtoolkit.info/
*
**/

function make_md5(string) {

    function RotateLeft(lValue, iShiftBits) {
        return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
    }

    function AddUnsigned(lX,lY) {
        var lX4,lY4,lX8,lY8,lResult;
        lX8 = (lX & 0x80000000);
        lY8 = (lY & 0x80000000);
        lX4 = (lX & 0x40000000);
        lY4 = (lY & 0x40000000);
        lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
        if (lX4 & lY4) {
            return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
        }
        if (lX4 | lY4) {
            if (lResult & 0x40000000) {
                return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
            } else {
                return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
            }
        } else {
            return (lResult ^ lX8 ^ lY8);
        }
    }

    function F(x,y,z) { return (x & y) | ((~x) & z); }
    function G(x,y,z) { return (x & z) | (y & (~z)); }
    function H(x,y,z) { return (x ^ y ^ z); }
    function I(x,y,z) { return (y ^ (x | (~z))); }

    function FF(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function GG(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function HH(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function II(a,b,c,d,x,s,ac) {
        a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
        return AddUnsigned(RotateLeft(a, s), b);
    };

    function ConvertToWordArray(string) {
        var lWordCount;
        var lMessageLength = string.length;
        var lNumberOfWords_temp1=lMessageLength + 8;
        var lNumberOfWords_temp2=(lNumberOfWords_temp1-(lNumberOfWords_temp1 % 64))/64;
        var lNumberOfWords = (lNumberOfWords_temp2+1)*16;
        var lWordArray=Array(lNumberOfWords-1);
        var lBytePosition = 0;
        var lByteCount = 0;
        while ( lByteCount < lMessageLength ) {
            lWordCount = (lByteCount-(lByteCount % 4))/4;
            lBytePosition = (lByteCount % 4)*8;
            lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
            lByteCount++;
        }
        lWordCount = (lByteCount-(lByteCount % 4))/4;
        lBytePosition = (lByteCount % 4)*8;
        lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
        lWordArray[lNumberOfWords-2] = lMessageLength<<3;
        lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
        return lWordArray;
    };

    function WordToHex(lValue) {
        var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
        for (lCount = 0;lCount<=3;lCount++) {
            lByte = (lValue>>>(lCount*8)) & 255;
            WordToHexValue_temp = "0" + lByte.toString(16);
            WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
        }
        return WordToHexValue;
    };

    function Utf8Encode(string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    };

    var x=Array();
    var k,AA,BB,CC,DD,a,b,c,d;
    var S11=7, S12=12, S13=17, S14=22;
    var S21=5, S22=9 , S23=14, S24=20;
    var S31=4, S32=11, S33=16, S34=23;
    var S41=6, S42=10, S43=15, S44=21;

    string = Utf8Encode(string);

    x = ConvertToWordArray(string);

    a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;

    for (k=0;k<x.length;k+=16) {
        AA=a; BB=b; CC=c; DD=d;
        a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
        d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
        c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
        b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
        a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
        d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
        c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
        b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
        a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
        d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
        c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
        b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
        a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
        d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
        c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
        b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
        a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
        d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
        c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
        b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
        a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
        d=GG(d,a,b,c,x[k+10],S22,0x2441453);
        c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
        b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
        a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
        d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
        c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
        b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
        a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
        d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
        c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
        b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
        a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
        d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
        c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
        b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
        a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
        d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
        c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
        b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
        a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
        d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
        c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
        b=HH(b,c,d,a,x[k+6], S34,0x4881D05);
        a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
        d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
        c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
        b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
        a=II(a,b,c,d,x[k+0], S41,0xF4292244);
        d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
        c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
        b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
        a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
        d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
        c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
        b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
        a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
        d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
        c=II(c,d,a,b,x[k+6], S43,0xA3014314);
        b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
        a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
        d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
        c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
        b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
        a=AddUnsigned(a,AA);
        b=AddUnsigned(b,BB);
        c=AddUnsigned(c,CC);
        d=AddUnsigned(d,DD);
    }

    var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);

    return temp.toLowerCase();
}
