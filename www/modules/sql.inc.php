<?

///////////////////////////////////////////////////////////////////////
//  Function: ona_sql (string $options='')
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
///////////////////////////////////////////////////////////////////////
function ona_sql($options="") {
    
    // The important globals
    global $conf;
    global $onadb;
    
    // Version - UPDATE on every edit!
    $version = '1.00';
    
    printmsg('DEBUG => ona_sql('.$options.') called', 3);
    
    // Parse incoming options string to an array
    $options = parse_options($options);
    
    // Sanitize delimeter
    if (!$options['delimiter']) {
        $options['delimiter'] = ':';
    }
    
    // Set "options[commit] to no if it's not set
    if (!array_key_exists('commit', $options)) {
        $options['commit'] = 'N';
    }
    // Otherwise sanitize it's value
    else {
        $options['commit'] = sanitize_YN($options['commit'], 'N');
    }
    
    // Set "options[header] to yes if it's not set
    if (!array_key_exists('header', $options)) {
        $options['header'] = 'Y';
    }
    // Otherwise sanitize it's value
    else {
        $options['header'] = sanitize_YN($options['header'], 'Y');
    }
    
    // Return the usage summary if we need to
    if ($options['help'] or !$options['sql']) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1, 
<<<EOM

ona_sql-v{$version}
Runs the specified SQL query on the database and prints the result

  Synopsis: ona_sql [KEY=VALUE] ...

  Required:
    sql=SQL_STATEMENT        quoted SQL statement to execute

  Optional:
    commit=yes|no            commit the transaction (no)
    header=yes|no            display record header (yes)
    delimiter=DELIMITER      record delimiter for output (:)

  Notes:
    * Query is sent to the configured ona database server
\n
EOM

        ));
    }
    
    
    // Run the query
    $rs = $onadb->Execute($options['sql']);
    
    if ($rs === false) {
        $self['error'] = 'ERROR => SQL query failed: ' . $onadb->ErrorMsg(); 
        return(array(1, $self['error']));
    }
    
    $text = "";
    
    // If we got a record, that means they did a select .. display it
    if ($rs->RecordCount()) {
        $build_header = 1;
        
        // Loop through each record returned by the sql query
        while (!$rs->EOF) {
            $record = $rs->FetchRow();
            
            // Build the header if we need to
            if ($build_header == 1 and $options['header'] == 'Y') {
                $build_header = 0;
                foreach (array_keys($record) as $key) {
                    $text .= $key . $options['delimiter'];
                }
                $text = preg_replace("/{$options['delimiter']}$/", "", $text);
                $text .= "\n";
            }
            
            // Display the row
            foreach (array_keys($record) as $key) {
                $text .= $record[$key] . $options['delimiter'];
            }
            $text = preg_replace("/{$options['delimiter']}$/", "", $text);
            $text .= "\n";
        }
    }
    
    else {
        $text .= "NOTICE => SQL executed successfully - no records returned\n";
    }
    
    
    // Unless the user said YES to commit, return a non-zero
    // exit status so that module_run.php doesn't commit the DB transaction.
    $return = 1;
    if ($options['commit'] == 'Y') {
        $return = 0;
    }
    
    return(array($return, $text));
}







?>