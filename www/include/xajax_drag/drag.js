///////////////////////////////////////////////////////////////////////////////
// Do not remove this notice.
// 
// Copyright 2001 by Mike Hall.
// See http://www.brainjar.com for terms of use.
// 
// Version:      v1.3
// Last Update:  2006-04-11
// 
// Large portions rewritten by Brandon Zehm <caspian@dotconf.net>
//   * Added support for the Konqueror web browser
//   * Save window position in global object (hash) window_position['el_name' + ('_x'|'_y')]
//   * Added several options documented below to dragStart()
//   
// DOCUMENTATION
//   Options for dragStart():
//       savePosition 0 | 1
//           Enable or disable saving the "window" position via xajax.
//           Default: 1 (enabled)
//       drag = both | vertical | horizontal
//           Permit movement on only the horizontal, vertical, or both axis.
//           Default: both
//       detectEdge = 0 | 1
//           Detect screen edges and don't allow elements to be "dropped" if
//           it's top-left corner is not currently viewable.
//           Default: 1 (enabled)
//       opacity = 0.0 - 1.0
//           Define the opacity of the element being dragged.  0 is totally 
//           transparent, 1 is full opacity.
//           Default: 0.7
// 
//*****************************************************************************
// 
// To make an element "draggable" just add a tag like this into it:
//   onmousedown="dragStart(event, 'id_name')
// For advanced options after the element_id pass a comma separated list
// of options and values like this:
//   onmousedown="dragStart(event, 'id_name', 'savePosition', 0, 'drag', 'vertical')"
// 
///////////////////////////////////////////////////////////////////////////////



// Determine browser and version.
function Browser() {

  var ua, s, i;

  this.isIE    = false;
  this.isNS    = false;
  this.isKONQ  = false;
  this.version = null;

  ua = navigator.userAgent;

  s = "MSIE";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isIE = true;
    this.version = parseFloat(ua.substr(i + s.length));
    return;
  }

  s = "Netscape6/";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isNS = true;
    this.version = parseFloat(ua.substr(i + s.length));
    return;
  }
  
  // Check to see if it's konqueror .. KHTML
  s = "KHTML/";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isKONQ = true;
    this.version = parseFloat(ua.substr(i + s.length));
    return;
  }
  
  // Treat any other "Gecko" browser as NS 6.1.
  s = "Gecko";
  if ((i = ua.indexOf(s)) >= 0) {
    this.isNS = true;
    this.version = 6.1;
    return;
  }
  
}

// Global browser object
var browser = new Browser();

// Global object to hold drag information.
// We only really need one since you can't drag more than one window at a time
var dragObj = new Object();
dragObj.zIndex = 0;




function dragStart(event, id) {
    var el;
    var x, y;
    
    // Define default options
    dragObj.options = new Object;
    dragObj.options['drag']            = 'both';
    dragObj.options['savePosition']    = 1;
    dragObj.options['opacity']         = 0.7;
    dragObj.options['detectEdge']      = 1;
    
    // Load in the options from the function call
    for (var i = 2; i < arguments.length; i += 2) {
        dragObj.options[arguments[i]] = arguments[i + 1];
    }
    
    // If an element id was given, find it. Otherwise use the element being clicked on.
    if (id)
        dragObj.elNode = document.getElementById(id);
    else {
        if (browser.isIE)
            dragObj.elNode = window.event.srcElement;
        if (browser.isNS || browser.isKONQ)
            dragObj.elNode = event.target;
        
        // If this is a text node, use its parent element.
        if (dragObj.elNode.nodeType == 3)
            dragObj.elNode = dragObj.elNode.parentNode;
    }
    
    // Get cursor position with respect to the page.
    if (browser.isNS) {
        x = event.clientX + window.scrollX;
        y = event.clientY + window.scrollY;
    }
    else if (browser.isIE || browser.isKONQ) {
        x = window.event.clientX + document.documentElement.scrollLeft
            + document.body.scrollLeft;
        y = window.event.clientY + document.documentElement.scrollTop
            + document.body.scrollTop;
    }
    
    // Make sure the element is positioned absolutly
    // (if it wasn't positioned absolutely before, it will move to the top left
    //  of the screen if we don't set it's position, so we do that too)
    dragObj.elNode.style.position = "absolute";
    
    // Find the element's current position .. calculate it if it's not already set.
    dragObj.elStartTop  = calcOffset(dragObj.elNode, 'offsetTop');
    dragObj.elStartLeft = calcOffset(dragObj.elNode, 'offsetLeft');
    
    // Save starting positions of cursor and element.
    dragObj.cursorStartX = x;
    dragObj.cursorStartY = y;
    if (isNaN(dragObj.elStartLeft)) dragObj.elStartLeft = 0;
    if (isNaN(dragObj.elStartTop))  dragObj.elStartTop  = 0;
    
    // Update element's z-index & opacity
    dragObj.zIndex = dragObj.elNode.style.zIndex;
    dragObj.elNode.style.zIndex = (dragObj.zIndex + 2);
    
    // Update the opacity .. at the end we do the CSS3 standard way, eventually browser detection shouldn't be needed.
    if (dragObj.options.opacity > 0 && dragObj.options.opacity < 1) {
        if (browser.isNS)
            dragObj.elNode.style.MozOpacity = dragObj.options.opacity;
        else if (browser.isKONQ)
            dragObj.elNode.style.KHTMLOpacity = dragObj.options.opacity;
        else if (browser.isIE)
            dragObj.elNode.style.filter = 'Alpha(opacity=' + (dragObj.options.opacity * 100) + ')';
        dragObj.elNode.style.opacity = dragObj.options.opacity;
    }
    
    // Capture mousemove and mouseup events on the page.
    if (browser.isNS || browser.isKONQ) {
        document.addEventListener("mousemove", dragGo,   true);
        document.addEventListener("mouseup",   dragStop, true);
        event.preventDefault();
    }
    else if (browser.isIE) {
        document.attachEvent("onmousemove", dragGo);
        document.attachEvent("onmouseup",   dragStop);
        window.event.cancelBubble = true;
        window.event.returnValue = false;
    }
}


