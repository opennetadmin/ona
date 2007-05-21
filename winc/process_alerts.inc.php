<?

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

// TODO: leaving this here in case we do something with tacacs in the future.
//     // Determine if their tacacs account is about to expire
//     list($status, $rows, $tac) = db_get_record($onadb, 'tacacs_users', "USERID LIKE '{$_SESSION[$conf['wiki_name']]['auth']['user']}'");
//
//     // If you find a tacacs entry with an enablepasswd
//     if ($tac['enablepasswd']) {
//         // calculate the time difference from now to expiration in days
//         $timedifference = (strtotime($tac['expiration']) - time()) / 84600;
//
//         // hard coded to one week out.  display icon if less than 7 days left till expire
//         if ($timedifference > 7) {
//             $js .= "if (el('tacacs_expire')) {el('tacacs_expire').style.visibility = 'hidden';}";
//         }
//         else {
//             $js .= "if (el('tacacs_expire')) {el('tacacs_expire').style.visibility = 'visible';}";
//         }
//     }


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