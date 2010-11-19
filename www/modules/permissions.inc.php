<?php
// DON'T put whitespace at the beginning or end of this file!!!



///////////////////////////////////////////////////////////////////////
//  Function: add_permission (string $options='')
//
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//
//  Output:
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = add_permission('');
///////////////////////////////////////////////////////////////////////
function add_permission($options="") {
    global $conf, $self, $onadb;
    printmsg('DEBUG => add_permission('.$options.') called', 3);

    // Version - UPDATE on every edit!
    $version = '1.00';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['name']) {
        $self['error'] = 'ERROR => Insufficient parameters';
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1,
<<<EOM

add_permission-v{$version}
Registers a new permission, this should be used by install scripts that are
creating new functionality that requires a registered permission.

  Synopsis: add_permission(OPTIONS)

  Options:
    name=STRING         Name of permission
    desc=STRING         Quoted string to describe this permission


EOM

        ));
    }


    // Get a list of the valid "permissions" and their descriptions.
    list($status, $rows, $permissions) = db_get_record($onadb, 'permissions', array('name' => $options['name']), '');
    if ($rows) {
        $self['error'] = "ERROR => add_permission() Permission already exists: {$options['name']}";
        printmsg($self['error'],0);
        return(array(1, $self['error'] . "\n"));
    }


    // Get the next ID for the new host record
    $id = ona_get_next_id('permissions');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id('permissions') call failed!";
        printmsg($self['error'], 0);
        return(array(7, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new permission record: $id", 3);

    // Add the record
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'permissions',
            array(
                'id'              => $id,
                'name'            => $options['name'],
                'description'     => $options['desc']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => add_permission() SQL Query failed: " . $self['error'];
        printmsg($self['error'],0);
        return(array(2, $self['error'] . "\n"));
    }


    // Return the success notice
    $self['error'] = "INFO => Permission ADDED: {$options['name']} [{$options['desc']}]";
    printmsg($self['error'],0);
    return(array(0, $self['error'] . "\n"));
}



?>
