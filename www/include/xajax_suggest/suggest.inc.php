<?php




// These are the functions we'll be exposing via Xajax
$xajax->registerFunction("suggest");

// for when and if I switch to xajax 0.5.x
//$xajax->register(XAJAX_FUNCTION,"suggest");




//////////////////////////////////////////////////////////////////////////////
// Xajax Server
// Generic wrapper for the various auto-suggest drop-downs.
// Passes $q, $el_input, and $el_suggest to a function
// you need to write called "suggest_$type".  If you are using suggest.js
// the and javascript suggest_setup() function, $type will be be the name
// of the input field we're building a suggestion list for.
// Example:
//   <input id="test" type="text">
//   <div id="suggest_test" class="suggest"></div>
//   <script> suggest_setup('test', 'suggest_test'); </script>
//   ## Now in PHP you'd create a function called "suggest_test()"
// 
// $type       = suggest type
// $q          = query string
// $el_input   = input element id
// $el_suggest = suggest element id
// 
//////////////////////////////////////////////////////////////////////////////
function suggest($type, $q, $el_input, $el_suggest) {
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    if (!$type or !$q or !$el_input or !$el_suggest) { return($response->getXML()); }
    
    // Make sure the requested function is defined
    $function = 'suggest_' . $type;
    if (function_exists($function)) {
        return($function($q, $el_input, $el_suggest));
    }
    
    return($response->getXML());
}





?>