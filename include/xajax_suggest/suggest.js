//////////////////////////////////////////////////////////////////////////////
// 
// Generic Auto-Suggest Javascript/Xajax Library
// Based loosly off of code from http://www.mininova.org/
// Author: Brandon Zehm <caspian@dotconf.net>
// 
// Version: 1.3
// Last Update: 2006-04-11
// 
// LICENSE:
// This script is licenced under the GNU LGPL, which basically means you
// can use it for any purpose whatsoever.
// 
// ABOUT:
// This script is intended to be used in conjunction with Xajax 
// (http://www.xajaxproject.org/) to provide a simple method for building
// "Google Suggest" style auto-suggest drop down boxes for text input fields.
// 
// USAGE:
// 
// 
// CHANGELOG:
//   v1.3
//     Updated calcOffset() to handle relative positioned objects.
// 
//////////////////////////////////////////////////////////////////////////////


// A few global variables
var suggestions = Array();
var storedSearchString = '';
var hoveredSuggestion = -1; 
var inter_exec = null;




//////////////////////////////////////////////////////////////////////////////
//  Function: suggest_setup (input_element_name, suggest_div_name)
//  Setup search suggest for the specified element-name.
//////////////////////////////////////////////////////////////////////////////
function suggest_setup(el_input, el_suggest) {
    
    // Find the input and suggest elemements
    var _input   = document.getElementById(el_input);
    var _suggest = document.getElementById(el_suggest);
    
    _input.setAttribute('autocomplete', 'off');
    
    // Setup some event handlers for the specified elements
    // We have to "hard code" el_input and el_suggest into these
    // anonymous functions or you can't have more than one suggest
    // form per web page.
    var code_keydown = "function(ev) { " +
                       "    try { searchKeyDown(event.keyCode, document.getElementById('"+el_input+"'), document.getElementById('"+el_suggest+"')); } " +
                       "    catch(e) { searchKeyDown(ev.keyCode, document.getElementById('"+el_input+"'), document.getElementById('"+el_suggest+"')); } " +
                       "};";
    var code_keyup   = "function(ev) { " +
                       "    try { searchKeyUp(event.keyCode, document.getElementById('"+el_input+"'), document.getElementById('"+el_suggest+"')); } " +
                       "    catch(e) { searchKeyUp(ev.keyCode, document.getElementById('"+el_input+"'), document.getElementById('"+el_suggest+"')); } " +
                       "};";
    var code_onblur  = "function() { " +
                       "    setTimeout(\"hoveredSuggestion = -1; _el = document.getElementById('"+el_suggest+"'); if (_el) _el.style.display = 'none';\", 200); " +
                       "};";
    
    eval("_input.onkeydown = " + code_keydown);
    eval("_input.onkeyup = " + code_keyup);
    eval("_input.onblur = " + code_onblur);
}




///////////////////////////////////////////////////////////////////////
//  Function: suggest_init (_input, _suggest)
//  Setup the suggest div.
///////////////////////////////////////////////////////////////////////
function suggest_init(_input, _suggest) {
    _suggest.style.top   = calcOffset(_input, 'offsetTop') + _input.offsetHeight - 1 + 'px';
    _suggest.style.left  = calcOffset(_input, 'offsetLeft') + 'px';
    _suggest.style.width = (_input.offsetWidth - 2) + 'px';
}



///////////////////////////////////////////////////////////////////////
//  Function: calcOffset(element, OffsetType)
//  Calculates the current offset, or position, in pixels of the 
//  specified element on the page.  OffsetType will usually be either
//  "offsetTop" or "offsetLeft".
///////////////////////////////////////////////////////////////////////
function calcOffset(_el, offsetType) {
    var calculatedOffset = 0;
    var start_id = _el.id;
    while (_el) {
        // Stop if we've reached an absolutly or relativly positioned element
        if (_el.id != start_id && (_el.style.position == 'absolute' || _el.style.position == 'relative') ) { break; }
        // Otherwise add the offset of the current element and go to our parent.
        calculatedOffset += _el[offsetType];
        _el = _el.offsetParent;
    }
    return calculatedOffset;
}




// Common Key Codes
//    8 = backspace
//    9 = tab (shouldn't ever get that right?)
//   13 = enter
//   17 = ctrl
//   18 = alt
//   27 = esc
//   38 = up arrow
//   40 = down arrow
//   46 = delete

