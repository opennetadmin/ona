<?php

/* 

Uncomment and set the following to enable ldap auth settings for your environment
It is best to make a copy of this file and put it into the following path:

/opt/ona/www/local/config/auth_ldap.config.php

This file is for documentation purposes and will be overwritten during 
upgrades of ONA.  The ldap code was patterend from the DokuWiki auth
plugins.  You can find documentation here that may be of use in
defining values below: http://www.dokuwiki.org/auth:ldap

*/

// Common settings and debugging
//$conf['auth']['ldap']['debug'] = 'true';
//$conf['auth']['ldap']['version'] = '3';
//$conf['auth']['ldap']['server'] = 'ldap://ldap.example.com:389';

// Active Directory DN bind as user example
//$conf['auth']['ldap']['binddn'] = '%{user}@example.local';
//$conf['auth']['ldap']['usertree'] = 'DC=example,DC=local';
//$conf['auth']['ldap']['userfilter']  = '(sAMAccountName=%{user})';
//$conf['auth']['ldap']['grouptree'] = 'DC=example,DC=local';
//$conf['auth']['ldap']['groupfilter']  = '(&(cn=*)(Member=%{dn})(objectClass=group))';
//$conf['auth']['ldap']['mapping']['grps'] = array('memberOf'=>'/cn=(.+?),/i');
//$conf['auth']['ldap']['referrals'] = '0';


// Novell E-Directory, anonymous bind example
//$conf['auth']['ldap']['usertree'] = 'cn=%{user},ou=users,ou=example,o=com';
//$conf['auth']['ldap']['mapping']['grps'] = array('groupmembership'=>'/cn=(.+?),/i');
//$conf['auth']['ldap']['userfilter']  = '(&(!(loginDisabled=TRUE)))';
