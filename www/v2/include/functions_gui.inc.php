<?php
/*
Functions used by the GUI interface
*/

// Takes the URL reqest and processes it
function process_urlnew() {
  global $pagetitle, $workspace;
  
  // process ws
  if ( array_key_exists('ws',$_REQUEST) ) {
    // Check validity of the value

// should only be [a-zA-Z0-9-_]
// should exist as a workspace file

    // set a new page title
    $pagetitle = "ONA - ${_REQUEST['ws']}";
    $workspace = $_REQUEST['ws'];
    
    
  }
}

// This function calls the code that opens a workspace window
function open_workspace() {

}

?>
