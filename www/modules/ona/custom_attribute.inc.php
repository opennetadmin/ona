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
        printmsg("DEBUG => The type '{$options['type']}' is already in use on {$desc}",3);
        $self['error'] = "ERROR => The type '{$options['type']}' is already in use on {$desc}";
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









?>
