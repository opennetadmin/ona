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
    $version = '1.06';

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

    // Set "options[commit] to no if it's not set
    if (!array_key_exists('dataarray', $options)) {
        $options['dataarray'] = 'N';
    }
    // Otherwise sanitize it's value
    else {
        $options['dataarray'] = sanitize_YN($options['dataarray'], 'N');
    }

    // Set "options[header] to yes if it's not set
    if (!array_key_exists('header', $options)) {
        $options['header'] = 'Y';
    }
    // Otherwise sanitize it's value
    else {
        $options['header'] = sanitize_YN($options['header'], 'Y');
    }

    // Check permissions
    if (!auth('ona_sql')) {
        $self['error'] = "Permission denied!";
        printmsg($self['error'], 0);
        return(array(10, $self['error'] . "\n"));
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
    * Some plugins may provide their own SQL dir inside the plugin directory
    * Use the show option to display contents of SQL files, this should contain
      a long description and any usage information that is needed.
\n
EOM

        ));
    }

    // TODO: check that the user has admin privs? or at least a ona_sql priv

    // Get a list of the files
    $plugins = plugin_list();
    $files   = array();
    $srvdirs = array();
    $sqlopts = array();
    array_push($srvdirs, $srvdir);
    // add a local sql dir as well so they don't get overrriden by installs
    array_push($srvdirs, dirname($base).'/www/local/sql');

    // loop through the plugins and find files inside of their sql directories.
    foreach($plugins as $plug) {
        array_push($srvdirs, $plug['path'].'/sql');
    }

    // Loop through each of our plugin directories and the default directory to find .sql files
    foreach ($srvdirs as $srvdir) {
        if ($handle = @opendir($srvdir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && substr($file, -4) == '.sql') {
                    // Build an array of filenames
                    array_push($files, $srvdir.'/'.$file);
                }
            }
            closedir($handle);
        }
    }

    // sort the file names
    asort($files);

    // List the sql files on the server side
    if ($options['list'] == 'Y') {
        $text .= sprintf("\n%-25s%s\n",'FILE','DESCRIPTION');
        $text .= sprintf("%'-80s\n",'');

        // Loop through and display info about the files
        foreach($files as $file) {
                // Open the file and get the first line, this is the short description
                $fh = fopen($file, 'r');
                $desc = rtrim(fgets($fh));
                fclose($fh);

                // Print the info
                $text .= sprintf("%-25s%s\n",basename($file),$desc);
        }
        $text .= "\n";


        return(array(0, $text));
    }

    // Check that the sql variable passsed matches a file name locally, if it does, open it and replace $options['sql'] with it
    // Loop through files array till we find the right file
    $foundfile=false;
    foreach($files as $file) {
        if (strstr($file,$options['sql'])) {
            $options['sql'] = trim(file_get_contents($file));   
            $foundfile=true;
        }
    }

    // if we have not found a file on the server and the sql option does end in .sql then print a message that we coulnt find a file
    // otherwise assume it is a sql statement being passed at the cli
    if($foundfile==false and substr($options['sql'], -4) == '.sql') {
        $self['error'] = "ERROR => Unable to find specified SQL stored on server: {$options['sql']}";
        printmsg($self['error'],2);
        return(array(10, $self['error']."\n"));
    }

    // Show the contents of the sql query for usage info etc.
    if ($options['show'] == 'Y') {
        $text .= $options['sql']."\n\n";

        return(array(0, $text));
    }

    // Count how many ?s there are in the sql query. that must match how many sqlopts are passed
    // if this is an oracle database you could change the ? to a :.. more work on this however needs to be done
    $qvars = substr_count($options['sql'], '?');

    // loop through the options based on how many qvars are in the sql statement. print an error if we didnt
    // get a variable to use in the sql statement
    for ($i = 1; $i <= $qvars; $i++) {
        if (!array_key_exists($i,$options)) {
            $self['error'] = "ERROR => You did not supply a value for bind variable {$i}!";
            printmsg($self['error'],2);
            return(array(10, $self['error']."\n"));
        }
        // assign the variables to sqlopts
        $sqlopts[$i] = $options[$i];
    }

    // One last check to be sure
    // Count how many times ? is in the sql statement.  there should be that many elements in sqlopts
    if (count($sqlopts) != $qvars) {
        $self['error'] = "ERROR => SQL query and bind variable count did not match.";
        printmsg($self['error'],2);
        return(array(1, $self['error']."\n"));
    }


    printmsg("DEBUG => [ona_sql] Running SQL query: {$options['sql']}",5);

    // Run the query
    $rs = $onadb->Execute($options['sql'],$sqlopts);

    if ($rs === false) {
        $self['error'] = "ERROR => SQL query failed: " . $onadb->ErrorMsg() . "\n";
        return(array(2, $self['error']));
    }

    $text = "";
    $dataarr = array();

    // If we got a record, that means they did a select .. display it
    if ($rs->RecordCount()) {
        $build_header = 1;
        $i=0;
        // Loop through each record returned by the sql query
        while (!$rs->EOF) {
            $i++;
            $record = $rs->FetchRow();

            $dataarr[$i] = $record;


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

    // If we want the recordset returned instead of the text
    if ($options['dataarray'] == 'Y') {
        return(array(0, $dataarr));
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
