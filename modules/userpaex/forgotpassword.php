<?php
/**
 * File containing the forgotpassword view
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

$tpl = eZTemplate::factory();
$tpl->setVariable( 'generated', false );
$tpl->setVariable( 'wrong_email', false );
$tpl->setVariable( 'link', false );
$tpl->setVariable( 'wrong_key', false );
$tpl->setVariable( 'password_form', false );
$tpl->setVariable( 'newPasswordNotValidate', false );
$tpl->setVariable( 'newPasswordMustDiffer', false );
$tpl->setVariable( 'password_changed', false );
$tpl->setVariable( 'newPasswordNotMatch', false );

$http = eZHTTPTool::instance();
$module = $Params["Module"];
$hashKey = $Params["HashKey"];
$hashKeyValidated = false;
$mbpaex_ini = eZINI::instance('mbpaex.ini');
$forgotPasswdHashLifeTime = $mbpaex_ini->variable( "mbpaexSettings", "ForgotPasswordHashLifeTime" );
$ini = eZINI::instance();

if ( strlen( $hashKey ) == 32 )
{
    $currentTime = time();

    $forgotPasswdObj = eZForgotPassword::fetchByKey( $hashKey );
    if (!is_object($forgotPasswdObj)) // HashKey not found
    {
        // if audit is enabled password changes should be logged
        eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'HashKey' => $hashKey,
                                                                'Comment' => 'HashKey not found' ) );

        $tpl->setVariable( 'wrong_key', true );
    }
    elseif (($currentTime - $forgotPasswdObj->attribute('time')) > $forgotPasswdHashLifeTime) // HashKey expired
    {
        // if audit is enabled password changes should be logged
        eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'HashKey' => $hashKey,
                                                                'Comment' => 'HashKey expired, proceed to remove' ) );

        $forgotPasswdObj->remove(); // Remove expired hash
        $tpl->setVariable( 'wrong_key', true );
    }
    else // HashKey OK -> show/process change password form
    {
        $tpl->setVariable( 'password_form', true );
        $tpl->setVariable( 'HashKey', $hashKey );
        $hashKeyValidated = true;
    }
}
else if ( strlen( $hashKey ) > 4 )
{
    // if audit is enabled password changes should be logged
    eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'HashKey' => $hashKey,
                                                            'Comment' => 'HashKey not found' ) );

    $tpl->setVariable( 'wrong_key', true );
}

if ( $module->isCurrentAction( "Generate" ) )
{
    $passwordLength = $ini->variable( "UserSettings", "GeneratePasswordLength" );
    $password = eZUser::createPassword( $passwordLength );
    $passwordConfirm = $password;

    if ( $module->hasActionParameter( "Email" ) && eZMail::validate($module->actionParameter( "Email" )) )
    {
        $email = $module->actionParameter( "Email" );
        if ( trim( $email ) != "" )
        {
            $users = eZPersistentObject::fetchObjectList( eZUser::definition(),
                                                       null,
                                                       array( 'email' => $email ),
                                                       null,
                                                       null,
                                                       true );
        }
        if ( !empty( $users ) )
        {
            $user = $users[0];
            $time = time();
            $hashKey = md5( $time . ":" . mt_rand() );

            $db = eZDB::instance();
            $db->begin();

            // Remove previous generated hash keys for same user
            eZForgotPassword::removeByUserID($user->id());

            $forgotPasswdObj = eZForgotPassword::createNew( $user->id(), $hashKey, $time );
            $forgotPasswdObj->store();

            $userToSendEmail = $user;
            $receiver = $email;

            $mail = new eZMail();
            if ( !$mail->validate( $receiver ) )
            {
            }
            $tpl = eZTemplate::factory();
            $tpl->setVariable( 'user', $userToSendEmail );
            $tpl->setVariable( 'object', $userToSendEmail->attribute( 'contentobject' ) );
            $tpl->setVariable( 'password', $password );
            $tpl->setVariable( 'link', true );
            $tpl->setVariable( 'hash_key', $hashKey );
            $tpl->setVariable( 'hash_key_lifetime', date("d/m/Y H:i",time() + $forgotPasswdHashLifeTime));

            $http = eZHTTPTool::instance();
            $http->UseFullUrl = true;
            $templateResult = $tpl->fetch( 'design:userpaex/forgotpasswordmail.tpl' );
            $http->UseFullUrl = false;
            $emailSender = $ini->variable( 'MailSettings', 'EmailSender' );
            if ( !$emailSender )
                $emailSender = $ini->variable( 'MailSettings', 'AdminEmail' );
            $mail->setSender( $emailSender );
            $mail->setReceiver( $receiver );
            $subject = ezpI18n::tr( 'kernel/user/register', 'Registration info' );
            if ( $tpl->hasVariable( 'subject' ) )
                $subject = $tpl->variable( 'subject' );
            $mail->setSubject( $subject );
            $mail->setBody( $templateResult );
            $mailResult = eZMailTransport::send( $mail );
            $tpl->setVariable( 'email', $email );
            $db->commit();

            // if audit is enabled password changes should be logged
            eZAudit::writeAudit( 'user-forgotpassword', array( 'Email' => $email,
                                                               'Comment' => 'Forgotpassword email sent' ) );

        }
        else
        {
            // if audit is enabled password changes should be logged
            eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'Email' => $email,
                                                                    'Comment' => 'Email address not found' ) );

            $tpl->setVariable( 'wrong_email', $email );
        }
    }
}
else if ( $module->isCurrentAction( "ChangePassword" ) && $hashKeyValidated )
{
    if ( $module->hasActionParameter( "NewPassword" ) )
        $newPassword = $module->actionParameter( "NewPassword" );
    else
        $newPassword = false;

    if ( $module->hasActionParameter( "NewPasswordConfirm" ) )
        $confirmPassword = $module->actionParameter( "NewPasswordConfirm" );
    else
        $confirmPassword = false;

    // The forgotPasswdObj was previously validated, fetch the corresponding user object
    $UserID = $forgotPasswdObj->attribute( 'user_id' );
    $user = eZUser::fetch($UserID);
    $login = $user->attribute( "login" );
    $type = $user->attribute( "password_hash_type" );
    $hash = $user->attribute( "password_hash" );
    $site = $user->site();

    if (  $newPassword ==  $confirmPassword )
    {
        if ( !$user->validatePassword($newPassword) ) // Password must meet "old" validation rules
        {
            // if audit is enabled password changes should be logged
            eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'UserID' => $UserID, 'Login' => $login,
                                                                    'Comment: ' => 'Password not pass standard validation' ) );

            $tpl->setVariable( 'newPasswordNotValidate', true);
        }
        else
        {
            // Patch for use mbpaex::validatePassword
            $paex = eZPaEx::getPaEx($UserID);
            if (!$paex->validatePassword($newPassword))
            {
                // if audit is enabled password changes should be logged
                eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'UserID' => $UserID, 'Login' => $login,
                                                                        'Comment: ' => 'Password not pass PAEX validation' ) );

                $tpl->setVariable( 'newPasswordNotValidate', true);
            }
            else
            {
                $newHash = $user->createHash( $login, $newPassword, $site, $type );
                if ($newHash == $user->attribute('password_hash'))
                {
                    // if audit is enabled password changes should be logged
                    eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'UserID' => $UserID, 'Login' => $login,
                                                                            'Comment: ' => 'New and old password are the same' ) );

                    $tpl->setVariable( 'newPasswordMustDiffer', true);
                }
                else
                {
                    // if audit is enabled password changes should be logged
                    eZAudit::writeAudit( 'user-forgotpassword', array( 'UserID' => $UserID, 'Login' => $login,
                                                                       'Comment: ' => 'Password changed successfully' ) );

                    $user->setAttribute( "password_hash", $newHash );
                    $user->store();
                    $paex->resetPasswordLastUpdated();

                    $forgotPasswdObj->remove();
                    $tpl->setVariable( 'password_changed', true);
                }
            }
        }
    }
    else
    {
        // if audit is enabled password changes should be logged
        eZAudit::writeAudit( 'user-forgotpassword-fail', array( 'UserID' => $UserID, 'Login' => $login,
                                                                'Comment: ' => 'Password not match password confirmation' ) );

        $tpl->setVariable( 'newPasswordNotMatch', true);
    }
}

$Result = array();
$Result['content'] = $tpl->fetch( 'design:userpaex/forgotpassword.tpl' );
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'kernel/user', 'User' ),
                                'url' => false ),
                         array( 'text' => ezpI18n::tr( 'kernel/user', 'Forgot password' ),
                                'url' => false ) );

if ( $ini->variable( 'SiteSettings', 'LoginPage' ) == 'custom' )
{
    $Result['pagelayout'] = 'loginpagelayout.tpl';
}

?>
