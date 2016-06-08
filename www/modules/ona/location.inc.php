<?php
// DON'T put whitespace at the beginning or end of this file!!!


///////////////////////////////////////////////////////////////////////
//  Function: location_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    reference=STRING
//
//  Output:
//    Adds a location into the database called 'name'
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = location_add('name=blah');
///////////////////////////////////////////////////////////////////////
function location_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.02';

    printmsg("DEBUG => location_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['reference'] and $options['name']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

location_add-v{$version}
Adds a location into the database

  Synopsis: location_add [KEY=VALUE] ...

  Required:
    reference=STRING       reference for identifying and searching for location
    name=STRING            location descriptive name

  Optional:
    address=STRING
    city=STRING
    state=STRING
    zip_code=NUMBER
    latitude=STRING
    longitude=STRING
    misc=STRING

\n
EOM

        ));
    }


    // The formatting rule on location reference is all upper and trim it
    $options['reference'] = strtoupper(trim($options['reference']));

    if (!$options['zip_code']) { $options['zip_code'] = 0; }

    // check to see if the campus already exists
    list($status, $rows, $loc) = ona_get_location_record(array('reference' => $options['reference']));

    if ($status or $rows) {
        printmsg("DEBUG => The location {$options['reference']} already exists!",3);
        $self['error'] = "ERROR => The location {$options['reference']} already exists!";
        return(array(3, $self['error'] . "\n"));
    }



    // Check permissions
    if (!auth('location_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next ID for the new location
    $id = ona_get_next_id('locations');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'], 0);
        return(array(5, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new location: $id", 3);

    // Add the record
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'locations',
            array(
                'id'                  => $id,
                'reference'           => $options['reference'],
                'name'                => $options['name'],
                'address'             => $options['address'],
                'city'                => $options['city'],
                'state'               => $options['state'],
                'zip_code'            => $options['zip_code'],
                'latitude'            => $options['latitude'],
                'longitude'           => $options['longitude'],
                'misc'                => $options['misc']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => location_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'],0);
        return(array(6, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => Location ADDED: {$options['reference']}: {$options['name']}";
    printmsg($self['error'],0);
    return(array(0, $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: location_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    name=ID
//
//  Output:
//    Deletes a location from the database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = location_del('ref=1223543');
///////////////////////////////////////////////////////////////////////
function location_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.01';

    printmsg("DEBUG => location_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['reference'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

locaiton_del-v{$version}
Deletes a location from the database

  Synopsis: location_del [KEY=VALUE] ...

  Required:
    reference=NAME or ID      Reference or ID of the location to delete

  Optional:
    commit=[yes|no]           commit db transaction (no)

\n
EOM

        ));
    }


    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');


    // Find the Location to use
    list($status, $rows, $loc) = ona_find_location($options['reference']);
    if ($status or !$rows) {
        printmsg("DEBUG => The location specified, {$options['reference']}, does not exist!", 3);
        return(array(2, "ERROR => The location specified, {$options['reference']}, does not exist!\n"));
    }
    printmsg("DEBUG => Location selected: {$loc['reference']}, location name: {$loc['name']}", 3);


    list($status, $rows, $usage) = db_get_records($onadb, 'devices', array('location_id' => $loc['id']), '' ,0);
    if ($rows != 0) {
        printmsg("DEBUG => The location ({$loc['reference']}) is in use by {$rows} devices(s)!",3);
        $self['error'] = "ERROR => The location ({$loc['reference']}) is in use by {$rows} devices(s)!";
        return(array(6, $self['error']."\n"));
    }

    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('location_del')) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'locations', array('id' => $loc['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => location_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(4, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => Location DELETED: {$loc['reference']} ({$loc['name']})";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }



    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:


EOL;

    $text .= format_array($loc);
    $text .= "\n";

    return(array(6, $text));

}









///////////////////////////////////////////////////////////////////////
//  Function: location_modify (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    reference=STRING or ID           location reference or ID
//    set_name=STRING                  change location name
//
//  Output:
//    Updates a location record in the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = location_modify('reference=23452&name=blah');
///////////////////////////////////////////////////////////////////////
function location_modify($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.01';

    printmsg("DEBUG => location_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or (!$options['reference']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

location_modify-v{$version}
Modifies an existing location entry in the database

  Synopsis: location_modify [KEY=VALUE] ...

  Where:
    reference=STRING or ID         location reference or ID

  Update:
    set_reference=NAME             change location reference
    set_name=NAME                  change location name
    set_address=STRING
    set_city=STRING
    set_state=STRING
    set_zip_code=NUMBER
    set_latitude=STRING
    set_longitude=STRING
    set_misc=STRING

\n
EOM
        ));
    }


    // See if it's an vlan_campus_id
    list($status, $rows, $loc) = ona_find_location($options['reference']);

    if (!$loc['id']) {
        printmsg("DEBUG => Unable to find location using: {$options['reference']}!",3);
        $self['error'] = "ERROR => Unable to find location using: {$options['reference']}!";
        return(array(1, $self['error'] . "\n"));
    }


    printmsg("DEBUG => Found location: {$loc['reference']}", 3);


    // This variable will contain the updated info we'll insert into the DB
    $SET = array();

    if ($loc['reference'] != $options['set_reference']) {
        $SET['reference'] = $options['set_reference'];
        $msg .= "INFO => Location UPDATED reference: {$loc['reference']} => {$options['set_reference']}\n";
    }

    // If they are specifying a new name, process it.
    if ($loc['name'] != $options['set_name']) {
        $SET['name'] = $options['set_name'];
        $msg .= "INFO => Location UPDATED name: {$loc['name']} => {$options['set_name']}\n";
    }

    if ($loc['address'] != $options['set_address']) {
        $SET['address'] = $options['set_address'];
        $msg .= "INFO => Location UPDATED address: {$loc['address']} => {$options['set_address']}\n";
    }

    if ($loc['city'] != $options['set_city']) {
        $SET['city'] = $options['set_city'];
        $msg .= "INFO => Location UPDATED city: {$loc['city']} => {$options['set_city']}\n";
    }

    if ($loc['state'] != $options['set_state']) {
        $SET['state'] = $options['set_state'];
        $msg .= "INFO => Location UPDATED state: {$loc['state']} => {$options['set_state']}\n";
    }

    if ($loc['zip_code'] != $options['set_zip_code']) {
        $SET['zip_code'] = $options['set_zip_code'];
        $msg .= "INFO => Location UPDATED zip_code: {$loc['zip_code']} => {$options['set_zip_code']}\n";
    }

    if ($loc['latitude'] != $options['set_latitude']) {
        $SET['latitude'] = $options['set_latitude'];
        $msg .= "INFO => Location UPDATED latitude: {$loc['latitude']} => {$options['set_latitude']}\n";
    }

    if ($loc['longitude'] != $options['set_longitude']) {
        $SET['longitude'] = $options['set_longitude'];
        $msg .= "INFO => Location UPDATED longitude: {$loc['longitude']} => {$options['set_longitude']}\n";
    }

    if ($loc['misc'] != $options['set_misc']) {
        $SET['misc'] = $options['set_misc'];
        $msg .= "INFO => Location UPDATED misc: {$loc['misc']} => {$options['set_misc']}\n";
    }

    if (!$SET) {
        $self['error'] = "ERROR => You did not update anything.";
        printmsg($self['error'], 1);
        return(array(2, $self['error'] . "\n"));
    }

    // Check permissions
    if (!auth('location_add')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(2, $self['error'] . "\n"));
    }


    // Update the record
    list($status, $rows) = db_update_record($onadb, 'locations', array('id' => $loc['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => location_modify() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(3, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = $msg;
    printmsg($self['error'], 0);
    return(array(0, $self['error'] . "\n"));
}














// DON'T put whitespace at the beginning or end of this file!!!
?>
