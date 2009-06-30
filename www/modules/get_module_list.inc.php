<?php
// DON'T put whitespace at the beginning or end of this file!!!




///////////////////////////////////////////////////////////////////////
//  Function: get_module_list (string $options='')
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
//  Example: list($status, $result) = get_module_list('type=string');
///////////////////////////////////////////////////////////////////////
function get_module_list($options="type=string") {
    global $conf, $self, $onadb;
    printmsg('DEBUG => get_module_list('.$options.') called', 3);

    // Version - UPDATE on every edit!
    $version = '1.01';

    // Parse incoming options string to an array
    $options = parse_options($options);

    // Return the usage summary if we need to
    if ($options['help'] or !$options['type']) {
        $self['error'] = 'ERROR => Insufficient parameters';
        // NOTE: Help message lines should not exceed 80 characters for proper display on a console
        return(array(1,
<<<EOM

get_module_list-v{$version}
Returns a list of available DCM modules

  Synopsis: get_module_list(OPTIONS)

  Options:
    type=<string|perl|array>  format module list in the specified format
                                string = human readable for console
                                perl   = hash for perl parsing
                                array  = php array
\n
EOM

        ));
    }

    // $pad_length is the amount of padding to put after each NAME.
    $pad_length = 25;
    $modules_string = str_pad('NAME', $pad_length) . " :: DESCRIPTION\n";
    $modules_perl   = "";
    $modules_array  = array();

    // Get a list of the valid "modules" and their descriptions.

    // FIXME: move this to the db later!
    list($status, $rows, $modules) = db_get_records($onadb, 'dcm_module_list', '1', 'name');
    printmsg("DEBUG => get_module_list() found {$rows} modules in db", 4);
    foreach ($modules as $module) {
        if ($module['name'] != 'get_module_list') {
            $modules_string .= str_pad($module['name'], $pad_length) . " :: {$module['description']}\n";
        }
        $modules_array[$module['name']] = $module['description'];
        $modules_perl .= "\$modules{'{$module['name']}'} = \"{$module['description']}\";\n";
    }

    // Return the list of modules as a string or array.
    if ($options['type'] == 'string')
        return (array(0, "\n" . $modules_string . "\n"));
    else if ($options['type'] == 'array')
        return(array(0, $modules_array));
    else if ($options['type'] == 'perl')
        return(array(0, $modules_perl));
    else
        return(array(3, "ERROR => get_module_list() Invalid \"type\" specified!"));
}


///////////////////////////////////////////////////////////////////////
//  Function: add_module (string $options='')
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
//  Example: list($status, $result) = add_module('');
///////////////////////////////////////////////////////////////////////
function add_module($options="") {
    global $conf, $self, $onadb;
    printmsg('DEBUG => add_module('.$options.') called', 3);

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

add_module-v{$version}
Registers a new DCM module, this should be used by install scripts that are
creating new functionality that requires a registered module.

  Synopsis: add_module(OPTIONS)

  Options:
    name=STRING         Name of DCM module
    desc=STRING         Quoted string to describe this module
    file=STRING         Path to php file, relative to {$conf['dcm_module_dir']}

\n
EOM

        ));
    }


    // Get a list of the valid "modules" and their descriptions.
    list($status, $rows, $modules) = db_get_record($onadb, 'dcm_module_list', array('name' => $options['name']), '');
    if ($rows) {
        $self['error'] = "ERROR => add_module() Module name already exists: {$options['name']}";
        printmsg($self['error'],0);
        return(array(1, $self['error'] . "\n"));
    }

    // Add the record
    list($status, $rows) =
        db_insert_record(
            $onadb,
            'dcm_module_list',
            array(
                'name'            => $options['name'],
                'description'     => $options['desc'],
                'file'            => $options['file'],
            )
        );
    if ($status or !$rows) {
        $self['error'] = "ERROR => add_module() SQL Query failed: " . $self['error'];
        printmsg($self['error'],0);
        return(array(2, $self['error'] . "\n"));
    }


    // Return the success notice
    $self['error'] = "INFO => Module ADDED: {$options['name']} [{$options['desc']}] => {$options['file']}";
    printmsg($self['error'],0);
    return(array(0, $self['error'] . "\n"));
}



?>