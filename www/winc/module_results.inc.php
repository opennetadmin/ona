<?
//
// This is not really a window at all.
// It is intended to be "included" by other code to build the html
// to display the output from a module it just ran.
// 
// The following variables must be pre-defined before including this file:
//   $output    The textual output from the module you just ran
// 
// The following are optional variables must both be deifned to enable the "commit" checkbox:
//   $build_commit_html = 1 | 0    Wether this code should build a commit checkbox and submit button.
//                                 We use this for almost every "delete" module.
//   $commit_function = function   The name of the xajax callback function that should be called when 
//                                 the submit button is pressed.
//  FYI, if you use the "commit" functionality, the created window MUST be called "{$window_name}_results"
//  for the Cancel button to work properly.
//  


global $color, $style;
if (!$window['title'])
    $window['title'] = "Module Output";

// This puts some nice color queues into the output
$output = str_replace("WARNING!", "<span style='font-weight: bold;color: red;'>WARNING!</span>", $output);

// Define the Window's inner html
$window['html'] = <<<EOL

<!-- Module Output -->
<table style="background-color: {$color['window_content_bg']}; padding-left: 25px; padding-right: 25px;" width="100%" cellspacing="0" border="0" cellpadding="0">
    <tr>
        <td align="left" class="padding">
            <br>
            <div style="border: solid 2px #000000; background-color: #FFFFFF; width: 650px; height: 350px; overflow: auto;">
                <pre style="padding: 4px;font-family: monospace;">{$output}</pre>
            </div>
        </td>
    </tr>
</table>

EOL;

// If we're not building the "commit" html, pad the bottom a little
if (!$build_commit_html) {
$window['html'] .= <<<EOL

<!-- Just a little padding -->
<table style="background-color: {$color['window_content_bg']}; padding-left: 25px; padding-right: 25px;" width="100%" cellspacing="0" border="0" cellpadding="0">
    <tr>
        <td id="{$window_name}_extras" align="center" class="padding"><input type="button" class="edit" name="Close" value="Close" onclick="removeElement('{$window_name}_results');"><br></td>
    </tr>
</table>

EOL;
}

// Otherwise take values in $form, and build a commit checkbox and submit button
else {

$window['html'] .= <<<EOL
<!-- Commit button or more text can go here -->
<table style="background-color: {$color['window_content_bg']}; padding-left: 25px; padding-right: 25px;" width="100%" cellspacing="0" border="0" cellpadding="0">
    <tr>
        <td id="{$window_name}_extras" align="right" class="padding">
            <form id="{$window_name}_extra_form">
EOL;

// For each element of $form, put a hidden input field into the form
foreach (array_keys($form) as $key) {
    list($key, $value) = array(str_replace('"', '&#034;', $key), str_replace('"', '&#034;', $form[$key]));
    $window['html'] .= <<<EOL
        <input type="hidden" name="{$key}" value="{$value}">
EOL;
}
$window['html'] .= <<<EOL
    Commit: <input type="checkbox" name="commit"><br>
    <input type="button" class="edit" name="Cancel" value="Cancel" onclick="removeElement('{$window_name}_results');">
    <!-- FIXME: The removeElement() below crashes Konqueror.. it seems that it tries removing the window before the xajax.getFormValues() has completed! -->
    <input type="button" class="edit" name="Submit" value="Submit" onclick="xajax_window_submit('{$window_name}', xajax.getFormValues('{$window_name}_extra_form'),'{$commit_function}'); removeElement('{$window_name}_results');">
EOL;


$window['html'] .= <<<EOL
            </form>
        </td>
    </tr>
</table>
EOL;

}

?>