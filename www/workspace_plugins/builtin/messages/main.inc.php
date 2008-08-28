<?php

$title_left_html = 'Messages';
$title_right_html = '';

// START MESSAGES BOX
// $kind is a reference directly to the table that contains the item
// we are displaying to the user.
// It is possible that you can have the same ID in multiple tables, currently.

require_once('winc/tooltips.inc.php');

// Determine if this is a host or a subnet we are dealing with
if (is_numeric($record['subnet_type_id'])) {
    $kind = 'subnets';
    list($lineshtml, $linesjs) = get_message_lines_html("table_id_ref = {$record['id']} AND table_name_ref LIKE '{$kind}'");

}
else {
    $kind = 'hosts';
    list($lineshtml, $linesjs) = get_message_lines_html("table_id_ref = {$record['id']} AND table_name_ref LIKE '{$kind}'");
}


if ($lineshtml) {
    $modbodyhtml .= $lineshtml;
} else {
    // dont display anything
    $modbodyhtml = '';
}




?>