///////////////////////////////////////////////////////////////////////
//  Function:    searchKeyDown (eventKeyCode, _input, _suggest)
//  Description: onKeyDown() handler for input fields
///////////////////////////////////////////////////////////////////////
function searchKeyDown(evKeyCode, _input, _suggest) {
    if(_suggest.style.display == 'block') {
        
        // If they pushed the up arrow key
        if (evKeyCode == 38 && hoveredSuggestion != -1) {
            hoveredSuggestion--;
            hoverSuggestion(hoveredSuggestion, hoveredSuggestion+1, _suggest);
            _input.value = suggestions[hoveredSuggestion];
            
            // If the last "up" key put the hoveredSuggestion at -1, they're at
            // the top of of the suggestion list, display their original query string.
            if(hoveredSuggestion == -1)
                _input.value = storedSearchString;
            
        }
        
        // If they pushed the down arrow key
        else if (evKeyCode == 40 && hoveredSuggestion < suggestions.length -1) {
            hoveredSuggestion++; 
            hoverSuggestion(hoveredSuggestion, hoveredSuggestion-1, _suggest);
            _input.value = suggestions[hoveredSuggestion];
        }
        
        // If they push <tab> or <enter> set the value to the current selection
        // and hide the suggestion box.
        else if (evKeyCode == 9 || evKeyCode == 13) {
            // If it was <tab> and there isn't a selected suggestion, use the first one
            if (hoveredSuggestion == -1 && evKeyCode == 9)
                hoveredSuggestion = 0;
            if (hoveredSuggestion != -1 && suggestions[hoveredSuggestion] != 'No suggestions...' && suggestions[hoveredSuggestion] != 'Searching...')
                _input.value = storedSearchString = suggestions[hoveredSuggestion];
            _suggest.style.display = 'none';
            _input.focus();
        }
        
        // If they push any other key, put them back in the input form but leave the suggestions up
        else if(evKeyCode != 38 && evKeyCode != 40 && evKeyCode != 13)
            hoveredSuggestion = -1;
        
    }
    
}



///////////////////////////////////////////////////////////////////////
//  Function:    searchKeyUp (eventKeyCode, _input, _suggest)
//  Description: onKeyUp() handler for input fields
///////////////////////////////////////////////////////////////////////
function searchKeyUp(evKeyCode, _input, _suggest) {
    
    // If the input value has changed and they're typing (i.e. they didn't just push enter, up or down, etc...)
    if(_input.value != storedSearchString && hoveredSuggestion == -1 && evKeyCode != 13 && evKeyCode != 38 && evKeyCode != 40) {
        
        storedSearchString = _input.value;
        
        // If the suggestion box is visible, hide it until we have results.
        if (_suggest.style.display == 'block') {
            _suggest.style.display = 'none';
        }
        
        // Make an xajax call to get suggestions (if there are still at least 3 input characters)
        // (if there were results, the php script will send back a command to display them)
        script = "if (document.getElementById('"+_input.id+"').value.length > 0) { " + 
                 "    suggestions = Array('Searching...'); " + 
                 "    suggest_display('"+_input.id+"', '"+_suggest.id+"'); " + 
                 "    xajax_suggest('" + _input.id + "', '"+escape(_input.value)+"', '"+_input.id+"', '"+_suggest.id+"'); " + 
                 "}";
        
        // Wait a while after the last keystroke until we actually make the xajax call
        if (inter_exec) clearInterval(inter_exec);
        inter_exec = setInterval(script + "clearInterval(inter_exec);", 300);
    }
    
    // If they push "esc", "backspace", or they've erased all input, hide the suggestion menu
    else if ((_input.value == '' || evKeyCode == 27 || evKeyCode == 8) && _suggest.style.display == 'block') {
        _suggest.style.display = 'none';
        hoveredSuggestion = -1;
    }
    
    // Anytime they press enter make sure that we cancel any pending suggest calls
    if (evKeyCode == 13) {
        clearInterval(inter_exec);
        _suggest.style.display = 'none';
        hoveredSuggestion = -1;
    }
    
}




///////////////////////////////////////////////////////////////////////
// Function:
//     suggest_display (el_input, el_suggest)
// 
// Description:
//     Display the list of suggestions in the "suggestions" array 
//     in the suggest element just below the input element.
//     
///////////////////////////////////////////////////////////////////////
function suggest_display(el_input, el_suggest) {
    // Find the input and suggest elemements
    _input   = document.getElementById(el_input);
    _suggest = document.getElementById(el_suggest);
    
    // If the elements don't exist, just exit
    if ((!_input) || (!_suggest)) { return; }
    
    _suggest.style.display = 'block';
    _suggest.innerHTML = '';
    hoveredSuggestion = -1;
    var j = 0;
    
    suggest_init(_input, _suggest);
    suggestion_width = _input.offsetWidth - 6;
    
    // HACK for our website?:
    if (msIE) { suggestion_width = _input.offsetWidth - 2; }
    
    // If there aren't any suggestions display "No suggestions..."
    if(suggestions.length == 0) {
        // _suggest.style.display = 'none';
        // FIXME: we may want a global variable to display "No results..." instead.
        suggestions = Array('No suggestions...');
    }
    
    // Display the suggestions!
    for (var i=0; i<suggestions.length; i++) {
        // FIXME: after a click we need to refocus the input element we just selected a value for
        _suggest.innerHTML += '<div style="width:' + suggestion_width + 'px;" ' +
                              'onMouseOver="hoverSuggestion(' + i + ', hoveredSuggestion, document.getElementById(\'' + _suggest.id + '\'))" ' + 
                              'onClick="document.getElementById(\'' + _input.id + '\').value = this.innerHTML;' +
                                       'hoveredSuggestion = -1;' + 
                                       'document.getElementById(\'' + _suggest.id + '\').style.display = \'none\'">' + 
                              suggestions[i] + '</div>';
    }
}



// Colors the div that has the mouse over it a different color
function hoverSuggestion(newHover, oldHover, _suggest) {
    if(oldHover != -1) {
        _suggest.getElementsByTagName('div').item(oldHover).className = '';
    }
    if(newHover != -1) { 
        _suggest.getElementsByTagName('div').item(newHover).className = 'hovered'; 
        hoveredSuggestion = newHover;
    }
}


