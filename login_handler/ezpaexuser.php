<?php
/**
 * File containing the eZPaExUser class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

/**
 * Handles logins for users using new Password Expiry constraints
 *
 * The handler will check that the user password is not expired and then log the
 * user in or force password change if correct password is entered but it is
 * expired.
 *
 * Once a login is requested by a user the handler will do one of two things:
 * - Login the user with the existing user object found in the system
 * - Force user to change their password if it is expired
 *
 */
class eZPaExUser extends eZUser
{
    /**
     * Holds the URL to the password change form
     *
     * @var string
     */
    protected static $changePasswordFormURL = "/userpaex/password/";

    /**
     * Pure wrapper for eZUser::__construct( $row ). Used to mute errors due to the absence of $row as parameter,
     * when called from eZUserLoginHandler::instance(), while according to the signature of eZUser::eZUser(), $row
     * is not optional.
     * This should be removed as soon as eZUser::eZUser() is fixed.
     *
     * @param string $row eZPersistenObject-compliant data row.
     * @see eZUser::eZUser()
     *
     */
    public function __construct( $row = null )
    {
        parent::eZUser( $row );
    }

    /**
     * Logs in the user if applied login and password is valid.
     *
     * @param string $login
     * @param string $password
     * @param bool $authenticationMatch
     * @return mixed eZUser or false
     */
    public static function loginUser( $login, $password, $authenticationMatch = false )
    {
        $user = self::_loginUser( $login, $password, $authenticationMatch );
        if ( $user instanceof eZUser )
        {
            $userID = $user->attribute( 'contentobject_id' );
            $paex = eZPaEx::getPaEx( $userID, true );

            if ( $paex instanceof eZPaEx && $paex->isExpired() )
            {
                self::passwordHasExpired( $user );
                return false;
            }
            else
            {
                self::loginSucceeded( $user );
                return $user;
            }
        }
        else
        {
            self::loginFailed( $user, $login );
            return false;
        }

        return $user;
    }

    /**
     * Writes audit information and redirects the user to the password change form.
     *
     * @param eZUser $user
     */
    protected static function passwordHasExpired( $user )
    {
        $userID = $user->attribute( 'contentobject_id' );

        // Password expired
        eZDebugSetting::writeDebug( 'kernel-user', $user, 'user password expired' );

        // Failed login attempts should be logged
        $userIDAudit = isset( $userID ) ? $userID : 'null';
        $loginEscaped = eZDB::instance()->escapeString( $user->attribute( 'login' ) );
        eZAudit::writeAudit( 'user-failed-login', array( 'User id' => $userIDAudit,
                                                         'User login' => $loginEscaped,
                                                         'Comment' => 'Failed login attempt: Password Expired. eZPaExUser::loginUser()' ) );

         // Redirect user to password change form
         self::redirectToChangePasswordForm( $userID );
    }

    /**
     * Performs a redirect to the password change form
     *
     * @param int $userID
     */
    protected static function redirectToChangePasswordForm( $userID )
    {
        $http = eZHTTPTool::instance();
        $url = self::$changePasswordFormURL . $userID;
        eZURI::transformURI( $url );
        $http->redirect( $url );
    }
}

?>
