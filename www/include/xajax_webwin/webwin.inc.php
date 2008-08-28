<?php
//////////////////////////////////////////////////////////////////////////////
//
// Documentation:  Before using this you MUST have a div in your web page like this one:
// <div id="window_container"></div>
// All windows will be a child of this div.  Also initial position of new
// windows will be based on the location of this div on the page.
//
// FIXME: Shouldn't rely on site specific variables like $style, $color, etc.
// FIXME: Requires javascript function RemoveElement() from global.js
// FIXME: Uses the "padding" style from the global stylesheet
//
// CHANGELOG:
//   2006-02-28 - Updated window_submit() - submitted functions must be prefixed with "ws_"
//   2006-03-28 - Fix new window creation to prevent "flicker"
//
//////////////////////////////////////////////////////////////////////////////
global $color;
$color['window_title_bg']           = '#69A6DE';
$color['window_title_font']         = '#F0F0F0';
$color['window_tab_active_bg']      = '#E5E3F0';
$color['window_tab_inactive_bg']    = '#FFFFFF';
$color['window_content_bg']         = '#F2F2F2';


// These are the functions we'll be exposing via Xajax
$xajax->registerFunction("window_open");
$xajax->registerFunction("window_submit");






//////////////////////////////////////////////////////////////////////////////
// Xajax Server
// Generic xajax "server" to opens a new "window" in the current web page.
//
// Is typically called from the javascript toggle_window() function, but
// can by called by any other PHP function assuming the web client is
// expecting an xajax response.
//
// If only a window_name is provided it will attempt to find/load the html
// to be inserted into the new window.  If no "header" or "footer" is provided
// a default header/footer will be used to provide the new "window" with
// a standardized title-bar and close button.
//
// Valid array elements for $window[]
//   title
//   header
//   html
//   footer
//   js (extra javascript to run after window is built)
//
//////////////////////////////////////////////////////////////////////////////
function window_open($window_name, $window=array()) {
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$window_name) { return($response->getXML()); }

    // Variables that might be used in building HTML :: FIXME this is site specific!
    global $font_family, $color, $style, $images;

    // Load $window_html and $window_title from an include file
    $file = window_find_include($window_name);
    if ($file) { require_once($file); }

    // Setup a header and footer for the new window
    if (!$window['header']) {
        $window['header'] = <<<EOL

        <!-- This wrapper table is so that internal tables can be set to 100% width and they won't stretch the box too wide. -->
        <table id="{$window_name}_table" cellspacing="0" border="0" cellpadding="0">
        <tr>
        <td>

            <!-- Window bar and close button -->
            <table id="{$window_name}_title_table" style="background-color: {$color['window_title_bg']};" width="100%" cellspacing="0" border="0" cellpadding="0">
            <tr>

                <td id="{$window_name}_title"
                    width="99%"
                    align="left"
                    nowrap="true"
                    onMouseDown="focus_window('$window_name'); dragStart(event, '{$window_name}');"
                    style="cursor: move;
                           color: {$color['window_title_font']};
                           white-space: nowrap;
                           font-weight: bold;
                           text-align: left;
                           padding: 2px 4px;">{$window['title']}</td>

                <td id="{$window_name}_title_r"
                    align="right"
                    nowrap="true"
                    style="color: {$color['window_title_font']};
                           white-space: nowrap;
                           text-align: right;
                           padding: 2px 4px;"><span id="{$window_name}_title_help"></span>&nbsp;<a title="Close window" style="cursor: pointer;" onClick="removeElement('{$window_name}');"><img src="{$images}/icon_close.gif" border="0" /></a></td>

            </tr>
            </table>
EOL;
    }
    if (!$window['footer']) {
        $window['footer'] = <<<EOL
        </td>
        </tr>
        </table>
EOL;
    }

    // Create a new div to display the content in
    $response->addScript("removeElement('{$window_name}');");
    $response->addCreate("window_container", "div", $window_name);
    $response->addScript(
        "initialize_window('{$window_name}');" .
        "el('$window_name').style.display = 'none';" .
        "el('$window_name').style.visibility = 'hidden';" .
        "el('$window_name').onclick = function(ev) { focus_window(this.id); };"
    );
    $response->addAssign($window_name, "innerHTML", $window['header'] . $window['html'] . $window['footer']);
    $response->addScript("toggle_window('{$window_name}');" . $window['js']);

    // Send an XML response to the web browser
    return($response->getXML());
}




//////////////////////////////////////////////////////////////////////////////
// Xajax Server
// Function:
//     window_submit ($window_name, [$form_data], [$function])
//
// Description:
//     Generic wrapper to handle window form submits.
//     Is typically called when a client pushes a submit-like button in
//     their web application.
//
// Input:
//     $window_name   The name of the "window" submitting data
//     $form[]        An optional array/string containing the (form?) data
//                    being submitted.
//                    This will often be generated by using this javascript
//                    xajax call:  xajax.getFormValues('form_id')
//     $function      The optional name of a PHP function to pass the first two
//                    parameters to.  For security reasons, the actual function
//                    called will be "ws_{$function}".
//                    If a function name is not specified, the default is
//                      "ws_{$window_name}_submit" or "ws_submit"
//
//////////////////////////////////////////////////////////////////////////////
function window_submit($window_name, $form='', $function='') {
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$window_name or !$form) { return($response->getXML()); }
    $js = "";

    printmsg("DEBUG => webwin_submit() Window: {$window_name} Function: {$function} Form: {$form}", 1);

    // If a function name wasn't provided, we look for a function called:
    //   $window_name . '_submit'
    if (!$function) {
        $function = "{$window_name}_submit";
    }
    $function = 'ws_' . $function;

    // If the function exists, run it and return it's output (an xml response)
    if (function_exists($function)) { return($function($window_name, $form)); }

    // Try looking for the same function in an include file
    $file = window_find_include($window_name);
    if ($file) { require_once($file); }

    // Now see if our function is available...
    if (function_exists($function)) { return($function($window_name, $form)); }

    // Ok, I couldn't find anything to do.. just return an empty XML response
    printmsg("NOTICE => webwin_submit() invalid function called! Window: {$window_name} Function: {$function}", 0);
    return($response->getXML());
}







//////////////////////////////////////////////////////////////////////////////
// Function:
//     window_find_include ($window_name)
//
// Description:
//     Internally used function that searches several places for an include
//     file containing information about a "window" named $window_name.
//     Returns the filename if one is found.
//
//////////////////////////////////////////////////////////////////////////////
function window_find_include($window_name) {
    if (!$window_name) { return(''); }

    $file = '';

    // Check the usual directories, now inlucdes the local plugins as well.
    // local plugins should override the builtin stuff if they are named the same.
    $directories = array('.',
                         './local/plugins/'.$window_name,
                         './winc',
                         './inc',
                        );

    // Find the file if at all possible!
//MP: disabled this as we do not use _win_ anywhere
//     foreach ($directories as $directory) {
//         $file = "{$directory}/_win_{$window_name}.inc.php";
//         if (is_file($file)) {
//             return($file);
//         }
//     }

    // If we still didn't find it, try it without the '_win_' in the file prefix
    // but with a .inc.php extension.
    foreach ($directories as $directory) {
        $file = "{$directory}/{$window_name}.inc.php";
        if (is_file($file)) {
            return($file);
        }
    }

    // If we still have not found it, lets just try the windowname as the file itself
    if (is_file('.'.$window_name)) {
        return('.'.$window_name);
    }

    // Couldn't find it :|
    return(FALSE);
}





?>