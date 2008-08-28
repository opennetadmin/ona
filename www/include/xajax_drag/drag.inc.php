<?php
//////////////////////////////////////////////////////////////////////////////
// Xajax Enabled Drag
// 
// FIXME: requires that site support $conf['html_headers'] 
// FIXME: ?? requires $_SESSION already be active
//
//////////////////////////////////////////////////////////////////////////////


$xajax->registerFunction("window_save_position");




//////////////////////////////////////////////////////////////////////////////
// If there are any saved window positions, set the variables in the
// current web page so they are displayed in the right place.
//////////////////////////////////////////////////////////////////////////////
if (isset($_SESSION['window_position']) and is_array($_SESSION['window_position'])) {
    $conf['html_headers'] .= "<script type=\"text/javascript\">\n";
    $conf['html_headers'] .= "    // Global object/hash to save current window positions in\n";
    $conf['html_headers'] .= "    if (typeof(window_position) == 'undefined')\n";
    $conf['html_headers'] .= "        var window_position = new Object();\n";
    foreach (array_keys($_SESSION['window_position']) as $element) {
    $conf['html_headers'] .= "    window_position['{$element}'] = '{$_SESSION['window_position'][$element]}';\n";
    }
    $conf['html_headers'] .= "</script>\n";
}






//////////////////////////////////////////////////////////////////////////////
// Xajax Server
// Saves the position for an element/window named "$element" to the $_SESSION
// These window locations are loaded into subsequent page loads by the code above.
//////////////////////////////////////////////////////////////////////////////
function window_save_position($element, $x, $y) {
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$element or !$x or !$y) { return($response->getXML()); }
    
    // Search the DB
    if ($x > 0 and $y > 0 and $x < 3000 and $y < 3000) {
        $_SESSION['window_position']["{$element}_x"] = $x;
        $_SESSION['window_position']["{$element}_y"] = $y;
    }
    
    return($response->getXML());
}





?>