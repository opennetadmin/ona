<?php
// DON'T put whitespace at the beginning or end of this file!!!



///////////////////////////////////////////////////////////////////////
//  Function: vlan_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    campus=STRING
//    name=STRING
//    number=NUMBER
//
//  Output:
//    Adds an vlan into the database called 'name' that is part of
//    the campus 'campus'.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = vlan_add('campus=test&name=blah&number=1');
///////////////////////////////////////////////////////////////////////
function vlan_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => vlan_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['campus'] and $options['name'] and $options['number']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

vlan_add-v{$version}
Adds a vlan into the database assigned to the specified campus

  Synopsis: vlan_add [KEY=VALUE] ...

  Required:
    campus=STRING|ID       Campus name or row ID
    name=STRING            Name of new VLAN
    number=NUMBER          VLAN number to be assigned

\n
EOM

        ));
    }


    // The formatting rule on vlan names/campus names is all upper and trim it
    $options['name'] = strtoupper(trim($options['name']));
    $options['campus'] = strtoupper(trim($options['campus']));

    if (is_numeric($options['campus']))
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('id' => $options['campus']));
    if(!$rows)
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('name' => $options['campus']));

    if ($status or !$rows) {
        $self['error'] = "ERROR => Unable to find campus";
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Debugging
    printmsg("DEBUG => Using VLAN campus: {$campus['name']}", 3);

    // check that the number option is a number
    if (!is_numeric($options['number'])) {
        printmsg("DEBUG => The VLAN number ({$options['number']}) must be numeric!",3);
        $self['error'] = "ERROR => The VLAN number ({$options['number']}) must be numeric!";
        return(array(3, $self['error'] . "\n"));
    }

    // Validate that there isn't already an vlan on this campus with this vlan number
    list($status, $rows, $record) =  ona_get_vlan_record(array('vlan_campus_id'    => $campus['id'],
                                                                'number' => $options['number']));
    if ($status or $rows) {
        printmsg("DEBUG => The vlan campus ({$campus['name']}) already has a vlan with the number ({$options['number']})!",3);
        $self['error'] = "ERROR => The vlan campus {$campus['name']} already has a vlan with the number {$options['number']}!";
        return(array(3, $self['error'] . "\n"));
    }


    // Validate that there isn't already an vlan
    list($v_status, $v_rows, $v_record) =  ona_get_vlan_record(array('vlan_campus_id'    => $campus['id'],
                                                                      'name' => $options['name']));
    if ($v_status or $v_rows) {
        printmsg("DEBUG => The vlan ({$options['name']}) already exists on campus ({$campus['name']})!",3);
        $self['error'] = "ERROR => The vlan {$options['name']} already exists on campus {$campus['name']}!";
        return(array(3, $self['error'] . "\n"));
    }


    // Check permissions
    if (!auth('vlan_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next ID for the new vlan
    $id = ona_get_next_id('vlans');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'],0);
        return(array(5, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new VLAN: $id", 3);

    // Add the vlan
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'vlans',
            array(
                'id'                  => $id,
                'number'              => $options['number'],
                'name'                => $options['name'],
                'vlan_campus_id'      => $campus['id']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => vlan_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => VLAN ADDED: {$options['name']} to {$campus['name']}.";
    printmsg($self['error'],0);
    return(array(0, $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: vlan_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    vlan=ID
//
//  Output:
//    Deletes an vlan from the database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = vlan_add('vlan=19328');
///////////////////////////////////////////////////////////////////////
function vlan_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => vlan_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['vlan'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

vlan_del-v{$version}
Deletes an vlan from the database

  Synopsis: vlan_del [KEY=VALUE] ...

  Required:
    vlan=ID             ID of the vlan to delete

  Optional:
    commit=[yes|no]     commit db transaction (no)

\n
EOM

        ));
    }


    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // If the vlan provided is numeric, check to see if it's an vlan
    if (is_numeric($options['vlan'])) {

        // See if it's an vlan_id
        list($status, $rows, $vlan) = ona_get_vlan_record(array('id' => $options['vlan']));
    }

        if (!$vlan['id']) {
            printmsg("DEBUG => Unable to find VLAN ({$options['vlan']})!",3);
            $self['error'] = "ERROR => Unable to find VLAN ({$options['vlan']})!";
            return(array(2, $self['error'] . "\n"));
        }


    list($status, $rows, $network) = db_get_records($onadb, 'subnets', array('vlan_id' => $vlan['id']), '' ,0);
    if ($rows != 0) {
        printmsg("DEBUG => This VLAN ({$vlan['name']}) is in use by {$rows} network(s)!",3);
        $self['error'] = "ERROR => This VLAN ({$vlan['name']}) is in use by {$rows} network(s)!";
        return(array(6, $self['error'] . "\n" .
                        "INFO  => Please dis-associate those networks from this vlan before deleting.\n"));
    }

    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('vlan_del')) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'vlans', array('id' => $vlan['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => vlan_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(4, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => VLAN DELETED: {$vlan['name']}";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }


    list($status, $rows, $campus) = ona_get_vlan_campus_record(array('id' => $vlan['vlan_campus_id']));

    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

    NAME:   {$vlan['name']}
    NUMBER: {$vlan['number']}
    CAMPUS: {$campus['name']}


EOL;

    return(array(6, $text));

}









///////////////////////////////////////////////////////////////////////
//  Function: vlan_modify (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    vlan=ID                        vlan ID
//    set_name=NAME                  change vlan name
//    set_number=NUMBER              change vlan number
//    set_campus=NAME or ID          change campus the vlan belongs to
//
//  Output:
//    Updates a vlan record in the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = vlan_modify('vlan=23452&set_name=default');
///////////////////////////////////////////////////////////////////////
function vlan_modify($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => vlan_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or (!$options['vlan']) or !($options['set_name'] or $options['set_number'] or $options['set_campus']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

vlan_modify-v{$version}
Modifies an vlan entry in the database

  Synopsis: vlan_modify [KEY=VALUE] ...

  Where:
    vlan=ID                        vlan ID

  Update:
    set_name=NAME                  change vlan name
    set_number=NUMBER              change vlan number
    set_campus=NAME or ID          change campus the vlan belongs to

\n
EOM
        ));
    }



    // Load the record we will be modifying
    list($status, $rows, $vlan) = ona_get_vlan_record(array('id' => $options['vlan']));

    // Validate that we got a record back, or return an error
    if (!$vlan['id']) {
        printmsg("DEBUG => The VLAN ID specified ({$options['vlan']}) does not exist!",3);
        $self['error'] = "ERROR => The VLAN ID specified, {$options['vlan']}, does not exist!";
        return(array(2, $self['error'] . "\n"));
    }

    printmsg("DEBUG => Found VLAN: {$vlan['name']}", 3);


    // This variable will contain the updated info we'll insert into the DB
    $SET = array();


    // If they are specifying a new name, process it.
    if ($options['set_name']) {
        // Validate that there isn't already an vlan with this name
        $options['set_name'] = strtoupper(trim($options['set_name']));

        list($status, $rows, $record) =  db_get_records($onadb, 'vlans', "vlan_campus_id = {$vlan['vlan_campus_id']} AND name LIKE '{$options['set_name']}' AND number != {$vlan['number']}");

        if ($status or $rows) {
            printmsg("DEBUG => The VLAN ({$options['set_name']}) already exists on this campus!",3);
            $self['error'] = "ERROR => The VLAN {$options['set_name']} already exists on this campus!";
            return(array(4, $self['error'] . "\n"));
        }
        $SET['name'] = $options['set_name'];
    }

    if ($options['set_number']) {
        // Validate that there isn't already an vlan on this campus with this vlan number
        list($status, $rows, $record) =  db_get_records($onadb, 'vlans', "vlan_campus_id = {$vlan['vlan_campus_id']} AND number = {$options['set_number']} AND name NOT LIKE '{$vlan['name']}'");
        if ($status or $rows) {
            printmsg("DEBUG => The VLAN with the number ({$options['set_number']}) already exists on this campus!",3);
            $self['error'] = "ERROR => The vlan with the number {$options['set_number']} already exists on this campus!";
            return(array(3, $self['error'] . "\n"));
        }
        // Add the new info to $SET
        $SET['number'] = $options['set_number'];
    }

    // FIXME: yes I'm lazy.. test that the new campus does not have the vlan name or number already on it.

    // If they are changing the campus the vlan points to, process it
    if ($options['set_campus']) {
        $options['set_campus'] = strtoupper(trim($options['set_campus']));

        if (is_numeric($options['set_campus']))
            list($status, $rows, $record) =  ona_get_vlan_campus_record(array('id' => $options['set_campus']));
        if( !array_key_exists('id',$record) )
            list($status, $rows, $record) =  ona_get_vlan_campus_record(array('name' => $options['set_campus']));

        // Make sure that worked - or return an error
        if (!$record['id']) {
            printmsg("DEBUG => The campus ({$options['set_campus']}) does not exist!",3);
            $self['error'] = "ERROR => The campus specified, {$options['set_campus']}, does not exist!";
            return(array(5, $self['error'] . "\n"));
        }

        // test that the new campus does not have the vlan name or number already on it.
        // only check if the campus has changed
        if($record['id'] != $vlan['vlan_campus_id'])    {

            // build where clause for checking the new campus for the vlan name/number
            $where='';
            $OR='';
            if(array_key_exists('number',$SET)) {
                $where .= " number = {$SET['number']} ";
                $OR=" OR ";
            }
            if(array_key_exists('name',$SET))
                $where .= "{$OR} name LIKE '{$SET['name']}' ";

            list($status, $rows, $new_campus_record) =  db_get_records($onadb, 'vlans', "vlan_campus_id = {$record['id']} AND ({$where})");
            if($rows > 0) {
                printmsg("DEBUG => The campus ({$options['set_campus']}) already contains this VLAN name or number ({$SET['name']} {$SET['number']})!",3);
                $self['error'] = "ERROR => The campus specified, {$options['set_campus']}, already contains this VLAN name or number ({$SET['name']} {$SET['number']})!";
                return(array(7, $self['error'] . "\n"));
            }
        }

        // Add the new info to $SET
        $SET['vlan_campus_id'] = $record['id'];
    }

    // Check permissions
    if (!auth('vlan_modify')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }


    // Update the record
    list($status, $rows) = db_update_record($onadb, 'vlans', array('id' => $vlan['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => vlan_modify() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Get the VLAN record after updating (logging)
    list($status, $rows, $new_vlan) = ona_get_vlan_record(array('id' => $options['vlan']));
    list($status, $rows, $campus) =  ona_get_vlan_campus_record(array('id' => $new_vlan['vlan_campus_id']));

$text = <<<EOL

    NAME:   {$new_vlan['name']}
    NUMBER: {$new_vlan['number']}
    CAMPUS: {$campus['name']}


EOL;
    // Return the success notice
    $renamed = '';
    if($new_vlan['name'] != $vlan['name']) $renamed .= "{$vlan['name']} => {$new_vlan['name']} ";
    if($new_vlan['number'] != $vlan['number']) $renamed .= "VLAN Num {$vlan['number']} => {$new_vlan['number']} ";
    if($new_vlan['vlan_campus_id'] != $vlan['vlan_campus_id']) $renamed .= "Campus ID {$vlan['vlan_campus_id']} => {$new_vlan['vlan_campus_id']}";
    $self['error'] = "INFO => VLAN UPDATED: {$renamed}";



    return(array(0, $self['error'] . "\n {$text}"));
}














// DON'T put whitespace at the beginning or end of this file!!!
?>