<?

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

    // validate the inpute value against the field_validation_rule.
    if (!preg_match($catype['field_validation_rule'], $options['value'])) {
        printmsg("DEBUG => The value '{$options['value']}' does not match field validation rule: {$catype['field_validation_rule']}",3);
        $self['error'] = "ERROR => The value '{$options['value']}' does not match field validation rule: {$catype['field_validation_rule']}";
        return(array(7, $self['error'] . "\n"));
    }

    // add it
    list($status, $rows) = db_insert_record(
        $onadb,
        'custom_attributes',
        array(
            'table_name_ref'       => $table_name_ref,
            'table_id_ref'         => $table_id_ref,
            'custom_attribute_type_id' => $catype['id'],
            'value'                => $options['value']
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






?>
