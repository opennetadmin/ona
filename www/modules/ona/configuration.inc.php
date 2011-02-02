<?php

// Make sure we have necessary functions & DB connectivity
require_once($conf['inc_functions_db']);




///////////////////////////////////////////////////////////////////////
//  Function: find_config (array $search)
//  
//  Input Options:
//    $search = See functions below for how this function is used.
//              It's an internal function.
//  
//  Output:
//    Returns the data returned from get_config_record()
//    Error messages are stored in global $self['error']
//  
///////////////////////////////////////////////////////////////////////
function find_config($options=array()) {
    
    global $self;
    
    $status = 1;
    $rows = 0;
    $config = array();
    
    // If the user specified a config text ID
    if ($options['config']) {
        if (!preg_match('/^\d+$/', $options['config'])) {
            $self['error'] = "ERROR => A non-digit config ID was specified!";
            return(array(2, 0, array()));
        }
        
        list($status, $rows, $config) = ona_get_config_record(array('id' => $options['config']));
    }
    
    // Otherwise we're selecting a config by hostname and type
    else if ($options['host'] and $options['type']) {
        // Search for the host first
        list($status, $rows, $host) = ona_find_host($options['host']);
        
        // Error if the host doesn't exist
        if (!$host['id']) {
            $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
            return(array(3, 0, array()));
        }
        
        // Now find the ID of the config type they entered
        list($status, $rows, $config_type) = ona_get_config_type_record(array('name' => $options['type']));
        if (!$config_type['id']) {
            $self['error'] = "ERROR => The config type specified, {$options['type']}, is invalid!";
            return(array(4, 0, array()));
        }
        
        // Select the first config record of the specified type and host
        list($status, $rows, $config) = ona_get_config_record(array('host_id' => $host['id'],
                                                                     'configuration_type_id' => $config_type['id']));
    }
    
    // Return the config record we got
    return(array($status, $rows, $config));
    
}










///////////////////////////////////////////////////////////////////////
//  Function: config_display (string $options='')
//  
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//  
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. A textual message displaying information on the selected unit
//         record from the database.
//  
//  Example: list($status, $text) = config_display('config=12345');
//  
//  Exit codes:
//    0  :: No error
//    1  :: Help text printed - Insufficient or invalid input received
//    2  :: No config text entries found!
///////////////////////////////////////////////////////////////////////
function config_display($options="") {
    
    // The important globals
    global $conf;
    global $self;
    global $onadb;
    
    // Version - UPDATE on every edit!
    $version = '1.02';
    
    printmsg('DEBUG => config_display('.$options.') called', 3);
    
    // Parse incoming options string to an array
    $options = parse_options($options);
    
    // Set "options[verbose] to yes if it's not set
    if (!array_key_exists('verbose', $options)) {
        $options['verbose'] = 'Y';
    }
    // Otherwise sanitize it's value
    else {
        $options['verbose'] = sanitize_YN($options['verbose']);
    }
    
    // Return the usage summary if we need to
    if ($options['help'] or ( (!$options['config']) and (!$options['host'] or !$options['type']) ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1, 
<<<EOM

config_display-v{$version}
Displays a config text record from the database
  
  Synopsis: config_display [KEY=VALUE] ...
  
  Required:
    config=ID                   display config by record ID
      - or -
    host=ID or NAME[.DOMAIN]    display most recent config for specified host
    type=TYPE                   type of config to display -
                                  usually "IOS_VERSION" or "IOS_CONFIG"
  Optional:
    verbose=[yes|no]            display entire record (yes)
                                  "no" displays only the actual config text
\n
EOM

        ));
    }
    
    
    // Get a config record if there is one
    $self['error'] = "";
    list($status, $rows, $config) = ona_find_config($options);
    
    // Error if an error was returned
    if ($status or !$config['id']) {
        $text = "";
        if ($self['error']) { $text = $self['error'] . "\n"; }
        $text .= "ERROR => No config text entries found!\n";
        return(array(2, $text));
    }
    
    // If 'verbose' is enabled, we display the entire record
    if ($options['verbose'] == 'Y') {
        // Build text to return
        $text  = "CONFIG TEXT RECORD (1 of {$rows})\n";
        $text .= format_array($config);
    }
    
    // Otherwise we return only the actual config text
    else {
        $text = $config['config_body'];
    }
    
    // Return the success notice
    return(array(0, $text));
    
}










