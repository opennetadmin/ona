<?php

$auth = '';


/**
 * Loads the specified Authentication class
 *
 * Authentication classes are located in www/include/auth/
 *
 * if no Authentication type is passed, it will use the system
 * configured 'authtype'
 *
 * @author  Matt Pascoe <matt@opennetadmin.com>
 * @return  struct  Auth class structure
 */
function load_auth_class($authtype='') {
    global $base, $conf;
    // define a variable having the path to our auth classes
    define('ONA_AUTH', $base.'/include/auth');

    // use the system configured authtype if one was not passed in
    if (!$authtype) $authtype = $conf['authtype'];

    // If we STILL dont have an auth type set, use the local one as default
    if (!$authtype) $authtype = 'local';

    // clear out the auth variable
    unset($auth);

    // load the the backend auth functions and instantiate the auth object
    if (@file_exists(ONA_AUTH.'/'.$authtype.'.class.php')) {
        require_once(ONA_AUTH.'/local.class.php');
        require_once(ONA_AUTH.'/'.$authtype.'.class.php');

//FIXME: add some error logging in the web gui if we get failures here
        $auth_class = "auth_".$authtype;
        if (class_exists($auth_class)) {
            $auth = new $auth_class();
            if ($auth->success == false) {
                // degrade to unauthenticated user
                unset($auth);
                unset($_SESSION['ona']['auth']);
                printmsg("ERROR => Failure loading auth module: {$conf['authtype']}.", 0);
            }
        } else {
            printmsg("ERROR => Unable to find auth class: {$auth_class}.", 0);
        }
    } else {
        printmsg("ERROR => Auth module {$authtype} not in path: ".ONA_AUTH, 0);
    }
    return($auth);
}


/**
 * Authenticates the username/password supplied against
 * the system configured auth type.
 *
 * 
 * @author  Matt Pascoe <matt@opennetadmin.com>
 * @return  int     1 or 0 indicating success or failure of auth
 * @return  string  A Javascript code containint status messages
 */
function get_authentication($login_name='', $login_password='') {
    global $base, $conf, $self, $onadb, $auth;

    $js = "el('loginmsg').innerHTML = '<span style=\"color: green;\">Success!</span>'; setTimeout('removeElement(\'tt_loginform\')',1000);";

    // Validate the userid was passed and is "clean"
    if (!preg_match('/^[A-Za-z0-9.\-_@]+$/', $login_name)) {
        $js = "el('loginmsg').innerHTML = 'Bad username format';";
        printmsg("ERROR => Login failure for {$login_name}: Bad username format", 0);
        return(array(1, $js));
    }


    // Force guest logins to only use local auth module
    if ($login_name == 'guest') {
        printmsg("DEBUG => Guest user login, forcing local auth.",1);
        // create new authentication class
        $auth = load_auth_class('local');
        $conf['authtype']='local';
    } else {
        // create new authentication class
        $auth = load_auth_class();
    }

    // Check user/pass authentication
    $authresult = $auth->checkPass($login_name,$login_password);

    // If we do not find a valid user, fall back to local auth
    if ($auth->founduser === false) {
        // Fall back to local database to see if we have something there
        if ($conf['authtype'] != 'local') {
            printmsg("DEBUG => Unable to find user via auth_{$conf['authtype']}, falling back to local auth_local.",1);
            $auth = load_auth_class('local');
            $authresult = $auth->checkPass($login_name,$login_password);
            if ($auth->founduser === false) {
                $js = "el('loginmsg').innerHTML = 'Unknown user';";
                printmsg("ERROR => Login failure for {$login_name}: Unknown user", 0);
                return(array(1, $js));
            }
            // override the system configured authtype for now
            $conf['authtype']='local';
        }
    }

    // If we do not get a positive authentication of user/pass then fail
    if ($authresult === false) {
        $js = "el('loginmsg').innerHTML = 'Password incorrect';";
        printmsg("ERROR => Login failure for {$login_name} using authtype {$conf['authtype']}: Password incorrect", 0);
        return(array(1, $js));
    }

    // look for group information:
    if ($conf['authtype'] == 'ldap') { // ... other constraints
        $userinfo = $auth->getUserData($login_name);
        if (empty($userinfo['grps'])) {
            $js = "el('loginmsg').innerHTML = 'Permission denied';";
            printmsg("ERROR => Login failure for {$login_name} using authtype {$conf['authtype']}: No group assigned", 0);
            return(array(1, $js));
        }
    }

    // If the password is good.. return success.
    printmsg("INFO => Authentication Successful for {$login_name} using authtype: {$conf['authtype']}", 1);
    return(array(0, $js));
}



