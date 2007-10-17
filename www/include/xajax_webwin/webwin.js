//////////////////////////////////////////////////////////////////////////////
// 
// Author: Brandon Zehm <caspian@dotconf.net>
// 
// WebWin Javascript Library
// 
// Version: 1.0
// Last Update: 2007-03-19
// 
// LICENSE:
// This script is licenced under the GNU LGPL, which basically means you
// can use it for any purpose whatsoever.  Full details at: 
//   http://www.gnu.org/copyleft/lesser.html
// 
// ABOUT:
//   NOTICE: This file depends on suggest.js from the xajax-suggest library.
//   NOTICE: This file depends on global.js from Brandon's other websites.
// 
// USAGE:
//   Public Functions:
//      toggle_window() - Displays or hides a window (creates it if it doesn't exist)
//      focus_window()  - "Focuses" the window name specified
//   
//   Private Functions:
//      initialize_window
//   
// CHANGELOG:
//   2007-03-19 - Brandon Zehm - Small updates, added this header.
//////////////////////////////////////////////////////////////////////////////

// Global Variables
var window_default_zindex = 2; // This should match the setting in the .css and .php file!







//////////////////////////////////////////////////////////////////////////////
// Function: toggle_window(window_name)
// 
// Description:
//     Hides or shows the "Window" named window_name if it exists, 
//     otherwise it makes an xajax call to create that window.
//     NOTE: Uses toggleBox() from global.js
//     NOTE: Uses el() from global.js
//     
// Example:
//     toggle_window('window_name');
//////////////////////////////////////////////////////////////////////////////
function toggle_window(el_name) {
    var _el = el(el_name);
    
    // If the element doesn't exist, make an xajax call to create it.
    if (!_el) {
        xajax_window_open(el_name);
        return;
    }
    
    // Initialize window position
    initialize_window(el_name);
    
    // Display/hide the box
    toggleBox(el_name);
    
    // If the window is visible, focus it.
    if (_el.style.display == 'block')
        focus_window(_el.id);
}







//////////////////////////////////////////////////////////////////////////////
// Function: initialize_window(window_name)
// 
// Description:
//     Sets the initial position of new "Window" div's
//     NOTE: Uses calcOffset() from suggest.js
//     NOTE: Uses el() from global.js
//     FIXME: This function could currently have site-specific definitions!
//     
// Example:
//     initialize_window('window_name');
//////////////////////////////////////////////////////////////////////////////
function initialize_window(el_name) {
    var _el = el(el_name);
    
    // Global object/hash to save current window positions in (for use outside of this library)
    if (typeof(window_position) == 'undefined')
        window_position = new Object();
    
    // If there is no position defined for this "window", let's set something.
    if (typeof(window_position[el_name+'_x']) == 'undefined') {
        window_position[el_name+'_x'] = 0;
        window_position[el_name+'_y'] = 0;
        
        switch (el_name) {
            
            // Set the default position of the asearch div .. just to the right of the main_menu
            case "asearch":
                window_position[el_name+'_y'] = calcOffset(el('content_table'), 'offsetTop');
                window_position[el_name+'_x'] = calcOffset(el('menu-apps-button'), 'offsetLeft');
                break;
            
            // Set the default position of the work_space div..
            case "work_space":
                window_position[el_name+'_y'] = calcOffset(el('content_table'), 'offsetTop');
                window_position[el_name+'_x'] = calcOffset(el('content_table'), 'offsetLeft');
                break;
            
            // Just choose a place randomly up to 300x50 pixels away from the "window_container" box
            default : 
                window_position[el_name+'_y'] = calcOffset(el('window_container'), 'offsetTop')  + 20 + (el('window_container').offsetHeight) + Math.floor(Math.random()*70);
                window_position[el_name+'_x'] = calcOffset(el('window_container'), 'offsetLeft') + 50 + Math.floor(Math.random()*150);
                
        }
    }
    
    // Make sure it's position is absolute (it should be already)
    _el.style.position   = 'absolute';
    _el.className        = 'window';
    _el.style.zIndex     = window_default_zindex;
    
    
    // The "window" will have some position defined.. either by drag.js
    // or initialize_window(), so we now position the box where it belongs.
    _el.style.top   = window_position[el_name+'_y'] + 'px';
    _el.style.left  = window_position[el_name+'_x'] + 'px';
    
}









//////////////////////////////////////////////////////////////////////////////
// Function: focus_window(window_name)
// 
// Description:
//     "Focuses" the window name specified (i.e. raise it above other windows)
//     
// Example:
//     focus_window('window_name');
//////////////////////////////////////////////////////////////////////////////
function focus_window(el_name) {
    var _el = el(el_name);
    var _parent = el('window_container');
    
    // Basically we loop through every window.  If we find any windows
    // that have a higher zIndex than the default we set it to the default.
    // When we find the window we're focusing we set it's zIndex to default + 1.
    nodes = _parent.childNodes;
    for (var i=0; i<nodes.length; i++) {
        if (nodes[i].id == _el.id)
            nodes[i].style.zIndex = window_default_zindex + 1;
        else if (nodes[i].style.zIndex > window_default_zindex)
            nodes[i].style.zIndex = window_default_zindex;
    }
}
