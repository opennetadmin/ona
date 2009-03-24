<?php
// DON'T put whitespace at the beginning or end of this file!!!


///////////////////////////////////////////////////////////////////////
//  Function: vlan_campus_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    campus=STRING
//
//  Output:
//    Adds an vlan campus into the database called 'name'
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = vlan_campus_add('name=blah');
///////////////////////////////////////////////////////////////////////
function vlan_campus_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => vlan_campus_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['name']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

vlan_campus_add-v{$version}
Adds a vlan campus into the database

  Synopsis: vlan_campus_add [KEY=VALUE] ...

  Required:
    name=STRING            Campus name

\n
EOM

        ));
    }


    // The formatting rule on vlan campus names is all upper and trim it
    $options['name'] = strtoupper(trim($options['name']));

    // check to see if the campus already exists
    list($status, $rows, $campus) = ona_get_vlan_campus_record(array('name' => $options['name']));

    if ($status or $rows) {
        printmsg("DEBUG => The vlan campus {$options['name']} already exists!",3);
        $self['error'] = "ERROR => The vlan campus {$options['name']} already exists!";
        return(array(3, $self['error'] . "\n"));
    }


    // Check permissions
    if (!auth('vlan_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next ID for the new alias
    $id = ona_get_next_id('vlan_campuses');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'], 0);
        return(array(5, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new VLAN Campus: $id", 3);

    // Add the record
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'vlan_campuses',
            array(
                'id'                  => $id,
                'name'                => $options['name']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => vlan_campus_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'],0);
        return(array(6, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => VLAN Campus ADDED: {$options['name']}";
    printmsg($self['error'],0);
    return(array(0, $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: vlan_campus_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    name=ID
//
//  Output:
//    Deletes an vlan campus from the database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = vlan_campus_del('name=1223543');
///////////////////////////////////////////////////////////////////////
function vlan_campus_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => vlan_campus_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['name'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

vlan_campus_del-v{$version}
Deletes a vlan campus from the database

  Synopsis: vlan_campus_del [KEY=VALUE] ...

  Required:
    name=NAME or ID      Name or ID of the vlan campus to delete

  Optional:
    commit=[yes|no]      commit db transaction (no)

\n
EOM

        ));
    }


    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // If the vlan provided is numeric, check to see if it's an vlan
    if (is_numeric($options['name'])) {

        // See if it's an vlan_campus_id
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('id' => $options['name']));

        if (!$campus['id']) {
            printmsg("DEBUG => Unable to find VLAN campus using the ID {$options['name']}!",3);
            $self['error'] = "ERROR => Unable to find VLAN campus using the ID {$options['name']}!";
            return(array(2, $self['error'] . "\n"));
        }
    }
    else {
        $options['name'] = strtoupper(trim($options['name']));
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('name' => $options['name']));
        if (!$campus['id']) {
            printmsg("DEBUG => Unable to find VLAN campus using the name {$options['name']}!",3);
            $self['error'] = "ERROR => Unable to find VLAN campus using the name {$options['name']}!";
            return(array(2, $self['error'] . "\n"));
        }
    }


    list($status, $rows, $vlan) = db_get_records($onadb, 'vlans', array('vlan_campus_id' => $campus['id']), '' ,0);
    if ($rows != 0) {
        printmsg("DEBUG => This VLAN campus ({$campus['name']}) is in use by {$rows} VLAN(s)!",3);
        $self['error'] = "ERROR => This VLAN campus ({$campus['name']}) is in use by {$rows} VLAN(s)!";
        return(array(6, $self['error'] . "\n" .
                        "INFO  => Please dis-associate those VLANS from this campus before deleting.\n"));
    }

    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('vlan_del')) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'vlan_campuses', array('id' => $campus['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => vlan_campus_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(4, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => VLAN Campus DELETED: {$campus['name']}";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }



    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

    NAME: {$campus['name']}

EOL;

    return(array(6, $text));

}









///////////////////////////////////////////////////////////////////////
//  Function: vlan_campus_modify (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    name=NAME or ID                  campus Name or ID
//    set_name=STRING                  change vlan name
//
//  Output:
//    Updates a vlan campus record in the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = vlan_campus_modify('name=23452&name=blah');
///////////////////////////////////////////////////////////////////////
function vlan_campus_modify($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => vlan_campus_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or (!$options['name']) or (!$options['set_name']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

vlan_campus_modify-v{$version}
Modifies an existing vlan campus entry in the database

  Synopsis: vlan_campus_modify [KEY=VALUE] ...

  Where:
    name=NAME or ID                campus Name or ID

  Update:
    set_name=NAME                  change VLAN campus name

\n
EOM
        ));
    }

    // The formatting rule on vlan campus names is all upper and trim it
    $options['set_name'] = strtoupper(trim($options['set_name']));

    // If the vlan provided is numeric, check to see if it's an vlan
    if (is_numeric($options['name'])) {

        // See if it's an vlan_campus_id
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('id' => $options['name']));

        if (!$campus['id']) {
            printmsg("DEBUG => Unable to find VLAN campus using the ID {$options['name']}!",3);
            $self['error'] = "ERROR => Unable to find VLAN campus using the ID {$options['name']}!";
            return(array(2, $self['error'] . "\n"));
        }
    }
    else {
        $options['name'] = strtoupper(trim($options['name']));
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('name' => $options['name']));
        if (!$campus['id']) {
            printmsg("DEBUG => Unable to find VLAN campus using the name {$options['name']}!",3);
            $self['error'] = "ERROR => Unable to find VLAN campus using the name {$options['name']}!";
            return(array(2, $self['error'] . "\n"));
        }
    }

    printmsg("DEBUG => Found VLAN campus: {$campus['name']}", 3);


    // This variable will contain the updated info we'll insert into the DB
    $SET = array();


    // If they are specifying a new name, process it.
    if ($options['set_name']) {
        // Validate that there isn't already an vlan with this name
        list($status, $rows, $record) =  db_get_records($onadb, 'vlan_campuses', "id != {$campus['id']} AND name LIKE '{$options['set_name']}'");

        if ($status or $rows) {
            printmsg("DEBUG => The VLAN campus ({$options['set_name']}) already exists!",3);
            $self['error'] = "ERROR => The VLAN campus {$options['set_name']} already exists!";
            return(array(4, $self['error'] . "\n"));
        }
        $SET['name'] = $options['set_name'];
    }


    // Check permissions
    if (!auth('vlan_modify')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }


    // Update the record
    list($status, $rows) = db_update_record($onadb, 'vlan_campuses', array('id' => $campus['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => vlan_campus_modify() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Return the success notice
    if($options['set_name'] != $campus['name']) $renamed = "=> {$options['set_name']}";
    $self['error'] = "INFO => VLAN Campus UPDATED: {$campus['name']} {$renamed}";

    return(array(0, $self['error'] . "\n"));
}









///////////////////////////////////////////////////////////////////////
//  Function: vlan_campus_display (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    campus=NAME or ID
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = vlan_campus_display('campus=test');
///////////////////////////////////////////////////////////////////////
function vlan_campus_display($options="") {
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => vlan_campus_display({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize options[verbose] (default is yes)
    $options['verbose'] = sanitize_YN($options['verbose'], 'Y');

    // Return the usage summary if we need to
    if ($options['help'] or !$options['campus'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

vlan_campus_display-v{$version}
Displays a vlan campus record from the database

  Synopsis: vlan_campus_display [KEY=VALUE] ...

  Required:
    campus=NAME or ID      Campus name or ID of the campus display

  Optional:
    verbose=[yes|no]       Display additional info (DEFAULT: yes)


EOM

        ));
    }

    // The formatting rule on campus names is all upper and trim it
    $options['campus'] = strtoupper(trim($options['campus']));


    // If the campus provided is numeric, check to see if it's valid
    if (is_numeric($options['campus'])) {

        // See if it's an vlan_campus_id
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('id' => $options['campus']));

        if (!$campus['id']) {
            printmsg("DEBUG => Unable to find campus using the ID {$options['campus']}!",3);
            $self['error'] = "ERROR => Unable to find campus using the ID {$options['campus']}!";
            return(array(2, $self['error'] . "\n"));
        }
    }
    else {
        list($status, $rows, $campus) = ona_get_vlan_campus_record(array('name' => $options['campus']));
        if (!$campus['id']) {
            $self['error'] = "ERROR => Unable to find campus using the name {$options['campus']}!";
            printmsg("DEBUG => Unable to find campus using the name {$options['campus']}!",3);
            return(array(2, $self['error'] . "\n"));
        }
    }

    printmsg("DEBUG => Found campus: {$campus['name']}", 3);

    // Build text to return
    $text  = "VLAN CAMPUS RECORD\n";
    $text .= format_array($campus);

    // If 'verbose' is enabled, grab some additional info to display
    if ($options['verbose'] == 'Y') {

        // vlan record(s)
        $i = 0;
        do {
            list($status, $rows, $vlan) = ona_get_vlan_record(array('vlan_campus_id' => $campus['id']));
            if ($rows == 0) { break; }
            $i++;
            $text .= "\nASSOCIATED VLAN RECORD ({$i} of {$rows})\n";
            $text .= format_array($vlan);
        } while ($i < $rows);


    }

    // Return the success notice
    return(array(0, $text));

}









// DON'T put whitespace at the beginning or end of this file!!!
?>