/**
 * Authorizes a user for specific permissions
 *
 * Populates session variable with permissions. no
 * data is returned to the calling function
 *
 * @author  Matt Pascoe <matt@opennetadmin.com>
 * @return  TRUE
 */
function get_perms($login_name='') {
    global $conf, $self, $onadb, $auth;

    // We'll be populating these arrays
    $user = array();
    $groups = array();
    $permissions = array();

    printmsg("INFO => Authorization Starting for {$login_name}", 1);

    // get user information and groups from the previously populated auth class
    $userinfo = $auth->getUserData($login_name);
    if ($userinfo === false) printmsg("INFO => Failed to get user information for user: {$login_name}", 0);

    // If this is the local auth type, check local user permissions
    // MP: This code should not be here but there is really not a better spot.
    //if ($conf['authtype'] == 'local') {
        // Load the users permissions based on their user_id.
        // this is specific permissions for user, outside of group permissions
        list($status, $rows, $records) = db_get_records($onadb, 'permission_assignments', array('user_id' => $userinfo['id']));
        foreach ($records as $record) {
            list($status, $rows, $perm) = db_get_record($onadb, 'permissions', array('id' => $record['perm_id']));
            $permissions[$perm['name']] = $perm['id'];
        }
    //}


    // Load the users permissions based on their group ids
    foreach ((array)$userinfo['grps'] as $group => $grpid) {
        // Look up the group id stored in local tables using the name
        list($status, $rows, $grp) = db_get_record($onadb, 'auth_groups', array('name' => $group));
        // get permission assignments per group id
        list($status, $rows, $records) = db_get_records($onadb, 'permission_assignments', array('group_id' => $grp['id']));
        foreach ($records as $record) {
            list($status, $rows, $perm) = db_get_record($onadb, 'permissions', array('id' => $record['perm_id']));
            $permissions[$perm['name']] = $perm['id'];
        }
    }

    // Save stuff in the session
    unset($_SESSION['ona']['auth']);
    $_SESSION['ona']['auth']['user']   = $userinfo;
    $_SESSION['ona']['auth']['perms']  = $permissions;

    // Log that the user logged in
    printmsg("INFO => Loaded permissions for " . $login_name, 2);
    return true;

}



/**
 * Encrypts a password using the given method and salt
 *
 * If the selected method needs a salt and none was given, a random one
 * is chosen.
 *
 * The following methods are understood:
 *
 *   smd5  - Salted MD5 hashing
 *   apr1  - Apache salted MD5 hashing
 *   md5   - Simple MD5 hashing
 *   sha1  - SHA1 hashing
 *   ssha  - Salted SHA1 hashing
 *   crypt - Unix crypt
 *   mysql - MySQL password (old method)
 *   my411 - MySQL 4.1.1 password
 *   kmd5  - Salted MD5 hashing as used by UNB
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @return  string  The crypted password
 */
