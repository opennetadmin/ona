
// This should match the setting in the .css and .php file!
var window_default_zindex = 2;

// Function to hide/show a moveable ipdb "window"
// Uses toggleBox() and el() from global.js
// Shows/hides the window if it exits, otherwise
// makes an xajax call to load/create the window.
function toggle_window(el_name) {
    var _el = el(el_name);
    
    // If the element doesn't exist, we need to make an xajax
    // call to create the "window".
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



// Function to set initial position of various elements
// Uses calcOffset() from suggest.js
// 
// NOTE: This function will have site-specific definitions in it!
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
            
            // Set the default position of the main_menu div .. just below the "window_container" box
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
    
    // Bug workaround: "position fixed" below is a workaround for this bug: https://bugzilla.mozilla.org/show_bug.cgi?id=167801
    if (navigator.userAgent.indexOf('Gecko/') != -1)
        _el.style.position = 'fixed';
    
    // The "window" will have some position defined.. either by drag.js
    // or initialize_window(), so we now position the box where it belongs.
    _el.style.top   = window_position[el_name+'_y'] + 'px';
    _el.style.left  = window_position[el_name+'_x'] + 'px';
    //alert('x=' + window_position[el_name+'_x'] + ' y=' + window_position[el_name+'_y']);
    
}


// Function to "focus" the window specified
function focus_window(el_name) {
    var _parent = el('window_container');
    
    // Basically we loop through every window.  If we find any windows
    // that have a higher zIndex than the default we set it to the default.
    // When we find the window we're focusing we set it's zIndex to default + 1.
    nodes = _parent.childNodes;
    for (var i=0; i<nodes.length; i++) {
        if (nodes[i].id == el_name)
            nodes[i].style.zIndex = window_default_zindex + 1;
        else if (nodes[i].style.zIndex > window_default_zindex)
            nodes[i].style.zIndex = window_default_zindex;
    }
}