///////////////////////////////////////////////////////////////////////
//  Function: config_chksum (string $options='')
//  
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//  
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. A textual message displaying information on the selected unit
//         record from the database.
//  
//  Example: list($status, $result) = config_chksum('config=12345');
//  
//  Exit codes:
//    0  :: No error
//    1  :: Help text printed - Insufficient or invalid input received
//    2  :: No config text entries found!
///////////////////////////////////////////////////////////////////////
function config_chksum($options="") {
    
    // The important globals
    global $conf;
    global $self;
    global $onadb;
    
    // Version - UPDATE on every edit!
    $version = '1.00';
    
    printmsg('DEBUG => config_chksum('.$options.') called', 3);
    
    // Parse incoming options string to an array
    $options = parse_options($options);
    
    // Return the usage summary if we need to
    if ($options['help'] or ( (!$options['config']) and (!$options['host'] or !$options['type']) ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1, 
<<<EOM

config_chksum-v{$version}
Displays the chksum of a config text record from the database
  
  Synopsis: config_chksum [KEY=VALUE] ...
  
  Required:
    config=ID                   display config by record ID
      - or -
    host=ID or NAME[.DOMAIN]    display most recent config for specified host
    type=TYPE                   type of config to display -
                                  usually "IOS_VERSION" or "IOS_CONFIG"
\n
EOM

        ));
    }
    
    // Get a config record if there is one
    $self['error'] = "";
    list($status, $rows, $config) = ona_find_config($options);
    
    // Error if an error was returned
    if ($status or !$config['id']) {
        $text = "";
        if ($self['error']) { $text = $self['error'] . "\n"; }
        $text .= "ERROR => No config text entries found!\n";
        return(array(2, $text));
    }
    
    // Build text to return
    $text  = $config['md5_checksum'] . "\n";
    
    // Return the success notice
    return(array(0, $text));
    
}












///////////////////////////////////////////////////////////////////////
//  Function: config_add (string $options='')
//  
//  Input Options:
//    $options = key=value pairs of options for this function.
//               multiple sets of key=value pairs should be separated
//               by an "&" symbol.
//  
//  Output:
//    Returns a two part list:
//      1. The exit status of the function (0 on success, non-zero on error)
//      2. A textual message displaying information on the selected unit
//         record from the database.
//  
//  Example: list($status, $result) = config_chksum('config=12345');
//  
//  Exit codes:
//    0  :: No error
//    1  :: Help text printed - Insufficient or invalid input received
//    2  :: Host specified doesn't exist
//    3  :: Invalid config type specified
//    4  :: The ona_get_next_id() call failed
//    5  :: SQL INSERT failed
///////////////////////////////////////////////////////////////////////
function config_add($options="") {
    
    // The important globals
    global $conf;
    global $self;
    global $onadb;
    
    // Version - UPDATE on every edit!
    $version = '1.00';
    
    // This debug is set very high as it can contain large configs and sensitive data, you gotta mean it!
    printmsg('DEBUG => config_add('.$options.') called', 7);
    
    // Parse incoming options string to an array
    $options = parse_options($options);
    
    // Return the usage summary if we need to
    if ($options['help'] or !($options['host'] and $options['type'] and $options['config']) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1, 
<<<EOM

config_add-v{$version}
Adds a new config text record into the database

  Synopsis: config_add [KEY=VALUE] ...

  Required:
    config=TEXT                 the actual config text or filename to insert
    host=ID or NAME[.DOMAIN]    host the config text is from
    type=TYPE                   type of config text we're inserting -
                                  usually "IOS_VERSION" or "IOS_CONFIG"
\n
EOM

        ));
    }
    
    
    // Search for the host first
    list($status, $rows, $host) = ona_find_host($options['host']);
    
    // Error if the host doesn't exist
    if (!$host['id']) {
        $self['error'] = "ERROR => The host specified, {$options['host']}, does not exist!";
        return(array(2, $self['error']));
    }
    
    // Now find the ID of the config type they entered
    list($status, $rows, $config_type) = ona_get_config_type_record(array('name' => $options['type']));
    if (!$config_type['id']) {
        $self['error'] = "ERROR => The config type specified, {$options['type']}, is invalid!";
        return(array(3, $self['error']));
    }
    
    $options['config'] = preg_replace('/\\\"/','"',$options['config']);
    $options['config'] = preg_replace('/\\\=/','=',$options['config']);
    // Get the next ID for the new config_text record
    $id = ona_get_next_id('configurations');
    if (!$id) {
        return(array(4, "ERROR => The ona_get_next_id(configurations) call failed!\n"));
    }
    printmsg("DEBUG => ID for new config_record: $id", 3);
    
    // Add the config_text
    list($status, $rows) = db_insert_record(
        $onadb,
        'configurations',
        array(
            'id'                      => $id,
            'configuration_type_id'   => $config_type['id'],
            'host_id'                 => $host['id'],
            'md5_checksum'            => md5($options['config']),
            'config_body'             => $options['config']
        )
    );
    if ($status or !$rows) {
        $self['error'] = "ERROR => message_add() SQL Query failed: " . $self['error'];
        printmsg($self['error'], 0);
        return(array(6, $self['error'] . "\n"));
    }
    
    list($status, $rows, $record) = ona_get_config_record(array('id'    => $id));
    if ($status or !$rows) {
        $self['error'] = 'ERROR => SQL INSERT failed.  Database error: ' . $error . "\n";
        return(array(5, $self['error']));
    }
    
    // Return the success notice
    $text = "NOTICE => Config text record ADDED, ID: {$id}\n";
    return(array(0, $text));
    
}








