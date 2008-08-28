<?php

//////////////////////////////////////////////////////////////////////////////
// Function: ws_submit($input)
//
// Description:
//     Inserts dynamic content into a tool-tip popup.
//     $form is a string array that should look something like this:
//       "tooltip=>something,id=>element_id,something_id=>143324"
//////////////////////////////////////////////////////////////////////////////
function ws_process_alerts_submit($window_name, $form='') {
    global $conf, $self, $onadb, $tip_style;
    global $font_family, $color, $style, $images;
    $html = $js = '';

    // If an array in a string was provided, build the array and store it in $form
    $form = parse_options_string($form);

    printmsg("DEBUG => Processing Alerts:", 5);

// FIXME: this code is called from html_desktop.inc.php.. however it is failing to process for some reason
// The intent of this code is to be called to display a "message waiting" type icon in the top menu bar.


    // Check for messages that begin with SYS_ in the table_name_ref column
    list($status, $rows, $msg) = db_get_record($onadb, 'messages', "table_name_ref LIKE 'SYS_%'");

    if ($rows) {
        $js .= "if (el('sys_alert')) {el('sys_alert').style.visibility = 'visible';}";
    }
    else {
        $js .= "if (el('sys_alert')) {el('sys_alert').style.visibility = 'hidden';}";
    }



    $response = new xajaxResponse();
    if ($js) { $response->addScript($js); }
    return($response->getXML());
}








?>