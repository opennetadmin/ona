<?php
//
// MP: Provided with ONA to help users convert older databases. Use at your own risk and backup your data!
//     If you are running multiple contexts, you must run this for each database instance.
//     Dont forget to update the username,password settings below.
//
// original script (v1.0) by/from: http://www.phpwact.org/php/i18n/utf-8/mysql
// improved/modified (v1.04) by Bogdan http://bogdan.org.ua/
// this script will output all queries needed to change all fields/tables to a different collation
// it is HIGHLY suggested you take a MySQL dump/backup prior to running any of the generated queries
// this code is provided AS IS and without any warranty
// add text/plain header when used not as a CLI script; PHP CLI SAPI shouldn't output headers
//
/////////////////////////// UPDATE INFO HERE ///////////////////////////////////////
// DB login information - *modify before use*
$username = 'user';
$password = 'pass';
$database = 'ona';
$host	  = 'localhost';
////////////////////////////////////////////////////////////////////////////////////

//-- usually, there is nothing to modify below this line --//
header("Content-Type: text/plain");

// precaution
//die("Make a backup of your MySQL database, then remove this line from the code!\n");

set_time_limit(0);

// collation you want to change to:
$convert_to   = 'utf8_general_ci';

// character set of new collation:
$character_set= 'utf8';

// show TABLE alteration queries?
$show_alter_table = true;

// show FIELD alteration queries?
$show_alter_field = true;

mysql_connect($host, $username, $password);
mysql_select_db($database);

$rs_tables = mysql_query(" SHOW TABLES ") or die(mysql_error());

while ($row_tables = mysql_fetch_row($rs_tables)) {

	$table = mysql_real_escape_string($row_tables[0]);

	// Alter table collation
	// ALTER TABLE `account` DEFAULT CHARACTER SET utf8
	if ($show_alter_table)
		echo("ALTER TABLE `$table` DEFAULT CHARACTER SET $character_set;\n");

	$rs = mysql_query(" SHOW FULL FIELDS FROM `$table` ") or die(mysql_error());

	while ( $row = mysql_fetch_assoc($rs) ) {
		if ( $row['Collation'] == '' || $row['Collation'] == $convert_to )
			continue;

		// Is the field allowed to be null?
		if ( $row['Null'] == 'YES' )
			$nullable = ' NULL ';
		else
			$nullable = ' NOT NULL';

		// Does the field default to null, a string, or nothing?
		if ( $row['Default'] === NULL )
			$default = $row['Null'] == 'YES' ? " DEFAULT NULL " : "";
		elseif ( $row['Default'] != '' )
			$default = " DEFAULT '".mysql_real_escape_string($row['Default'])."'";
		else
			$default = '';

		// Don't alter int columns as they do not have a collation anyway and it buggers up the extra parameters
		if (strpos($row[Type], 'int') !== false)
			$show_alter_field = false;
		else
			$show_alter_field = true;

		// Alter field collation:
		// ALTER TABLE `tab` CHANGE `fiel` `fiel` CHAR( 5 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
		if ($show_alter_field) {
			$field = mysql_real_escape_string($row['Field']);
			echo "ALTER TABLE `$table` CHANGE `$field` `$field` $row[Type] CHARACTER SET $character_set COLLATE $convert_to $nullable $default;\n";
		}
	}
}

?>
