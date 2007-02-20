//////////////////////////////////////////////////////////////////////////////
// 
// Author: Brandon Zehm <caspian@dotconf.net>
// 
// WebWin Tool Tip Javascript Library
// (i.e. DomTT Lite)
// Originally based off of code from the DomTT project: 
//   http://www.mojavelinux.com/projects/domtooltip/
// 
// Version: 1.3
// Last Update: 2006-04-11
// 
// LICENSE:
// This script is licenced under the GNU LGPL, which basically means you
// can use it for any purpose whatsoever.  Full details at: 
//   http://www.gnu.org/copyleft/lesser.html
// 
// ABOUT:
// 
// USAGE:
// 
// CHANGELOG:
// 
//////////////////////////////////////////////////////////////////////////////


var wwTTobj = new Object;
wwTTobj.autoId = 0;
wwTTobj.options = new Object;


//////////////////////////////////////////////////////////////////////////////
// Function: wwTT(this, event, option, value, [option, value] ... )
// 
// Description:
//     Displays a tool tip "popup" using options provided
//     Usage based loosly off of the open source domTT package.
//     
// Example:
//     wwTT(this, event, '');
//////////////////////////////////////////////////////////////////////////////
function wwTT(in_this, in_event) {
    wwTTobj.autoId++;
    wwTTobj.options.parent = document.body;
    wwTTobj.document = wwTTobj.options.parent.ownerDocument || wwTTobj.options.parent.document;
    
    // make sure in_event is set (for IE, some cases we have to use window.event)
    if (typeof(in_event) == 'undefined') 
        in_event = window.event;
    
    // FIXME: this is using the browser variable defined when drag.js is loaded!
    // Get event position
    if (browser.isNS) {
        wwTTobj.cursorX = in_event.clientX + window.scrollX;
        wwTTobj.cursorY = in_event.clientY + window.scrollY;
    }
    else if (browser.isIE || browser.isKONQ) {
        wwTTobj.cursorX = in_event.clientX + document.documentElement.scrollLeft + document.body.scrollLeft;
        wwTTobj.cursorY = in_event.clientY + document.documentElement.scrollTop + document.body.scrollTop;
    }
    
    // make sure we have nothing higher than the body element
    if (in_this.nodeType && in_this.nodeType != document.DOCUMENT_NODE)
        wwTTobj.options.owner = in_this;
    
    // make sure the owner has a unique id
    if (!wwTTobj.options.owner.id)
        wwTTobj.options.owner.id = '_wwTT_owner_' + wwTTobj.autoId;
    
    // Define default options
    wwTTobj.options['id']         = 'wwTT_' + wwTTobj.autoId;
    wwTTobj.options['content']    = '';              // Content to display in the tool-tip
    wwTTobj.options['delay']      = 1000;            // Microsecond delay before displaying the tool-tip
    wwTTobj.options['detectEdge'] = 1;               // Avoid placing tool-tips outside the browser boundaries
    wwTTobj.options['direction']  = 'southeast';     // Position the tool-tip this direction from the mouse
    wwTTobj.options['javascript'] = '';              // Execute this javascript after tool-tip creation
    wwTTobj.options['lifetime']   = 5000;            // auto expire tool-tip after X microseconds
    wwTTobj.options['styleClass'] = 'wwTT_Classic';  // any valid css class name
    wwTTobj.options['type']       = 'greasy';        // Tool-tip type: greasy, velcro, or static
    wwTTobj.options['width']      = '';              // Manual tool-tip width (in pixels)
    wwTTobj.options['x']          = 0;               // Manual tool-tip x position
    wwTTobj.options['y']          = 0;               // Manual tool-tip y position
    wwTTobj.options['zIndex']     = 5;               // Manual tool-tip zIndex
    
    // Load in the options from the function call
    for (var i = 2; i < arguments.length; i += 2) {
        wwTTobj.options[arguments[i]] = arguments[i + 1];
    }
    
    // Setup an onMouseOut handler for the owner element
    if (wwTTobj.options.type == 'greasy') {
        wwTTobj.options.owner.onmouseout = 
            function(ev) {
                clearTimeout(wwTTobj.timer);
                removeElement(wwTTobj.lastID);
            };
    }
    else if (wwTTobj.options.type == 'velcro' || wwTTobj.options.type == 'static') {
        wwTTobj.options.owner.onmouseout = 
            function(ev) {
                clearTimeout(wwTTobj.timer);
            };
        wwTTobj.options.lifetime = 0;
    }
    
    // Set a timer to display the popup .. if the tool-tip doesn't already exist
    if (!el(wwTTobj.options.id))
        wwTTobj.timer = setTimeout('wwTT_create();', wwTTobj.options.delay);
}




