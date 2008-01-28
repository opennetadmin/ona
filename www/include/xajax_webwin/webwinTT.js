//////////////////////////////////////////////////////////////////////////////
// Author: Brandon Zehm <caspian@dotconf.net>
//
// Version: 1.5
// Last Update: 2007-03-19
//
// LICENSE:
// This script is licenced under the GNU LGPL, which basically means you
// can use it for any purpose whatsoever.  Full details at:
//   http://www.gnu.org/copyleft/lesser.html
//
// ABOUT:
//   WebWin Tool Tip Javascript Library (i.e. DomTT Lite)
//   Originally based off of code from the DomTT project:
//     http://www.mojavelinux.com/projects/domtooltip/
//
// USAGE:
//   Simply include this file, and webwin.css in your html headers.
//   This file has no other dependancies on any PHP scripts, and can be used
//   stand-alone without the other webwin stuff.
//   NOTICE: This file depends on global.js from Brandon's other websites.
//   NOTICE: This file depends on drag.js from the xajax_drag library.
//
//   Public Functions:
//     wwTT() - Create a new tool-tip
//
//   Private Functions:
//     wwTT_create() - Build & display the current tool-tip
//     wwTT_position - (Re)position the current tool-tip
//     wwTT_isDescendantOf() - Determine if one element is a child of another
//
//   Basic example:
//     <span onMouseOver="wwTT(this, event, 'content', 'See spot run..');">Hi there!</span>
//
// CHANGELOG:
//   v1.5 - 2007-03-19 - Brandon Zehm
//       * Add workaround for Firefox/Mozilla bug #167801
//       * Fixed some bugs with the greasy tool-tip option
//       * Enabled and documented the lifetime option
//       * Added a lot of documentation
//
//   v1.4 - 2006-08-22 - Brandon Zehm
//       * Make sure wwTT_position() always returns a value
//
//   v1.3 - 2006-04-11 - Brandon Zehm
//       * Initial public release
//////////////////////////////////////////////////////////////////////////////

// Global variables
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
// Input:
//     There are several options that can be passed in, here is a list.
//       OPTION        DEFAULT          DESCRIPTION
//       ------        -------          -----------
//       id            (auto)           Override the default element id for the new tool-tip
//       content       ''               Text or html to display in the tool-tip
//       type          'greasy'         Tool-tip type: greasy, velcro, or static
//                                        greasy: tool-tip removed when mouse leaves parent
//                                        velcro: tool-tip removed when the mouse is moved into and then outside of the tool-tip
//                                        static: tool-tip is not removed (unless another tool-tip is created)
//       delay         1000             Millisecond delay before displaying the tool-tip
//       lifetime      0                Millisecond delay before expiring the tool-tip
//                                        greasy: expires after being displayed for X milliseconds
//                                        static: expires after being displayed for X milliseconds
//                                        velcro: expires after the mouse has been into and then outside of the tool-tip for X milliseconds
//       detectEdge    1                Avoid placing tool-tips outside the browser boundaries
//       direction     'southeast'      Position the tool-tip this direction from the mouse cursor:
//                                        northeast, northwest, north, southwest, southeast, south, east, west
//       javascript    ''               Execute this javascript after tool-tip creation
//       styleClass    'wwTT_Classic'   Any valid css class name
//       width         ''               Manual tool-tip width (in pixels)
//       x             0                Manual absolute tool-tip x position
//       y             0                Manual absolute tool-tip y position
//       zIndex        5                Manual tool-tip zIndex
//
// Example:
//     wwTT(this, event, 'content', 'sample content!', 'type', 'velcro');
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
    wwTTobj.options['type']       = 'greasy';        // Tool-tip type: greasy, velcro, or static
    wwTTobj.options['delay']      = 1000;            // Millisecond delay before displaying the tool-tip
    wwTTobj.options['lifetime']   = 0;               // Millisecond delay before expiring the tool-tip (0 disables)
    wwTTobj.options['detectEdge'] = 1;               // Avoid placing tool-tips outside the browser boundaries
    wwTTobj.options['direction']  = 'southeast';     // Position the tool-tip this direction from the mouse
    wwTTobj.options['javascript'] = '';              // Execute this javascript after tool-tip creation
    wwTTobj.options['styleClass'] = 'wwTT_Classic';  // Any valid css class name
    wwTTobj.options['width']      = '';              // Manual tool-tip width (in pixels)
    wwTTobj.options['x']          = 0;               // Manual tool-tip x position
    wwTTobj.options['y']          = 0;               // Manual tool-tip y position
    wwTTobj.options['zIndex']     = 5;               // Manual tool-tip zIndex

    // Load in the options from the function call
    // (this is a sweet way to pass key=value pairs into a function!)
    for (var i = 2; i < arguments.length; i += 2)
        wwTTobj.options[arguments[i]] = arguments[i + 1];

    // Setup an onMouseOut handler for the owner element
    wwTTobj.options.owner.onmouseout =
        function(ev) {
            clearTimeout(wwTTobj.timer_create);
            // If a greasy tool-tip was actually created before we leave the parent element, remove it!
            if (wwTTobj.lastID == wwTTobj.options.id && wwTTobj.options.type == 'greasy') {
                clearTimeout(wwTTobj.timer_destroy);
                removeElement(wwTTobj.lastID);
            }
        };

    // If the tool-tip doesn't already exist, set a timer to display the popup
    if (!el(wwTTobj.options.id))
        wwTTobj.timer_create = setTimeout('wwTT_create();', wwTTobj.options.delay);
}