/*
 Function: config_diff (string $options='')
 
 Input Options:
   $options = key=value pairs of options for this function.
              multiple sets of key=value pairs should be separated
              by an "&" symbol.
 
 Output:
   Returns a two part list:
     1. The exit status of the function (0 on success, non-zero on error)
     2. A textual message displaying information on the selected unit
        record from the database.
 
 Example: list($status, $text) = config_diff('config=12345');
 
 Exit codes:
   0  :: No error
   1  :: Help text printed - Insufficient or invalid input received
   2  :: No config text entries found!
*/
function config_diff($options="") {
    
    // The important globals
    global $conf;
    global $self;
    global $onadb;
    
    // Version - UPDATE on every edit!
    $version = '1.03';
    
    printmsg('DEBUG => config_diff('.$options.') called', 3);
    
    // Parse incoming options string to an array
    $options = parse_options($options);
    
    // Return the usage summary if we need to
    if ($options['help'] or ( !$options['host'] or !$options['type'] ) and ( !$options['ida'] or !$options['idb'] ) ) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1, 
<<<EOM

config_diff-v{$version}
Displays the difference between selected archive entries
  
  Synopsis: config_diff [KEY=VALUE] ...
  
  Required:
    host=ID or NAME[.DOMAIN]    display most recent config for specified host
    type=TYPE                   type of config to display -
                                  usually "IOS_VERSION" or "IOS_CONFIG"
     OR
    ida=ID                      First config ID to compare against idb
    idb=ID                      Second config ID to compare against ida

  Note:
    If you don't pass any IDs you will get the two most recent configs
    related to the host/type you provide.

EOM
        ));
    }

    $text = "";

    // Compare arbitrary configs based on config IDs
    // If we have ids, lets use those instead
    if ($options['ida'] and $options['idb']) {
        // get the two configs from the db
        list($status, $rows, $configs) = db_get_records($onadb,'configurations',
                                               "id in ({$options['ida']},{$options['idb']})",
                                               'ctime DESC',
                                               '2',
                                               ''
                                           );
    } else {
    // Get a config record if there is one
    $self['error'] = "";
    list($status, $rows, $config) = ona_find_config($options);
    list($status, $rows, $configs) = db_get_records($onadb,'configurations',
                                               array('host_id' => $config['host_id'],'configuration_type_id' => $config['configuration_type_id']),
                                               'ctime DESC',
                                               '2',
                                               ''
                                           );
    }

    // Error if an error was returned
    if ($status or $rows != 2) {
        if ($self['error']) { $text = $self['error'] . "\n"; }
        $text .= "ERROR => One or more config text entries not found!\n";
        return(array(2, $text));
    }
    
    // Get a unified text diff output
    $text .= text_diff($configs[1]['config_body'], $configs[0]['config_body']);

    // Return the success notice
    return(array(0, $text));
    
}


?>
