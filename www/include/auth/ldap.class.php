<?php
/**
 * LDAP authentication backend
 *
 * @license   GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Andreas Gohr <andi@splitbrain.org>
 * @author    Chris Smith <chris@jalakaic.co.uk>
 */

class auth_ldap extends auth_local {
    var $cnf = null;
    var $con = null;
    var $bound = 0; // 0: anonymous, 1: user, 2: superuser
    var $founduser = false;

    /**
     * Constructor: auth_ldap
     *
     * Reads configuration and checks for PHP LDAP module.
     */
    function __construct(){
        global $conf,$base;

        // load in system default ldap config
        $ldap_conf="{$base}/config/auth_ldap.config.php";
        if (file_exists($ldap_conf)) { require_once($ldap_conf); }

        // Load any user specific entries and add them to the list
        // If you re-define an existing entry, it will override the default
        $ldap_conf="{$base}/local/config/auth_ldap.config.php";
        if (file_exists($ldap_conf)) { require_once($ldap_conf); }


        $this->cnf = $conf['auth']['ldap'];

        // ldap extension is needed
        if(!function_exists('ldap_connect')) {
            if ($this->cnf['debug'])
                printmsg("ERROR => auth_ldap err: PHP LDAP extension not found.",0);
            $this->success = false;
            return;
        }

        if(empty($this->cnf['groupkey'])) $this->cnf['groupkey'] = 'cn';

        // auth_ldap currently just handles authentication
    }