function auth_cryptPassword($clear,$method='',$salt=null){
    global $conf;
    if(empty($method)) $method = $conf['passcrypt'];

    //prepare a salt
    if(is_null($salt)) $salt = md5(uniqid(rand(), true));

    switch(strtolower($method)){
        case 'smd5':
            if(defined('CRYPT_MD5') && CRYPT_MD5) return crypt($clear,'$1$'.substr($salt,0,8).'$');
            // when crypt can't handle SMD5, falls through to pure PHP implementation
            $magic = '1';
        case 'apr1':
            //from http://de.php.net/manual/en/function.crypt.php#73619 comment by <mikey_nich at hotmail dot com>
            if(!$magic) $magic = 'apr1';
            $salt = substr($salt,0,8);
            $len = strlen($clear);
            $text = $clear.'$'.$magic.'$'.$salt;
            $bin = pack("H32", md5($clear.$salt.$clear));
            for($i = $len; $i > 0; $i -= 16) {
                $text .= substr($bin, 0, min(16, $i));
            }
            for($i = $len; $i > 0; $i >>= 1) {
                $text .= ($i & 1) ? chr(0) : $clear[0];
            }
            $bin = pack("H32", md5($text));
            for($i = 0; $i < 1000; $i++) {
                $new = ($i & 1) ? $clear : $bin;
                if ($i % 3) $new .= $salt;
                if ($i % 7) $new .= $clear;
                $new .= ($i & 1) ? $bin : $clear;
                $bin = pack("H32", md5($new));
            }
            $tmp = '';
            for ($i = 0; $i < 5; $i++) {
                $k = $i + 6;
                $j = $i + 12;
                if ($j == 16) $j = 5;
                $tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
            }
            $tmp = chr(0).chr(0).$bin[11].$tmp;
            $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
                    "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
            return '$'.$magic.'$'.$salt.'$'.$tmp;
        case 'md5':
            return md5($clear);
        case 'none':
            return $clear;
        case 'sha1':
            return sha1($clear);
        case 'ssha':
            $salt=substr($salt,0,4);
            return '{SSHA}'.base64_encode(pack("H*", sha1($clear.$salt)).$salt);
        case 'crypt':
            return crypt($clear,substr($salt,0,2));
        case 'mysql':
            //from http://www.php.net/mysql comment by <soren at byu dot edu>
            $nr=0x50305735;
            $nr2=0x12345671;
            $add=7;
            $charArr = preg_split("//", $clear);
            foreach ($charArr as $char) {
                if (($char == '') || ($char == ' ') || ($char == '\t')) continue;
                $charVal = ord($char);
                $nr ^= ((($nr & 63) + $add) * $charVal) + ($nr << 8);
                $nr2 += ($nr2 << 8) ^ $nr;
                $add += $charVal;
            }
            return sprintf("%08x%08x", ($nr & 0x7fffffff), ($nr2 & 0x7fffffff));
        case 'my411':
            return '*'.sha1(pack("H*", sha1($clear)));
        case 'kmd5':
            $key = substr($salt, 16, 2);
            $hash1 = strtolower(md5($key . md5($clear)));
            $hash2 = substr($hash1, 0, 16) . $key . substr($hash1, 16);
            return $hash2;
        default:
            printmsg("Unsupported crypt method $method",0);
    }
}



/**
 * Verifies a cleartext password against a crypted hash
 *
 * The method and salt used for the crypted hash is determined automatically
 * then the clear text password is crypted using the same method. If both hashs
 * match true is is returned else false
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @return  bool
 */
function auth_verifyPassword($clear,$crypt){
    $method='';
    $salt='';

    //determine the used method and salt
    $len = strlen($crypt);
    if(preg_match('/^\$1\$([^\$]{0,8})\$/',$crypt,$m)){
        $method = 'smd5';
        $salt   = $m[1];
    }elseif(preg_match('/^\$apr1\$([^\$]{0,8})\$/',$crypt,$m)){
        $method = 'apr1';
        $salt   = $m[1];
    }elseif(substr($crypt,0,6) == '{SSHA}'){
        $method = 'ssha';
        $salt   = substr(base64_decode(substr($crypt, 6)),20);
    }elseif($len == 32){
        $method = 'md5';
    }elseif($len == 40){
        $method = 'sha1';
    }elseif($len == 16){
        $method = 'mysql';
    }elseif($len == 41 && $crypt[0] == '*'){
        $method = 'my411';
    }elseif($len == 34){
        $method = 'kmd5';
        $salt   = $crypt;
    }else{
        $method = 'crypt';
        $salt   = substr($crypt,0,2);
    }

    //crypt and compare
    if(auth_cryptPassword($clear,$method,$salt) === $crypt){
        return true;
    }
    return false;
}




?>