//////////////////////////////////////////////////////////////////////////////
// Function: wwTT_create()
//
// Description:
//     Creates & displays a tool-tip "popup" using options found in the
//     global object wwTTobj.
//     Private function.
//
// Example:
//     wwTT_create();
//////////////////////////////////////////////////////////////////////////////
function wwTT_create() {

    // Clear any old lifetime timers .. they're not needed now ;)
    if (typeof(wwTTobj.timer_destroy) != 'undefined')
        clearTimeout(wwTTobj.timer_destroy);

    // Make sure the last tool-tip (if any) is removed
    if (typeof(wwTTobj.lastID) != 'undefined')
        removeElement(wwTTobj.lastID);

    // Copy a few things for later reference
    wwTTobj.lastID = wwTTobj.options.id;
    wwTTobj.lastLifetime = wwTTobj.options.lifetime;

    // Create the tool-tip div
    var tooltip = wwTTobj.options.parent.appendChild(wwTTobj.document.createElement('div'));
    tooltip.style.visibility = 'hidden';
    tooltip.id = wwTTobj.options.id;
    tooltip.className = wwTTobj.options.styleClass;
    tooltip.style.zIndex = wwTTobj.options.zIndex;
    tooltip.style.position = 'absolute';
    // FIXME: This should be removed someday (year) when Firefox/Mozilla no longer have this bug
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

    // For "greasy" tool-tips
    //   * Setup a lifetime destroy counter if it's not 0
    if (wwTTobj.options.type == 'greasy') {
        if (wwTTobj.options.lifetime != 0)
            wwTTobj.timer_destroy = setTimeout("removeElement('" + wwTTobj.options.id + "');", wwTTobj.options.lifetime);
    }


    // For "velcro" tool-tips
    //   * Setup it's onMouseOut handler
    //     * Setup a lifetime countdown timer to destroy the tool-tip
    //   * Add a onMouseOver handler that cancels any pending lifetime "destroy" timers
    //   * Disable the owner's onMouseOut handler (just to keep things clean in the DOM)
    if (wwTTobj.options.type == 'velcro') {
        // Setup a timer to remove the element if the event didn't come from one of our children
        tooltip.onmouseout =
            function(ev) {
                if (typeof(ev) == 'undefined') ev = event;
                var tag = browser.isIE ? 'toElement' : 'relatedTarget';
                if (!wwTT_isDescendantOf(this, ev[tag]))
                    wwTTobj.timer_destroy = setTimeout("removeElement('" + wwTTobj.lastID + "');", wwTTobj.lastLifetime);
            };

        // Cancel the count-down timer if the mouse comes into the tool-tip
        tooltip.onmouseover =
            function(ev) {
                clearTimeout(wwTTobj.timer_destroy);
            };
        wwTTobj.options.owner.onmouseout = function(ev) { return true; };
    }


    // For "static" tool-tips
    //   * Disable the owner's onMouseOut handler (just to keep things clean in the DOM)
    //   * Setup a lifetime destroy counter if it's not 0
    if (wwTTobj.options.type == 'static') {
        wwTTobj.options.owner.onmouseout = function(ev) { return true; };
        if (wwTTobj.options.lifetime != 0)
            wwTTobj.timer_destroy = setTimeout("removeElement('" + wwTTobj.options.id + "');", wwTTobj.options.lifetime);
    }

    // Display the tool-tip
    tooltip.style.visibility = 'visible';

    // Execute any javascript queued to be run
    if (wwTTobj.options.javascript != '')
        eval(wwTTobj.options.javascript);

}







//////////////////////////////////////////////////////////////////////////////
// Function: wwTT_position(element_id)
//
// Description:
//     Positions an already existing tool-tip
//     Private function.
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
    return true;
}






//////////////////////////////////////////////////////////////////////////////
// Function: wwTT_isDescendantOf(element, potential_child)
//
// Description:
//     Returns true if potential_child is a descendant of element.
//     Private function.
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
