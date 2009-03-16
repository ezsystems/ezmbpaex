<?php
/**
 * File containing the userpaex module definition
 *
 * @copyright Copyright (C) 1999-2008 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package mbpaex
 */

$Module = array( 'name' => 'User with Password Expiry management',
                 'variable_params' => true );

$ViewList = array();

$ViewList['password'] = array(
    'functions' => array( 'password' ),
    'script' => 'password.php',
    'ui_context' => 'administration',
    'default_navigation_part' => 'ezmynavigationpart',
    'params' => array( 'UserID' ) );

$ViewList['forgotpassword'] = array(
    'functions' => array( 'password' ),
    'script' => 'forgotpassword.php',
    'params' => array( ),
    'ui_context' => 'administration',
    'single_post_actions' => array( 'GenerateButton' => 'Generate',
                                    'ChangePasswdButton' => 'ChangePassword' ),
    'post_action_parameters' => array( 'Generate' => array( 'Login' => 'UserLogin',
                                                            'Email' => 'UserEmail' ),
                                       'ChangePassword' => array('NewPassword' => 'NewPassword',
                                                                 'NewPasswordConfirm' => 'NewPasswordConfirm' ) ),
    'params' => array( 'HashKey' ) );

$FunctionList = array();
$FunctionList['password'] = array();
$FunctionList['editpaex'] = array();

?>