//////////////////////////////////////////////////////////////////////////////
// Function: wwTT_create(this, event, option, value)
// 
// Description:
//     Creates & displays a tool-tip "popup" using options provided
//     
// Example:
//     wwTT_create();
//////////////////////////////////////////////////////////////////////////////
function wwTT_create() {
    
    // Make sure the last tool-tip (if any) is removed
    if (typeof(wwTTobj.lastID) != 'undefined')
        removeElement(wwTTobj.lastID);
    wwTTobj.lastID = wwTTobj.options.id;
    
    // Create the tool-tip div
    var tooltip = wwTTobj.options.parent.appendChild(wwTTobj.document.createElement('div'));
    tooltip.style.visibility = 'hidden';
    tooltip.id = wwTTobj.options.id;
    tooltip.className = wwTTobj.options.styleClass;
    tooltip.style.zIndex = wwTTobj.options.zIndex;
    tooltip.style.position = 'absolute';
    // Bug workaround: "position fixed" below is a workaround for this bug: https://bugzilla.mozilla.org/show_bug.cgi?id=167801
    if (navigator.userAgent.indexOf('Gecko/') != -1)
        tooltip.style.position = 'fixed';
    tooltip.style.left = '0px';
    tooltip.style.top = '0px';
    if (wwTTobj.options.content != '')
        tooltip.innerHTML = wwTTobj.options.content;
    
    // adjust the width if specified
    if (wwTTobj.options.width != '')
        tooltip.style.width = parseInt(wwTTobj.options.width) + 'px';
    
    // Position the tool-tip
    wwTT_position(tooltip.id);
    
    // If it's a "velcro" tool-tip setup it's onMouseOut handler
    // and disable the onMouseOut on the owner element.  We only
    // remove the tool-tip if the event involved an element that's
    // not one of our own children.
    if (wwTTobj.options.type == 'velcro') {
        tooltip.onmouseout = 
            function(ev) {
                if (typeof(ev) == 'undefined') ev = event;
                var tag = browser.isIE ? 'toElement' : 'relatedTarget';
                if (!wwTT_isDescendantOf(this, ev[tag])) 
                    removeElement(this.id);
            };
        wwTTobj.options.owner.onmouseout = function(ev) { return true; };
    }
    
    // If it's a "static" tool-tip, disable the onMouseOut on the 
    // owner element.  We don't remove the element.
    if (wwTTobj.options.type == 'static') {
        wwTTobj.options.owner.onmouseout = function(ev) { return true; };
    }
    
    // Display the tool-tip
    tooltip.style.visibility = 'visible';
    
    // Execute any javascript the browser asked us to
    if (wwTTobj.options.javascript != '')
        eval(wwTTobj.options.javascript);

}






//////////////////////////////////////////////////////////////////////////////
// Function: wwTT_position(element_id)
// 
// Description:
//     Positions an already existing tool-tip
//     
// Example:
//     wwTT_position(element_id);
//////////////////////////////////////////////////////////////////////////////
function wwTT_position(element_id) {
    tooltip = el(element_id);
    
    // If an absolute position is specified use it
    if (parseInt(wwTTobj.options.x) != 0 && parseInt(wwTTobj.options.y) != 0) {
        tooltip.style.left = wwTTobj.options.x + 'px';
        tooltip.style.top  = wwTTobj.options.y + 'px';
        return true;
    }
    
    // Otherwise calculate our location from the cursor's position
    // FIXME: uses "browser" variable from drag.js
    var mouseHeight = browser.isIE ? 13 : 19;
    var offsetX = 0;
    var offsetY = 0;
    var padding = 10;
    switch (wwTTobj.options.direction) {
        case 'northeast':
            offsetX = 0;
            offsetY = 0 - tooltip.offsetHeight - padding;
        break;
        case 'northwest':
            offsetX = 0 - tooltip.offsetWidth;
            offsetY = 0 - tooltip.offsetHeight - padding;
        break;
        case 'north':
            offsetX = 0 - parseInt(tooltip.offsetWidth/2);
            offsetY = 0 - tooltip.offsetHeight - padding;
        break;
        case 'southwest':
            offsetX = 0 - tooltip.offsetWidth;
            offsetY = 0 + mouseHeight;
        break;
        case 'southeast':
            offsetX = 0;
            offsetY = 0 + mouseHeight;
        break;
        case 'south':
            offsetX = 0 - parseInt(tooltip.offsetWidth/2);
            offsetY = 0 + mouseHeight;
        break;
        case 'east':
            offsetX = padding;
            offsetY = 0 - parseInt(tooltip.offsetHeight/2);
        break;
        case 'west':
            offsetX = 0 - tooltip.offsetWidth - padding;
            offsetY = 0 - parseInt(tooltip.offsetHeight/2);
        break;
    }
    
    // Calculate new X & Y positions
    var newX = wwTTobj.cursorX + offsetX;
    var newY = wwTTobj.cursorY + offsetY;
    
    // Detect screen edges and adjust the tool-tip position if needed
    if (wwTTobj.options.detectEdge == 1) {
        if (newY < 0) newY = 0;
        if (newX < 0) newX = 0;
        if ( (newX + tooltip.offsetWidth) > parseInt(document.body.clientWidth))
            newX = document.body.clientWidth - tooltip.offsetWidth;
        if ( (newY + tooltip.offsetHeight) > parseInt(document.body.clientHeight))
            newY = document.body.clientHeight - tooltip.offsetHeight;
    }
    
    // Position the tool-tip
    tooltip.style.left = newX + 'px';
    tooltip.style.top  = newY + 'px';
}



//////////////////////////////////////////////////////////////////////////////
// Function: wwTT_isDescendantOf(element, potential_child)
// 
// Description:
//     Returns true if potential_child is a descendant of element
//////////////////////////////////////////////////////////////////////////////
function wwTT_isDescendantOf(_parent, _object) {
    var cur_el = _object;
    while ( (cur_el) && (cur_el != document.body) ) {
        // If the current element is the same as the parent element, return true.
        if(cur_el == _parent)
            return true;
        
        // Otherwise go up a node
        cur_el = cur_el.parentNode ? cur_el.parentNode : false;
    }
    return false;
}


