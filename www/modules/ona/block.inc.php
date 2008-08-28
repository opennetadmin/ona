<?php
// DON'T put whitespace at the beginning or end of this file!!!

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);


///////////////////////////////////////////////////////////////////////
//  Function: block_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    name=STRING
//    start=IP
//    end=IP
//    notes=STRING
//
//  Output:
//    Adds a block into the database called 'name'
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = block_add('name=test&start=blah&end=1');
///////////////////////////////////////////////////////////////////////
function block_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => block_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !($options['name'] and $options['start'] and $options['end']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

block_add-v{$version}
Adds a block into the database

  Synopsis: block_add [KEY=VALUE] ...

  Required:
    name=STRING                 block name
    start=IP                    block start IP
    end=IP                      block end IP

  Optional:
    notes=STRING                notes

\n
EOM

        ));
    }


    // The formatting rule on block names is all upper and trim it
    $options['name'] = trim($options['name']);
    $options['name'] = preg_replace('/\s+/', '-', $options['name']);
    $options['name'] = strtoupper($options['name']);

    $options['start'] = ip_mangle($options['start'], 1);
    $options['end']  = ip_mangle($options['end'], 1);

    // check to see if the campus already exists
    list($status, $rows, $block) = ona_get_block_record(array('name' => $options['name']));

    if ($status or $rows) {
        $self['error'] = "ERROR => The block {$options['name']} already exists!";
        printmsg("DEBUG => The block {$options['name']} already exists!",3);
        return(array(3, $self['error'] . "\n"));
    }


    // Check permissions
    if (!auth('advanced')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next ID for the new block
    $id = ona_get_next_id('blocks');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'],0);
        return(array(5, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new block: $id", 3);

    // Add the block
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'blocks',
            array(
                'id'               => $id,
                'name'             => $options['name'],
                'ip_addr_start'    => $options['start'],
                'ip_addr_end'      => $options['end'],
                'notes'            => $options['notes']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => block_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'],0);
        return(array(6, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => Block ADDED: {$options['name']}";
    printmsg($self['error'],0);
    return(array(0, $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: block_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    block=name or ID
//
//  Output:
//    Deletes a block from the database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = block_del('id=1223543');
///////////////////////////////////////////////////////////////////////
function block_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => block_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['block'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

block_del-v{$version}
Deletes a block from the database

  Synopsis: block_del [KEY=VALUE] ...

  Required:
    block=name or ID           name or ID of the block to delete

  Optional:
    commit=[yes|no]            commit db transaction (no)

\n
EOM

        ));
    }


    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // If the block provided is numeric, check to see if it's an block
    if (is_numeric($options['block'])) {

        // See if it's an block_id
        list($status, $rows, $block) = ona_get_block_record(array('id' => $options['block']));

        if (!$block['id']) {
            $self['error'] = "ERROR => Unable to find block using the ID {$options['block']}!";
            printmsg("DEBUG => Unable to find block using the ID {$options['block']}!",3);
            return(array(2, $self['error'] . "\n"));
        }
    }
    else {
        list($status, $rows, $block) = ona_get_block_record(array('name' => $options['block']));
        if (!$block['id']) {
            $self['error'] = "ERROR => Unable to find block using the name {$options['block']}!";
            printmsg("DEBUG => Unable to find block using the name {$options['block']}!",3);
            return(array(2, $self['error'] . "\n"));
        }
    }


    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (!auth('advanced')) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'blocks', array('id' => $block['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => block_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'],0);
            return(array(4, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => Block DELETED: {$block['name']}";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }

    $start = ip_mangle($block['ip_addr_start'], 'dotted');
    $end  = ip_mangle($block['ip_addr_end'], 'dotted');


    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

    DESCRIPTION: {$block['name']}
    IP START:    {$start}
    IP END:      {$end}
    Notes:       {$block['notes']}


EOL;

    return(array(6, $text));

}









///////////////////////////////////////////////////////////////////////
//  Function: block_modify (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    set_name=NAME           change block name
//    set_start=STRING        change block starting IP address
//    set_end=STRING          change block end IP
//    set_notes=STRING        change block notes
//
//  Output:
//    Updates a block record in the IP database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = block_modify('name=23452&notes=blah stuff default');
///////////////////////////////////////////////////////////////////////
function block_modify($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.01';

    printmsg("DEBUG => block_modify({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['block'] or
                             !($options['set_name'] or
                               $options['set_start'] or
                               $options['set_end'] or
                               $options['set_notes']
                              )
       ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

block_modify-v{$version}
Modifies a block entry in the database

  Synopsis: block_modify [KEY=VALUE] ...

  Where:
    block=NAME or ID               block Name or ID

  Update:
    set_name=NAME                  change block name
    set_start=STRING               change block starting IP address
    set_end=STRING                 change block end
    set_notes=STRING               change block notes

\n
EOM
        ));
    }

    // The formatting rule on block campus names is all upper and trim it
    $options['set_name'] = strtoupper(trim($options['set_name']));

    // If the block provided is numeric, check to see if it's an block
    if (is_numeric($options['block'])) {

        // See if it's an block_id
        list($status, $rows, $block) = ona_get_block_record(array('id' => $options['block']));

        if (!$block['id']) {
            printmsg("DEBUG => Unable to find block using the ID {$options['block']}!",3);
            $self['error'] = "ERROR => Unable to find block using the ID {$options['block']}!";
            return(array(2, $self['error'] . "\n"));
        }
    }
    else {
        list($status, $rows, $block) = ona_get_block_record(array('name' => $options['block']));
        if (!$block['id']) {
            $self['error'] = "ERROR => Unable to find block using the name {$options['block']}!";
            printmsg("DEBUG => Unable to find block using the name {$options['block']}!",3);
            return(array(2, $self['error'] . "\n"));
        }
    }

    printmsg("DEBUG => Found block: {$block['name']}", 3);


    // This variable will contain the updated info we'll insert into the DB
    $SET = array();


    // If they are specifying a new name, process it.
    if ($options['set_name']) {
        // Validate that there isn't already an block with this name
        list($status, $rows, $record) =  db_get_records($onadb, 'blocks', "id != {$block['id']} AND name LIKE '{$options['set_name']}'");

        if ($status or $rows) {
            printmsg("DEBUG => The block {$options['set_name']} already exists!",3);
            $self['error'] = "ERROR => The block {$options['set_name']} already exists!";
            return(array(4, $self['error'] . "\n"));
        }
        $SET['name'] = $options['set_name'];
    }

    if ($options['set_start']) {
        $SET['ip_addr_start'] = ip_mangle($options['set_start'], 'numeric');
    }

    if ($options['set_end']) {
        $SET['ip_addr_end'] = ip_mangle($options['set_end'], 'numeric');
    }

    if (array_key_exists('set_notes', $options)) {
        // There is an issue with escaping '=' and '&'.  We need to avoid adding escape characters
        $options['set_notes'] = str_replace('\\=','=',$options['set_notes']);
        $options['set_notes'] = str_replace('\\&','&',$options['set_notes']);
        if ($options['set_notes'] != $block['notes']) $SET['notes'] = $options['set_notes'];
    }

    // Check permissions
    if (!auth('advanced')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the block record before updating (logging)
    list($status, $rows, $original_block) = ona_get_block_record(array('id' => $block['id']));

    // Update the record
    list($status, $rows) = db_update_record($onadb, 'blocks', array('id' => $block['id']), $SET);
    if ($status or !$rows) {
        $self['error'] = "ERROR => block_modify() SQL Query failed: " . $self['error'];
        printmsg($self['error'],0);
        return(array(6, $self['error'] . "\n"));
    }
    // Get the block record before updating (logging)
    list($status, $rows, $new_block) = ona_get_block_record(array('id' => $block['id']));

    if ($SET['name'] != $block['name'])
        $new_name = " => {$SET['name']}";

    // Return the success notice
    $self['error'] = "INFO => Block UPDATED:{$block['id']}: {$block['name']} {$new_name}";

    $log_msg = "INFO => Block UPDATED:{$block['id']}: ";
    $more="";
    foreach(array_keys($original_block) as $key) {
        if($original_block[$key] != $new_block[$key]) {
            $log_msg .= $more . $key . "[" .$original_block[$key] . "=>" . $new_block[$key] . "]";
            $more= ";";
        }
    }

    // only print to logfile if a change has been made to the record
    if($more != '') {
        printmsg($self['error'], 0);
        printmsg($log_msg, 0);
    }

    return(array(0, $self['error'] . "\n"));
}














// DON'T put whitespace at the beginning or end of this file!!!
?>