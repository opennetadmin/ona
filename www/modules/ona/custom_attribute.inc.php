<?php

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);















///////////////////////////////////////////////////////////////////////
//  Function: custom_attribute_add (string $options='')
//
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = custom_attribute_add('host=test');
//
//  Exit codes:
//    0  :: No error
//    1  :: Help text printed - Insufficient or invalid input received
//    4  :: SQL Query failed
//
//
//  History:
//
//
///////////////////////////////////////////////////////////////////////
function custom_attribute_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => custom_attribute_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
       (!$options['subnet'] and !$options['host']) or
       (!$options['type'] and
        !$options['value'])) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

custom_attribute_add-v{$version}
Adds the custom attribute to the host or subnet specified

  Synopsis: custom_attribute_add

  Required:
    host=NAME[.DOMAIN]|IP     hostname or IP of the host
    OR
    subnet=NAME|IP            name or IP of the subnet

    type=ID|STRING            the name or ID of the attribute type
    value="STRING"            the value of the attribute

\n
EOM

        ));
    }


    // If they provided a hostname / ID let's look it up
    if ($options['host']) {
        list($status, $rows, $host) = ona_find_host($options['host']);
        $table_name_ref = 'hosts';
        $table_id_ref = $host['id'];
        $desc = $host['fqdn'];
    }

    // If they provided a subnet name or ip
    else if ($options['subnet']) {
        list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);
        $table_name_ref = 'subnets';
        $table_id_ref = $subnet['id'];
        $desc = $subnet['name'];
    }

    // If we didn't get a record then exit
    if (!$host['id'] and !$subnet['id']) {
        printmsg("DEBUG => No host or subnet found!",3);
        $self['error'] = "ERROR => No host or subnet found!";
        return(array(4, $self['error'] . "\n"));
    }

    // determine how we are searching for the type
    $typesearch = 'name';
    if (is_numeric($options['type'])) {
        $typesearch = 'id';
    }

    // find the attribute type
    list($status, $rows, $catype) = ona_get_custom_attribute_type_record(array($typesearch => $options['type']));
    if (!$rows) {
        printmsg("DEBUG => Unable to find custom attribute type: {$options['type']}",3);
        $self['error'] = "ERROR => Unable to find custom attribute type: {$options['type']}";
        return(array(5, $self['error'] . "\n"));
    }


    // check for existing attributes like this
    list($status, $rows, $record) = ona_get_custom_attribute_record(array('table_name_ref' => $table_name_ref, 'table_id_ref' => $table_id_ref, 'custom_attribute_type_id' => $catype['id']));
    if ($rows) {
        printmsg("DEBUG => The type '{$catype['name']}' is already in use on {$desc}",3);
        $self['error'] = "ERROR => The type '{$catype['name']}' is already in use on {$desc}";
        return(array(6, $self['error'] . "\n"));
    }

    if (!$catype['failed_rule_text']) $catype['failed_rule_text'] = "Not specified.";

    // validate the inpute value against the field_validation_rule.
    if ($catype['field_validation_rule'] and !preg_match($catype['field_validation_rule'], $options['value'])) {
        printmsg("DEBUG => The value '{$options['value']}' does not match field validation rule: {$catype['field_validation_rule']}",3);
        $self['error'] = "ERROR => The value: '{$options['value']}', does not match field validation rule: {$catype['field_validation_rule']}\\nReason: {$catype['failed_rule_text']}";
        return(array(7, $self['error'] . "\n"));
    }

    // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
    $options['value'] = str_replace('\\=','=',$options['value']);
    $options['value'] = str_replace('\\&','&',$options['value']);

    // add it
    list($status, $rows) = db_insert_record(
        $onadb,
        'custom_attributes',
        array(
            'table_name_ref'       => $table_name_ref,
            'table_id_ref'         => $table_id_ref,
            'custom_attribute_type_id' => $catype['id'],
            'value'                => trim($options['value'])
        )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => custom_attribute_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(8, $self['error'] . "\n"));
    }


    $text = "INFO => Custom Attribute ADDED to: {$desc}\n";


    // Return the message file
    return(array(0, $text));

}






