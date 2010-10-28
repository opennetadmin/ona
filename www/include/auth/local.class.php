<?php
/**
 * auth/local.class.php
 *
 * foundation authorisation class
 * all auth classes should inherit from this class
 *
 * @author    Chris Smith <chris@jalakai.co.uk>
 */

class auth_local {

  var $success = true;
  var $founduser = false;
  var $user = null;

  /**
   * Constructor.
   *
   * Carry out sanity checks to ensure the object is
   * able to operate.
   *
   * @author  Christopher Smith <chris@jalakai.co.uk>
   */
  function auth_local() {
     // the base class constructor does nothing, derived class
    // constructors do the real work
  }



  /**
   * Check user+password [ MUST BE OVERRIDDEN ]
   *
   * Checks if the given user exists and the given
   * password is correct
   *
   * Set $this->founduser to true if we find a valid user
   *     found user should be set if we find the user even if the password does not match
   *
   * Return value will be true or false depnding on match of user/pass
   *
   * @author  Matt Pascoe <matt@opennetadmin.com>
   * @return  bool      True/False indicating successful auth match of user/pass
   */
  function checkPass($user,$pass){
        global $onadb;

        list($status, $rows, $user) = db_get_record($onadb, 'users', "username LIKE '{$user}'");

        if (!$rows) {
            $this->founduser = false;
            return false;
        } else {
            $this->founduser = true;
            $md5pass = md5($pass);
            // check that the password is the same.
            if($md5pass === $user['password']) {
                return true;
            } else {
                return false;
            }
        }
  }



  /**
   * Return user info [ MUST BE OVERRIDDEN ] or false
   *
   * Returns info about the given user needs to contain
   * at least these fields:
   *
   * username   string      name of the user
   * grps       array       list of groups the user is in
   *                        $user['grps']['groupname']=groupidnum
   *
   * sets a variable ($this->founduser) to show if a user was
   * found by this function
   *
   * @author  Matt Pascoe <matt@opennetadmin.com>
   * @return  array containing user data or false
   */
  function getUserData($login_name) {
        global $onadb;

        list($status, $rows, $user) = db_get_record($onadb, 'users', "username LIKE '{$login_name}'");

        if (!$rows) {
            $this->founduser = false;
            return false;
        } else {
            $this->founduser = true;

            // Update the access time for the user
            db_update_record($onadb, 'users', array('id' => $user['id']), array('atime' => date_mangle(time())));

            // Load the user's groups
            list($status, $rows, $records) = db_get_records($onadb, 'group_assignments', array('user_id' => $user['id']));
            foreach ($records as $record) {
                list($status, $rows, $group) = db_get_record($onadb, 'groups', array('id' => $record['group_id']));
                $user['grps'][$group['name']] = $group['id'];
                if ($group['level'] > $user['level']) { $user['level'] = $group['level']; }
            }

            return $user;
        }
  }


}
//Setup VIM: ex: et ts=2 enc=utf-8 :