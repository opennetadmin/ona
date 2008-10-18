<?php

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
    global $conf, $onadb, $base;

    // Version - UPDATE on every edit!
    $version = '1.02';

    // TODO: Maybe make this into a sys_config option
    $srvdir = dirname($base)."/sql";

    printmsg('DEBUG => ona_sql('.$options.') called', 3);

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Sanitize delimeter
    if (!$options['delimiter']) {
        $options['delimiter'] = ':';
    }

    // fix up the escaped ' marks.  may need the = and & stuff too????
    $options['sql'] = str_replace('\\\'','\'',$options['sql']);
    $options['sql'] = str_replace('\\=','=',$options['sql']);

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
    if ($options['help'] or !(($options['list'] and !$options['sql']) or (!$options['list'] and $options['sql']))) {
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1, 
<<<EOM

ona_sql-v{$version}
Runs the specified SQL query on the database and prints the result

  Synopsis: ona_sql [KEY=VALUE] ...

  Required:
    sql=STATEMENT|FILENAME   quoted SQL statement to execute
    OR
    list                     lists the SQL files available on the server side

  Optional:
    show                     displays contents of SQL, gives usage etc
    commit=yes|no            commit the transaction (no)
    header=yes|no            display record header (yes)
    delimiter=DELIMITER      record delimiter for output (:)
    (1,2,..)=VALUE           bind variables, replaces ? in query sequentially.
                             the first ? found is replaced by 1=value, and so on

  Notes:
    * Query is sent to the configured OpenNetAdmin database server.
    * The use of bind variables requires your options to match positionally.
    * The SQL option will be tried first as a local file, then as a server
      file, then as a raw text SQL query.  Filenames are case sensitive.
    * Server based SQL files are located in {$srvdir}
    * Use the show option to display contents of SQL files, this should contain
      a long description and any usage information that is needed.
\n
EOM

        ));
    }

    // TODO: check that the user has admin privs? or at least a ona_sql priv

    // List the sql files on the server side
    if ($options['list'] == 'Y') {
        $text .= sprintf("\n%-25s%s\n",'FILE','DESCRIPTION');
        $text .= sprintf("%'-80s\n",'');
        $files = array();
        // Get a list of the files
        if ($handle = opendir($srvdir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    // Build an array of filenames
                    array_push($files, $file);
                }
            }
            closedir($handle);

            // sort the file names
            asort($files);

            // Loop through and display info about the files
            foreach($files as $file) {
                    // Open the file and get the first line, this is the short description
                    $fh = fopen($srvdir.'/'.$file, 'r');
                    $desc = rtrim(fgets($fh));
                    fclose($fh);

                    // Print the info
                    $text .= sprintf("%-25s%s\n",$file,$desc);
            }
            $text .= "\n";
        }

        return(array(0, $text));
    }

    // Check that the sql variable passsed matches a file name locally, if it does, open it and replace $options['sql'] with it
    if (file_exists($srvdir.'/'.$options['sql'])) {
        $options['sql'] = trim(file_get_contents($srvdir.'/'.$options['sql']));
    }

    // Show the contents of the sql query for usage info etc.
    if ($options['show'] == 'Y') {
        $text .= $options['sql']."\n\n";

        return(array(0, $text));
    }


    // Sort the options array so we get our numerical bind variables in the right order
    ksort($options);

    // After the array is sorted, get just the positional bind variable options.
    // The number to shift in the slice is variable but 5 works for now unless I add more things.
    $sqlopts = array_slice($options,5);

    // Count how many ?s there are in the sql query. that must match how many sqlopts are passed
    // if this is an oracle database you could change the ? to a :.. more work on this however needs to be done
    $qvars = substr_count($options['sql'], '?');

    // Count how many times ? is in the sql statement.  there should be that many elements in sqlopts
    if (count($sqlopts) != $qvars) {
        $self['error'] = "ERROR => SQL query and bind variable count did not match.\n"; 
        return(array(1, $self['error']));
    }


    printmsg("DEBUG => [ona_sql] Running SQL query: {$options['sql']}",5);

    // Run the query
    $rs = $onadb->Execute($options['sql'],$sqlopts);

    if ($rs === false) {
        $self['error'] = "ERROR => SQL query failed: " . $onadb->ErrorMsg() . "\n"; 
        return(array(2, $self['error']));
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