///////////////////////////////////////////////////////////////////////
//  Function: custom_attribute_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    name=ID
//
//  Output:
//    Deletes an custom_attribute from the database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = custom_attribute_del('name=1223543');
///////////////////////////////////////////////////////////////////////
function custom_attribute_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => custom_attribute_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
       (!$options['subnet'] and !$options['host']) or
       (!$options['type'] )) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

custom_attribute_del-v{$version}
Deletes a custom attribute from the database

  Synopsis: custom_attribute_del [KEY=VALUE] ...

  Required:
    host=NAME[.DOMAIN]|IP     hostname or IP of the host
    OR
    subnet=NAME|IP            name or IP of the subnet

    type=ID|STRING            the name or ID of the attribute type

  Optional:
    commit=[yes|no]           commit db transaction (no)

\n
EOM

        ));
    }


    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // If they provided a hostname / ID let's look it up
    if ($options['host']) {
        list($status, $rows, $host) = ona_find_host($options['host']);
        $table_name_ref = 'hosts';
        $table_id_ref = $host['id'];
        $desc = $host['fqdn'];
    }

    // If they provided a subnet name or ip
    else if ($options['subnet']) {
        list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);
        $table_name_ref = 'subnets';
        $table_id_ref = $subnet['id'];
        $desc = $subnet['name'];
    }

    // If we didn't get a record then exit
    if (!$host['id'] and !$subnet['id']) {
        printmsg("DEBUG => No host or subnet found!",3);
        $self['error'] = "ERROR => No host or subnet found!";
        return(array(1, $self['error'] . "\n"));
    }

    // If the type provided is numeric, check to see if it's an vlan
    if (is_numeric($options['type'])) {

        // See if it's an vlan_campus_id
        list($status, $rows, $catype) = ona_get_custom_attribute_type_record(array('id' => $options['type']));

        if (!$catype['id']) {
            printmsg("DEBUG => Unable to find custom attribute type using the ID {$options['name']}!",3);
            $self['error'] = "ERROR => Unable to find custom attribute type using the ID {$options['name']}!";
            return(array(2, $self['error'] . "\n"));
        }
    }
    else {
        $options['type'] = trim($options['type']);
        list($status, $rows, $catype) = ona_get_custom_attribute_type_record(array('name' => $options['type']));
        if (!$catype['id']) {
            printmsg("DEBUG => Unable to find custom attribute type using the name {$options['type']}!",3);
            $self['error'] = "ERROR => Unable to find custom attribute type using the name {$options['type']}!";
            return(array(3, $self['error'] . "\n"));
        }
    }

    list($status, $rows, $record) = ona_get_custom_attribute_record(array('table_name_ref' => $table_name_ref, 'table_id_ref' => $table_id_ref, 'custom_attribute_type_id' => $catype['id']));
    if (!$rows) {
        printmsg("DEBUG => Unable to find custom attribute!",3);
        $self['error'] = "ERROR => Unable to find custom attribute!";
        return(array(4, $self['error'] . "\n"));
    }


    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('custom_attribute_del')) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(5, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'custom_attributes', array('id' => $record['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => custom_attribute_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(6, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => Custom Attribute DELETED: {$record['name']} ({$record['value']}) from {$desc}";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }



    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

    ASSOCIATED WITH: {$desc}
    NAME: {$record['name']}
    VALUE: {$record['value']}


EOL;

    return(array(6, $text));

}











///////////////////////////////////////////////////////////////////////
//  Function: custom_attribute_modify (string $options='')
//
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = custom_attribute_modify('host=test');
//
//  Exit codes:
//    0  :: No error
//    1  :: Help text printed - Insufficient or invalid input received
//    4  :: SQL Query failed
//
//
//  History:
//
//
///////////////////////////////////////////////////////////////////////
function custom_attribute_modify($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => custom_attribute_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or
        !(
            ($options['id'])
            and
            (
                ($options['set_type'] and array_key_exists('set_value',$options))
                or
                (array_key_exists('set_value',$options))
                or
                (array_key_exists('set_type',$options))
            )
         )
       )
    {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

custom_attribute_modify-v{$version}
Modifies the custom attribute specified

  Synopsis: custom_attribute_modify

  Where:
    id=ID                     custom attribute ID

  Options:
    set_type=ID|STRING        the name or ID of the attribute type
    set_value="STRING"        the value of the attribute

  Notes:
    If you specify a type, you must specify a value.
\n
EOM

        ));
    }


    // Determine the entry itself exists
    list($status, $rows, $entry) = ona_get_custom_attribute_record(array('id' => $options['id']));
    if ($status or !$rows) {
        printmsg("DEBUG => Invalid Custom Atribute record ID ({$options['id']})!",3);
        $self['error'] = "ERROR => Invalid Custom Atribute record ID ({$options['id']})!";
        return(array(2, $self['error']. "\n"));
    }

    printmsg("DEBUG => custom_attribute_modify(): Found entry, {$entry['name']} => {$entry['value']}", 3);
    $desc='';

    // If they provided a hostname / ID let's look it up
    if ($entry['table_name_ref'] == "hosts") {
        list($status, $rows, $host) = ona_find_host($entry['table_id_ref']);
        $table_name_ref = 'hosts';
        $table_id_ref = $host['id'];
        $desc = $host['fqdn'];
    }
    if ($entry['table_name_ref'] == "subnets") {
        list($status, $rows, $subnet) = ona_find_subnet($entry['table_id_ref']);
        $table_name_ref = 'subnets';
        $table_id_ref = $subnet['id'];
        $desc = $subnet['name'];
    }


    // This variable will contain the updated info we'll insert into the DB
    $SET = array();

    $typesearch = 'id';
    $typeval = $entry['custom_attribute_type_id'];

    // determine the attribute type
    if (array_key_exists('set_type', $options)) {
        if (!is_numeric($options['set_type'])) {
            $typesearch = 'name';
        }
        $typeval = $options['set_type'];
    }

    // Find the attribute type
    list($status, $rows, $catype) = ona_get_custom_attribute_type_record(array($typesearch => $typeval));
    if (!$rows) {
        printmsg("DEBUG => Unable to find custom attribute type: {$typeval}",3);
        $self['error'] = "ERROR => Unable to find custom attribute type: {$typeval}";
        return(array(3, $self['error'] . "\n"));
    }

    // default to whatever was in the record you are editing
    $SET['value'] = $entry['value'];

    if (array_key_exists('set_value', $options)) {
        // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
        $options['set_value'] = str_replace('\\=','=',$options['set_value']);
        $options['set_value'] = str_replace('\\&','&',$options['set_value']);

        // trim leading and trailing whitespace from 'value'
        $SET['value'] = $valinfo = trim($options['set_value']);
    }

    if (!$catype['failed_rule_text']) $catype['failed_rule_text'] = "Not specified.";

    // validate the inpute value against the field_validation_rule.
    if ($catype['field_validation_rule'] and !preg_match($catype['field_validation_rule'], $SET['value'])) {
        printmsg("DEBUG => The value '{$SET['value']}' does not match field validation rule: {$catype['field_validation_rule']}",3);
        $self['error'] = "ERROR => The value: '{$SET['value']}', does not match field validation rule: {$catype['field_validation_rule']}\\nReason: {$catype['failed_rule_text']}";
        return(array(4, $self['error'] . "\n"));
    }

    // if the value has not changed, skip it
    if ($SET['value'] == $entry['value']) { unset($SET['value']); $valinfo = "Value Not Changed";}

    // if we change the type do a few things
    if ($catype['id'] != $entry['custom_attribute_type_id']) {
        // check for existing attributes like this that might already be assigned
        list($status, $rows, $record) = ona_get_custom_attribute_record(array('table_name_ref' => $table_name_ref, 'table_id_ref' => $table_id_ref, 'custom_attribute_type_id' => $catype['id']));
        if ($rows) {
            printmsg("DEBUG => The type '{$catype['name']}' is already in use on {$desc}",3);
            $self['error'] = "ERROR => The type '{$catype['name']}' is already in use on {$desc}";
            return(array(5, $self['error'] . "\n"));
        }

        // if we are good to go.. set the new type
        $SET['custom_attribute_type_id'] = $catype['id'];
    }



    $msg = "INFO => Updated Custom Attribute type: {$catype['name']} => '{$valinfo}'.";

    // If nothing at all changed up to this point, bail out
    if (!$SET) {
        $self['error'] = "ERROR => custom_attribute_modify() You didn't change anything. Make sure you have a new value.";
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Update the record
    list($status, $rows) = db_update_record($onadb, 'custom_attributes', array('id' => $entry['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => custom_attribute_modify() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }


    // Return the success notice
    $self['error'] = $msg;
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));

}







///////////////////////////////////////////////////////////////////////
//  Function: custom_attribute_display (string $options='')
//
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = custom_attribute_modify('host=test');
//
//  Exit codes:
//    0  :: No error
//    1  :: Help text printed - Insufficient or invalid input received
//
//
//  History:
//
//
///////////////////////////////////////////////////////////////////////
function custom_attribute_display($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => custom_attribute_display({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or (!$options['host'] and !$options['id'] and !$options['subnet'])) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

custom_attribute_display-v{$version}
Display the custom attribute specified or attributes for a host

  Synopsis: custom_attribute_display

  Where:
    id=ID                     custom attribute ID
    OR
    host=ID or NAME[.DOMAIN]  display custom attributes for specified host
    OR
    subnet=ID or NAME         display custom attributes for specified subnet

  Optional:
    type=ID or NAME           If you specify a type and a host or subnet you
                              will only get back a 1 or a 0 indicating that
                              that type is set or not set for the host or subnet
EOM

        ));
    }




    // Now find the ID of the record
    if ($options['id']) {
        list($status, $rows, $ca) = ona_get_custom_attribute_record(array('id' => $options['id']));
        if (!$ca['id']) {
            $self['error'] = "ERROR => The custom attribute specified, {$options['id']}, is invalid!";
            return(array(4, $self['error']));
        }

        $text .= "CUSTOM ATTRIBUTE ENTRY RECORD ({$ca['id']})\n";
        $text .= format_array($ca);
        // Return the success notice
        return(array(0, $text));
    }

    // if a type was set, check if it is associated with the host or subnet and return 1 or 0
    if ($options['type']) {
        $field = (is_numeric($options['type'])) ? 'id' : 'name';
        list($status, $rows, $catype) = ona_get_custom_attribute_type_record(array($field => $options['type']));
        // error if we cant find the type specified
        if (!$catype['id']) {
            $self['error'] = "ERROR => The custom attribute type specified, {$options['type']}, does not exist!";
            return(array(5, $self['error']));
        }

        $where['custom_attribute_type_id'] = $catype['id'];
    }

    // Search for the host first
    if ($options['host']) {
        list($status, $rows, $host) = ona_find_host($options['host']);

        // Error if the host doesn't exist
        if (!$host['id']) {
            $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
            return(array(2, $self['error']));
        } else {
                $where['table_id_ref'] = $host['id'];
                $where['table_name_ref'] = 'hosts';
                list($status, $rows, $cas) = db_get_records($onadb,'custom_attributes', $where );
        }

        $anchor = 'host';
        $desc = $host['fqdn'];
    }

    // Search for subnet
    if ($options['subnet']) {
        list($status, $rows, $subnet) = ona_find_subnet($options['subnet']);

        // Error if the record doesn't exist
        if (!$subnet['id']) {
            $self['error'] = "ERROR => The subnet specified, {$options['subnet']}, does not exist!";
            return(array(3, $self['error']));
        } else {
                list($status, $rows, $cas) = db_get_records($onadb,'custom_attributes',
                                                array('table_id_ref' => $subnet['id'], 'table_name_ref' => 'subnets')

                                            );
        }

        $anchor = 'server';
        $desc = $subnet['description'];
    }

    if ($options['type']) {
        if ($cas[0]) {
            return(array(0, '1'));
        } else {
            return(array(0, '0'));
        }
    }

    // Build text to return
    $text  = strtoupper($anchor) . " CUSTOM ATTRIBUTE RECORDS ({$desc})\n";

    // Display the record(s)
    $i = 0;
    do {
        $i++;
        $text .= "\nASSOCIATED CUSTOM ATTRIBUTE ENTRY RECORD ({$i} of {$rows})\n";
        $text .= format_array($cas[$i - 1]);
    } while ($i < $rows);

    // Return the success notice
    return(array(0, $text));

}


?>
