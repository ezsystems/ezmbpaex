<?php
/**
 * File containing the password view
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

$ini = eZINI::instance();
$currentUser = eZUser::currentUser();
$currentUserID = $currentUser->attribute( "contentobject_id" );
$http = eZHTTPTool::instance();
$Module = $Params["Module"];
$message = 0;
$oldPasswordNotValid = 0;
$newPasswordNotMatch = 0;
$newPasswordTooShort = 0;
$newPasswordNotValidate = 0;
$newPasswordMustDiffer = 0;
$userRedirectURI = '';

$userRedirectURI = $Module->actionParameter( 'UserRedirectURI' );

if ( $http->hasSessionVariable( "LastAccessesURI" ) )
     $userRedirectURI = $http->sessionVariable( "LastAccessesURI" );

$redirectionURI = $userRedirectURI;
if ( $redirectionURI == '' )
     $redirectionURI = $ini->variable( 'SiteSettings', 'DefaultPage' );

if( !isset( $oldPassword ) )
    $oldPassword = '';

if( !isset( $newPassword ) )
    $newPassword = '';

if( !isset( $confirmPassword ) )
    $confirmPassword = '';

if ( is_numeric( $Params["UserID"] ) )
    $UserID = $Params["UserID"];
else
    $UserID = false;

$user = eZUser::fetch( $UserID );

if ( $http->hasPostVariable( "OKButton" ) && $user)
{
    if ( $http->hasPostVariable( "oldPassword" ) )
    {
        $oldPassword = $http->postVariable( "oldPassword" );
    }
    if ( $http->hasPostVariable( "newPassword" ) )
    {
        $newPassword = $http->postVariable( "newPassword" );
    }
    if ( $http->hasPostVariable( "confirmPassword" ) )
    {
        $confirmPassword = $http->postVariable( "confirmPassword" );
    }

    $login = $user->attribute( "login" );
    $type = $user->attribute( "password_hash_type" );
    $hash = $user->attribute( "password_hash" );
    $site = $user->site();

    if ( $user->authenticateHash( $login, $oldPassword, $site, $type, $hash ) ) // Old password is correct
    {
        if (  $newPassword == $confirmPassword )
        {
            if ( !$user->validatePassword($newPassword) )
            {
                // if audit is enabled password changes should be logged
                eZAudit::writeAudit( 'user-password-change-self-fail', array( 'UserID: ' => $UserID, 'Login: ' => $login,
                                                                              'Comment: ' => 'Password not pass standard validation' ) );

                $newPasswordNotValidate = 1;
            }
            else
            {
                // Patch for use mbpaex::validatePassword
                $paex = eZPaEx::getPaEx( $UserID );
                if (!$paex->validatePassword($newPassword))
                {
                    // if audit is enabled password changes should be logged
                    eZAudit::writeAudit( 'user-password-change-self-fail', array( 'UserID: ' => $UserID, 'Login: ' => $login,
                                                                                  'Comment: ' => 'Password not pass PAEX validation' ) );
                    $newPasswordNotValidate = 1;
                }
                else
                {
                    $oldHash = $user->createHash( $login, $oldPassword, $site, $type );
                    $newHash = $user->createHash( $login, $newPassword, $site, $type );
                    if ($oldHash == $newHash)
                    {
                        // if audit is enabled password changes should be logged
                        eZAudit::writeAudit( 'user-password-change-self-fail', array( 'UserID: ' => $UserID, 'Login: ' => $login,
                                                                                      'Comment: ' => 'New and old password are the same' ) );
                        $newPasswordMustDiffer = 1;
                    }
                    else
                    {
                        // if audit is enabled password changes should be logged
                        eZAudit::writeAudit( 'user-password-change-self', array( 'UserID: ' => $UserID, 'Login: ' => $login ) );

                        $user->setAttribute( "password_hash", $newHash );
                        $user->store();
                        $paex->resetPasswordLastUpdated();
                        $oldPassword = '';

                        eZUser::setCurrentlyLoggedInUser( $user, $UserID );

                        if ( $http->hasPostVariable( "RedirectOnChange" ) )
                        {
                            return $Module->redirectTo( $http->postVariable( "RedirectOnChange" ) );
                        }
                        eZRedirectManager::redirectTo( $Module, $redirectionURI );
                        return;
                    }
                }
            }
            $newPassword = '';
            $confirmPassword = '';
            $message = true;
        }
        else
        {
            // if audit is enabled password changes should be logged
            eZAudit::writeAudit( 'user-password-change-self-fail', array( 'UserID: ' => $UserID, 'Login: ' => $login,
                                                                          'Comment: ' => 'Password not match password confirmation' ) );
            $newPassword = "";
            $confirmPassword = "";
            $newPasswordNotMatch = 1;
            $message = true;
        }
    }
    else
    {
        // if audit is enabled password changes should be logged
        eZAudit::writeAudit( 'user-password-change-self-fail', array( 'UserID: ' => $UserID, 'Login: ' => $login,
                                                                      'Comment: ' => 'Old password incorrect' ) );
        $oldPassword = "";
        $oldPasswordNotValid = 1;
        $message = true;
    }
}

if ( $http->hasPostVariable( "CancelButton" ) || !$user )
{
    if ( $http->hasPostVariable( "RedirectOnCancel" ) )
    {
        return $Module->redirectTo( $http->postVariable( "RedirectOnCancel" ) );
    }
    eZRedirectManager::redirectTo( $Module, $redirectionURI );
    return;
}

$Module->setTitle( "Edit user information" );
// Template handling
$tpl = eZTemplate::factory();
$tpl->setVariable( "module", $Module );
$tpl->setVariable( "http", $http );
$tpl->setVariable( "userID", $UserID );
$tpl->setVariable( "userAccount", $user );
$tpl->setVariable( "oldPassword", $oldPassword );
$tpl->setVariable( "newPassword", $newPassword );
$tpl->setVariable( "confirmPassword", $confirmPassword );
$tpl->setVariable( "oldPasswordNotValid", $oldPasswordNotValid );
$tpl->setVariable( "newPasswordNotMatch", $newPasswordNotMatch );
$tpl->setVariable( "newPasswordTooShort", $newPasswordTooShort );
$tpl->setVariable( "newPasswordNotValidate", $newPasswordNotValidate );
$tpl->setVariable( "newPasswordMustDiffer", $newPasswordMustDiffer );
$tpl->setVariable( "message", $message );

$Result = array();
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'kernel/user', 'User' ),
                                'url' => false ),
                         array( 'text' => ezpI18n::tr( 'kernel/user', 'Change password' ),
                                'url' => false ) );
$Result['content'] = $tpl->fetch( "design:userpaex/password.tpl" );

$currentuser = eZUser::currentUser();
if ( !$currentuser->isLoggedIn() )
{
   if ( $ini->variable( 'SiteSettings', 'LoginPage' ) == 'custom' )
   {
       $Result['pagelayout'] = 'loginpagelayout.tpl';
   }
}
?>