function dragGo(event) {
    var x, y;
    
    // Get cursor position with respect to the page.
    if (browser.isIE || browser.isKONQ) {
        x = window.event.clientX + document.documentElement.scrollLeft
            + document.body.scrollLeft;
        y = window.event.clientY + document.documentElement.scrollTop
            + document.body.scrollTop;
    }
    if (browser.isNS) {
        x = event.clientX + window.scrollX;
        y = event.clientY + window.scrollY;
    }
    
    // Move drag element by the same amount the cursor has moved.
    if (dragObj.options.drag == 'both' || dragObj.options.drag == 'horizontal')
        dragObj.elNode.style.left = (dragObj.elStartLeft + x - dragObj.cursorStartX) + "px";
    if (dragObj.options.drag == 'both' || dragObj.options.drag == 'vertical')
        dragObj.elNode.style.top  = (dragObj.elStartTop  + y - dragObj.cursorStartY) + "px";
    
    if (browser.isIE) {
        window.event.cancelBubble = true;
        window.event.returnValue = false;
    }
    if (browser.isNS || browser.isKONQ)
        event.preventDefault();
}


function dragStop(event) {
    
    
    // Update element's z-index
    dragObj.elNode.style.zIndex = dragObj.zIndex;
    
    // Update element's opacity if we need to
    if (dragObj.options.opacity > 0 && dragObj.options.opacity < 1) {
        if (browser.isNS)
            dragObj.elNode.style.MozOpacity = '1.0';
        else if (browser.isKONQ)
            dragObj.elNode.style.KhtmlOpacity = '1.0';
        else if (browser.isIE)
            dragObj.elNode.style.filter = "Alpha(opacity=100)";
        dragObj.elNode.style.opacity = '1.0';
    }
    
    // Global object/hash to save current window positions in (for use outside of this library)
    if (typeof(window_position) == 'undefined')
        window_position = new Object();
    
    // Get the element's currnet coordinates
    var currentX = parseInt(dragObj.elNode.style.left);
    var currentY = parseInt(dragObj.elNode.style.top);
    
    // If the top-left corner is off the screen, get it's original position if we need to.
    if (dragObj.options.detectEdge == 1) {
        if (dragObj.options.drag == 'both' || dragObj.options.drag == 'horizontal') {
            if (currentX < 0 || currentX > parseInt(document.body.clientWidth)) {
                currentX = parseInt(dragObj.elStartLeft);
                dragObj.elNode.style.left = currentX + 'px';
            }
        }
        if (dragObj.options.drag == 'both' || dragObj.options.drag == 'vertical') {
            if (currentY < 0 || currentY > parseInt(document.body.clientHeight)) {
                currentY = parseInt(dragObj.elStartTop);
                dragObj.elNode.style.top  = currentY + 'px';
            }
        }
    }
    
    // Save it's position in a global variable for use outside this script
    window_position[dragObj.elNode.id + '_x'] = currentX;
    window_position[dragObj.elNode.id + '_y'] = currentY;
    
    // Stop capturing mousemove and mouseup events.
    if (browser.isIE) {
        document.detachEvent("onmousemove", dragGo);
        document.detachEvent("onmouseup",   dragStop);
    }
    if (browser.isNS || browser.isKONQ) {
        dragObj.elNode.style.position = "fixed";
        document.removeEventListener("mousemove", dragGo,   true);
        document.removeEventListener("mouseup",   dragStop, true);
    }
    
    // Brandon: We want to save the window's position in the PHP session
    // after it's been moved, so here we do an xajax call if it's available.
    if (dragObj.options.savePosition == 1)
        xajax_window_save_position(dragObj.elNode.id, window_position[dragObj.elNode.id + '_x'], window_position[dragObj.elNode.id + '_y']);
    
}

