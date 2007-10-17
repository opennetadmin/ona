<?

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
    
    printmsg('DEBUG => config_add('.$options.') called', 3);
    
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
    
    // Get the next ID for the new config_text record
    $id = ona_get_next_id('configurations');
    if (!$id) {
        return(array(4, "ERROR => The ona_get_next_id(configurations) call failed!\n"));
    }
    printmsg("DEBUG => ID for new config_record: $id", 3);
    
    // Add the config_text
    // I guess we don't add anything to the APPROVED_DT field..?
    $q = 'INSERT INTO configurations (
              ID, 
              CONFIGURATION_TYPE_ID,
              HOST_ID,
              MD5_CHECKSUM,
              CONFIG_BODY
          )
          VALUES (' . 
              $onadb->qstr($id) . ', ' .
              $onadb->qstr($config_type['id']) . ', ' .
              $onadb->qstr($host['id']) . ', ' .
              $onadb->qstr(md5($options['config'])) . ', ' .
              $onadb->qstr($options['config']) .
          ')';
    $ok = $onadb->Execute($q);
    $error = $onadb->ErrorMsg();
    
    list($status, $rows, $record) = ona_get_config_record(array('id'    => $id));
    if ($ok === false or $status or $rows != 1) {
        $self['error'] = 'ERROR => SQL INSERT failed.  Database error: ' . $error . "\n";
        return(array(5, $self['error']));
    }
    
    // Return the success notice
    $text = "NOTICE => Config text record ADDED, ID: {$id}\n";
    return(array(0, $text));
    
}















?>