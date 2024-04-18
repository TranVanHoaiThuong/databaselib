<?php

use exceptions\CustomException;

define('USER_STATUS_INACTIVE', 0);
define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_DELETED', 1);

/**
 * Get object user
 * @param array $params Condition to get user
 * @return object|false
 */
function get_user(array $params, bool $getdelete = false): object|false {
    global $DB;
    if($getdelete) {
        $params['deleted'] = USER_STATUS_DELETED;
    }
    return $DB->get_row_data('users', $params);
}

/**
 * Create a new user
 * @param stdClass $user Object user to create
 * @return int New userid
 */
function create_user(stdClass $user): int {
    global $DB;
    $user->timecreated = time();
    $user->timemodified = time();
    
    // Check username
    if($DB->get_row_data('users', ['username' => $user->username])) {
        throw new CustomException('The username already exists');
    }
    // Check email
    if(isset($user->email) && !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        throw new CustomException("$user->email is a valid email address");
    }
    if($DB->get_row_data('users', ['email' => $user->email])) {
        throw new CustomException('The email of user is already exists');
    }
    // Check password
    if(!isset($user->password) || (isset($user->password) && !$user->password)) {
        throw new CustomException('Password can not be empty');
    }
    $user->password = password_hash($user->password, null);
    // Check name
    if(!isset($user->firstname) || (isset($user->firstname) && !$user->firstname)) {
        throw new CustomException('First name of user can not be empty');
    }
    if(!isset($user->lastname) || (isset($user->lastname) && !$user->lastname)) {
        throw new CustomException('Last name of user can not be empty');
    }
    if(!isset($user->fullname) || (isset($user->fullname) && !$user->fullname)) {
        throw new CustomException('Full name of user can not be empty');
    }
    return $DB->insert_row('users', $user);
}

/**
 * Update user
 * @param stdClass $user Object user to update
 */
function update_user(stdClass $user) {
    global $DB;
    if(!isset($user->id) || (isset($user->id) && !$user->id)) {
        throw new CustomException('Id of user is required in update');
    }
    $beforeupdate = get_user(['id' => $user->id]);
    // check update email
    if(isset($user->email) && $beforeupdate->email !== $user->email) {
        if(!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new CustomException("$user->email is a valid email address");
        }
        if(get_user(['email' => $user->email])) {
            throw new CustomException('The email of user is already exists');
        }
    }
    // New password
    if(isset($user->password) && !empty($user->password) && !password_verify($user->password, $beforeupdate->password)) {
        $user->password = password_hash($user->password, null);
    } else {
        unset($user->password);
    }
    // Check name
    if(!isset($user->firstname) || (isset($user->firstname) && !$user->firstname)) {
        unset($user->firstname);
    }
    if(!isset($user->lastname) || (isset($user->lastname) && !$user->lastname)) {
        unset($user->lastname);
    }
    if(!isset($user->fullname) || (isset($user->fullname) && !$user->fullname)) {
        unset($user->fullname);
    }
    $user->timemodified = time();
    $DB->update_row('users', $user);
}

/**
 * Delete a user
 * @param int $userid Id of user need to delete
 * @param bool $deletepermanently If it true, the user will be deleted and can not restored
 * @return bool
 */
function delete_user(int $userid, bool $deletepermanently = false): bool {
    global $DB;
    if(!$userid) {
        return false;
    }
    if($deletepermanently) {
        $DB->delete_rows('users', ['id' => $userid]);
        return true;
    }
    $user = get_user(['id' => $userid]);
    if(!$user) {
        return false;
    }
    unset($user->password);
    unset($user->email);
    $user->deleted = USER_STATUS_DELETED;
    $user->username = $user->username . '.deleted';
    update_user($user);
    return true;
}

/**
 * Delete a user
 * @param int $userid Id of user need to restore
 * @return bool
 */
function restore_user($userid): bool {
    $user = get_user(['id' => $userid], true);
    if(!$user) {
        return false;
    }
    $user->deleted = 0;
    $user->username = explode('.', $user->username)[0];
    unset($user->password);
    unset($user->email);
    update_user($user);
    return true;
}