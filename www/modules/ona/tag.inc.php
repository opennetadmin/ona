<?php
// DON'T put whitespace at the beginning or end of this file!!!



///////////////////////////////////////////////////////////////////////
//  Function: tag_add (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    name=STRING
//    type=STRING
//    reference=NUMBER
//
//  Output:
//    Adds an tag into the database called 'name' 
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = tag_add('name=test&type=blah&reference=1');
///////////////////////////////////////////////////////////////////////
function tag_add($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.01';

    printmsg("DEBUG => tag_add({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Possible types
    $allowed_types = array('subnet', 'host');

    $typetext=implode(', ',$allowed_types);
    // Return the usage summary if we need to
    if ($options['help'] or !($options['type'] and $options['name'] and $options['reference']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

tag_add-v{$version}
Adds a tag into the database assigned to the specified type of data.

  Synopsis: tag_add [KEY=VALUE] ...

  Required:
    name=STRING            Name of new tag.
    type=STRING            Type of thing to tag, Possible types listed below.
    reference=ID|STRING    Reference to apply the tag to. ID or name used to find
                           a record to attatch to.

  Possible types of tags:
    {$typetext}
\n
EOM

        ));
    }

    // Check if provided type is in the allowed types
    $options['type'] = strtolower(trim($options['type']));
    if (!in_array($options['type'], $allowed_types)) {
        $self['error'] = "ERROR => Invalid tag type: {$options['type']}";
        printmsg($self['error'], 0);
        return(array(1, $self['error'] . "\n"));
    }

    // The formatting rule on tag input
    $options['name'] = preg_replace('/\s+/', '-', trim($options['name']));
    if (preg_match('/[@$%^*!\|,`~<>{}]+/', $options['name'])) {
        $self['error'] = "ERROR => Invalid character in tag name";
        printmsg($self['error'], 0);
        return(array(1, $self['error'] . "\n"));
    }
    $options['reference'] = (trim($options['reference']));

    // Use the find functions based on the type 
    // this requires allowed types to have an 'ona_find_' related function
    eval("list(\$status, \$rows, \$reference) = ona_find_".$options['type']."('".$options['reference']."');");

    if ($status or !$rows) {
        $self['error'] = "ERROR => Unable to find a {$options['type']} matching {$options['reference']}";
        printmsg($self['error'], 0);
        return(array(1, $self['error'] . "\n"));
    }

    // Validate that there isn't already an tag of this type associated to the reference
    list($status, $rows, $tags) = db_get_records($onadb, 'tags',array('type' => $options['type'],'reference' => $reference['id']));

    foreach ($tags as $t) {
      if (in_array($options['name'], $t)) {
        printmsg("DEBUG => The tag {$options['name']} is already associated with this {$options['type']}!",3);
        $self['error'] = "ERROR => The tag {$options['name']} is already associated with this {$options['type']}!";
        return(array(3, $self['error'] . "\n"));
      }
    }


    // Check permissions
    if (! (auth('subnet_add') or auth('host_add')) ) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
    }

    // Get the next ID for the new tag
    $id = ona_get_next_id('tags');
    if (!$id) {
        $self['error'] = "ERROR => The ona_get_next_id() call failed!";
        printmsg($self['error'],0);
        return(array(5, $self['error'] . "\n"));
    }
    printmsg("DEBUG => ID for new tag: $id", 3);

    // Add the tag
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'tags',
            array(
                'id'             => $id,
                'name'           => $options['name'],
                'type'           => $options['type'],
                'reference'      => $reference['id']
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => tag_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }

    // Return the success notice
    $self['error'] = "INFO => {$options['type']} TAG ADDED: {$options['name']} to {$reference['name']}({$reference['id']}).";
    printmsg($self['error'],0);
    return(array(0, $self['error'] . "\n"));
}












///////////////////////////////////////////////////////////////////////
//  Function: tag_del (string $options='')
//
//  $options = key=value pairs of options for this function.
//             multiple sets of key=value pairs should be separated
//             by an "&" symbol.
//
//  Input Options:
//    tag=ID
//
//  Output:
//    Deletes an tag from the database.
//    Returns a two part list:
//      1. The exit status of the function.  0 on success, non-zero on
//         error.  All errors messages are stored in $self['error'].
//      2. A textual message for display on the console or web interface.
//
//  Example: list($status, $result) = tag_del('tag=19328');
///////////////////////////////////////////////////////////////////////
function tag_del($options="") {

    // The important globals
    global $conf, $self, $onadb;

    // Version - UPDATE on every edit!
    $version = '1.00';

    printmsg("DEBUG => tag_del({$options}) called", 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['tag'] ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        $self['error'] = 'ERROR => Insufficient parameters';
        return(array(1,
<<<EOM

tag_del-v{$version}
Deletes an tag from the database

  Synopsis: tag_del [KEY=VALUE] ...

  Required:
    tag=ID             ID of the tag to delete

  Optional:
    commit=[yes|no]     commit db transaction (no)

\n
EOM

        ));
    }


    // Sanitize options[commit] (default is no)
    $options['commit'] = sanitize_YN($options['commit'], 'N');

    // If the tag provided is numeric, check to see if it's an tag
    if (is_numeric($options['tag'])) {
        // See if it's a tag_id
        list($status, $rows, $tag) = db_get_record($onadb,'tags', array('id' => $options['tag']));
    }

        if (!$tag['id']) {
            printmsg("DEBUG => Unable to find tag ({$options['tag']})!",3);
            $self['error'] = "ERROR => Unable to find tag ({$options['tag']})!";
            return(array(2, $self['error'] . "\n"));
        }


    // If "commit" is yes, delete the record
    if ($options['commit'] == 'Y') {

        // Check permissions
        if (! (auth('host_del') or auth('subnet_del')) ) {
            $self['error'] = "Permission denied!";
            printmsg($self['error'], 0);
            return(array(10, $self['error'] . "\n"));
        }

        list($status, $rows) = db_delete_records($onadb, 'tags', array('id' => $tag['id']));
        if ($status or !$rows) {
            $self['error'] = "ERROR => tag_del() SQL Query failed: " . $self['error'];
            printmsg($self['error'], 0);
            return(array(4, $self['error'] . "\n"));
        }

        // Return the success notice
        $self['error'] = "INFO => TAG DELETED: {$tag['name']} from {$tag['type']}[{$tag['reference']}]";
        printmsg($self['error'],0);
        return(array(0, $self['error'] . "\n"));
    }


    // Otherwise display the record that would have been deleted
    $text = <<<EOL
Record(s) NOT DELETED (see "commit" option)
Displaying record(s) that would have been deleted:

    NAME:      {$tag['name']}
    TYPE:      {$tag['type']}
    REFERENCE: {$tag['reference']}


EOL;

    return(array(6, $text));

}






// DON'T put whitespace at the beginning or end of this file!!!
?>