    /**
     * Check user+password
     *
     * Checks if the given user exists and the given
     * plaintext password is correct by trying to bind
     * to the LDAP server
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     * @return  bool
     */
    function checkPass($user,$pass){
	global $base;
        $ldap_conf="{$base}/local/config/auth_ldap.config.php";

        //Opening of the LDAP conf file
        $confFile=fopen($ldap_conf,"r");

        // This list can be modified depending on the LDAP config file
        $var=['debug','version','server','usertree','grouptree','groupfilter'];

        for ($i=0;$i<count($var);$i++){
                $reading=fgets($confFile);
                while($reading[0]!="$"){
                        $reading=fgets($confFile);
                };
                $string=explode(" = '",$reading);
                $this->cnf[$var[$i]]=explode("';",$string[1])[0];
        };

        // This 2 variables below can be modified depending on the LDAP configuration you have
        $this->cnf['port']=389;
        $this->cnf['groupkey']='cn';

        fclose($confFile);    
	    
	// reject empty password
        if(empty($pass)) return false;
        if(!$this->_openLDAP()) return false;

        // indirect user bind
        if($this->cnf['binddn'] && $this->cnf['bindpw']){
            // use superuser credentials
            if(!@ldap_bind($this->con,$this->cnf['binddn'],$this->cnf['bindpw'])){
                if($this->cnf['debug'])
                    printmsg('DEBUG => auth_ldap: LDAP bind as superuser: '.htmlspecialchars(ldap_error($this->con)),1);
                return false;
            }
            $this->bound = 2;
        }else if($this->cnf['binddn'] &&
                 $this->cnf['usertree'] &&
                 $this->cnf['userfilter']) {
            // special bind string
            $dn = $this->_makeFilter($this->cnf['binddn'],
                                     array('user'=>$user,'server'=>$this->cnf['server']));

        }else if(strpos($this->cnf['usertree'], '%{user}')) {
            // direct user bind
            $dn = $this->_makeFilter($this->cnf['usertree'],
                                     array('user'=>$user,'server'=>$this->cnf['server']));

        }else{
            // Anonymous bind
            if(!@ldap_bind($this->con)){
                printmsg("ERROR => auth_ldap: can not bind anonymously",0);
                if($this->cnf['debug'])
                    printmsg('DEBUG => auth_ldap: LDAP anonymous bind: '.htmlspecialchars(ldap_error($this->con)),1);
                return false;
            }
        }

        // Try to bind to with the dn if we have one.
        if(!empty($dn)) {
            printmsg("DEBUG => auth_ldap: binding with DN: $dn", 5);
            // User/Password bind
            if(!@ldap_bind($this->con,$dn,$pass)){
                if($this->cnf['debug']){
                    printmsg("ERROR => auth_ldap: bind with $dn failed", 1);
                    printmsg('DEBUG => auth_ldap: LDAP user dn bind: '.htmlspecialchars(ldap_error($this->con)),1);
                }
                return false;
            }
            $this->bound = 1;
            $this->founduser = true;
            return true;
        }else{
            // See if we can find the user
            $info = $this->getUserData($user,true);
            if(empty($info['dn'])) {
                return false;
            } else {
                $dn = $info['dn'];
            }

            // Try to bind with the dn provided
            if(!@ldap_bind($this->con,$dn,$pass)){
                if($this->cnf['debug']){
                    printmsg("ERROR => auth_ldap: bind with $dn failed", 1);
                    printmsg('DEBUG => auth_ldap: LDAP user bind: '.htmlspecialchars(ldap_error($this->con)),1);
                }
                return false;
            }
            $this->bound = 1;
            $this->founduser = true;
            return true;
        }

        return false;
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * username string  name of the user
     * grps array       list of groups the user is in
     *
     * This LDAP specific function returns the following
     * addional fields:
     *
     * dn     string  distinguished name (DN)
     * uid    string  Posix User ID
     * inbind bool    for internal use - avoid loop in binding
     * mail string    email addres of the user
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     * @author  Trouble
     * @author  Dan Allen <dan.j.allen@gmail.com>
     * @author  <evaldas.auryla@pheur.org>
     * @author  Stephane Chazelas <stephane.chazelas@emerson.com>
     * @return  array containing user data or false
     */
    function getUserData($user,$inbind=false) {
        global $conf;
        if(!$this->_openLDAP()) return false;

        // force superuser bind if wanted and not bound as superuser yet
        if($this->cnf['binddn'] && $this->cnf['bindpw'] && $this->bound < 2){
            // use superuser credentials
            if(!@ldap_bind($this->con,$this->cnf['binddn'],$this->cnf['bindpw'])){
                if($this->cnf['debug'])
                    printmsg('DEBUG => auth_ldap: LDAP bind as superuser: '.htmlspecialchars(ldap_error($this->con)),1);
                return false;
            }
            $this->bound = 2;
        }elseif($this->bound == 0 && !$inbind) {
            // in some cases getUserData is called outside the authentication workflow
            // eg. for sending email notification on subscribed pages. This data might not
            // be accessible anonymously, so we try to rebind the current user here
            //////////////////$pass = PMA_blowfish_decrypt($_SESSION[DOKU_COOKIE]['auth']['pass'],auth_cookiesalt());
            //$this->checkPass($_SESSION[DOKU_COOKIE]['auth']['user'], $pass);
        }

        $info['username']   = $user;
        $info['user']   = $user;
        $info['server'] = $this->cnf['server'];

        //get info for given user
        $base = $this->_makeFilter($this->cnf['usertree'], $info);
        if(!empty($this->cnf['userfilter'])) {
            $filter = $this->_makeFilter($this->cnf['userfilter'], $info);
        } else {
            $filter = "(ObjectClass=*)";
        }

        $sr     = @ldap_search($this->con, $base, $filter);
        $result = @ldap_get_entries($this->con, $sr);
        if($this->cnf['debug']){
            printmsg('DEBUG => auth_ldap: LDAP user search: '.htmlspecialchars(ldap_error($this->con)),1);
            printmsg('DEBUG => auth_ldap: LDAP search at: '.htmlspecialchars($base.' '.$filter),1);
        }

        // Don't accept more or less than one response
        if(!is_array($result) || $result['count'] != 1){
            return false; //user not found
        }

        $user_result = $result[0];
        ldap_free_result($sr);
        $g = 0;

        // general user info
        $info['dn']   = $user_result['dn'];
        $info['gid']  = $user_result['gidnumber'][0];
        $info['mail'] = $user_result['mail'][0];
        $info['name'] = $user_result['cn'][0];
        $info['grps'] = array();

        // overwrite if other attribs are specified.
        if(is_array($this->cnf['mapping'])){
            foreach($this->cnf['mapping'] as $localkey => $key) {
                if(is_array($key)) {
                    // use regexp to clean up user_result
                    list($key, $regexp) = $key;
                    if($user_result[$key]) foreach($user_result[$key] as $grp){
                        if (preg_match($regexp,$grp,$match)) {
                            if($localkey == 'grps') {
                                $info[$localkey][$match[1]] = $g++;
                            } else {
                                $info[$localkey] = $match[1];
                            }
                        }
                    }
                } else {
                    $info[$localkey] = $user_result[$key][0];
                }
            }
        }
        $user_result = array_merge($info,$user_result);

        //get groups for given user if grouptree is given
        if ($this->cnf['grouptree'] && $this->cnf['groupfilter']) {
            $g = 0;
            $base   = $this->_makeFilter($this->cnf['grouptree'], $user_result);
            $filter = $this->_makeFilter($this->cnf['groupfilter'], $user_result);
            $sr = @ldap_search($this->con, $base, $filter, array($this->cnf['groupkey']));
            if(!$sr){
                printmsg("ERROR => auth_ldap: Reading group memberships failed",0);
                if($this->cnf['debug']){
                    printmsg('DEBUG => auth_ldap: LDAP group search: '.htmlspecialchars(ldap_error($this->con)),1);
                    printmsg('DEBUG => auth_ldap: LDAP search at: '.htmlspecialchars($base.' '.$filter),1);
                }
                return false;
            }
            $result = ldap_get_entries($this->con, $sr);
            ldap_free_result($sr);

            if(is_array($result)) foreach($result as $grp){
                if(!empty($grp[$this->cnf['groupkey']][0])){
                    $groupname = $grp[$this->cnf['groupkey']][0];
                    if($this->cnf['debug'])
                        printmsg('DEBUG => auth_ldap: LDAP usergroup: '
                        .htmlspecialchars($groupname),2);
                    if(!empty($this->cnf['mapping']['grps'][$this->cnf['groupkey']])){
                        $regexp = $this->cnf['mapping']['grps'][$this->cnf['groupkey']];
                        printmsg('DEBUG => Matching '.htmlspecialchars($groupname)
                                                     .' against '.htmlspecialchars($regexp),2);
                        if (preg_match($regexp,$groupname,$match)) {
                            $groupname_mapped = $match[1];
                            if($this->cnf['debug'])
                                printmsg('DEBUG => auth_ldap: mapped LDAP usergroup: '
                                .htmlspecialchars($groupname_mapped),2);
                            $info['grps'][$groupname_mapped] = $g++;
                        }
                    } else {
                        $info['grps'][$groupname] = $g++;
                    }
                }
            }
        }

        return $info;
    }

    /**
     * Most values in LDAP are case-insensitive
     */
    function isCaseSensitive(){
        return false;
    }

    /**
     * Make LDAP filter strings.
     *
     * Used by auth_getUserData to make the filter
     * strings for grouptree and groupfilter
     *
     * filter      string  ldap search filter with placeholders
     * placeholders array   array with the placeholders
     *
     * @author  Troels Liebe Bentsen <tlb@rapanden.dk>
     * @return  string
     */
    function _makeFilter($filter, $placeholders) {
        preg_match_all("/%{([^}]+)/", $filter, $matches, PREG_PATTERN_ORDER);
        //replace each match
        foreach ($matches[1] as $match) {
            //take first element if array
            if(is_array($placeholders[$match])) {
                $value = $placeholders[$match][0];
            } else {
                $value = $placeholders[$match];
            }
            $value = $this->_filterEscape($value);
            $filter = str_replace('%{'.$match.'}', $value, $filter);
        }
        return $filter;
    }

    /**
     * Escape a string to be used in a LDAP filter
     *
     * Ported from Perl's Net::LDAP::Util escape_filter_value
     *
     * @author Andreas Gohr
     */
    function _filterEscape($string){
        // see https://github.com/adldap/adLDAP/issues/22
        return preg_replace_callback(
            '/([\x00-\x1F\*\(\)\\\\])/',
            function ($matches) {
                return "\\".join("", unpack("H2", $matches[1]));
            },
            $string
        );
    }

    /**
     * Opens a connection to the configured LDAP server and sets the wanted
     * option on the connection
     *
     * @author  Andreas Gohr <andi@splitbrain.org>
     */
    function _openLDAP(){
        if($this->con) return true; // connection already established

        $this->bound = 0;

        $port = ($this->cnf['port']) ? $this->cnf['port'] : 389;
        $this->con = @ldap_connect($this->cnf['server'],$port);
        if(!$this->con){
            printmsg("ERROR => auth_ldap: couldn't connect to LDAP server",0);
            return false;
        }

        //set protocol version and dependend options
        if($this->cnf['version']){
            if(!@ldap_set_option($this->con, LDAP_OPT_PROTOCOL_VERSION,
                                 $this->cnf['version'])){
                printmsg('ERROR => auth_ldap: Setting LDAP Protocol version '.$this->cnf['version'].' failed',0);
                if($this->cnf['debug'])
                    printmsg('DEBUG => auth_ldap: LDAP version set: '.htmlspecialchars(ldap_error($this->con)),1);
            }else{
                //use TLS (needs version 3)
                if($this->cnf['starttls']) {
                    if (!@ldap_start_tls($this->con)){
                        printmsg('ERROR => auth_ldap: Starting TLS failed',0);
                        if($this->cnf['debug'])
                            printmsg('DEBUG => auth_ldap: LDAP TLS set: '.htmlspecialchars(ldap_error($this->con)),1);
                    }
                }
                // needs version 3
                if(isset($this->cnf['referrals'])) {
                    if(!@ldap_set_option($this->con, LDAP_OPT_REFERRALS,
                       $this->cnf['referrals'])){
                        printmsg('ERROR => auth_ldap: Setting LDAP referrals to off failed',0);
                        if($this->cnf['debug'])
                            printmsg('DEBUG => auth_ldap: LDAP referal set: '.htmlspecialchars(ldap_error($this->con)),1);
                    }
                }
            }
        }

        //set deref mode
        if($this->cnf['deref']){
            if(!@ldap_set_option($this->con, LDAP_OPT_DEREF, $this->cnf['deref'])){
                printmsg('ERROR => auth_ldap: Setting LDAP Deref mode '.$this->cnf['deref'].' failed',0);
                if($this->cnf['debug'])
                    printmsg('DEBUG => auth_ldap: LDAP deref set: '.htmlspecialchars(ldap_error($this->con)),1);
            }
        }

        return true;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
