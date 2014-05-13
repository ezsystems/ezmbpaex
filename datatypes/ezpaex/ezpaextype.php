<?php
/**
 * File containing the ezpaextype class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

/**
 * Handles the datatype used for password expiration. Using this datatype
 * you can assing expiry parameters for password in user and user group
 * classes.
 */
class ezpaextype extends eZDataType
{
    const DATA_TYPE_STRING = "ezpaex";

    /**
     * Constructor
     */
    function ezpaextype()
    {
        $this->eZDataType( self::DATA_TYPE_STRING, ezpI18n::tr( 'mbpaex/classes/datatypes', "Password Expiration", 'Datatype name' ),
                           array( 'translation_allowed' => false,
                                  'serialize_supported' => true ) );
    }

    /**
     * Delete stored object attribute
     */
    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        $db = eZDB::instance();
        $paexID = $contentObjectAttribute->attribute( "contentobject_id" );

        $res = $db->arrayQuery( "SELECT COUNT(*) AS version_count FROM ezcontentobject_version WHERE contentobject_id = $paexID" );
        $versionCount = $res[0]['version_count'];

        if ( $version == null || $versionCount <= 1 )
        {
            eZPaEx::removePaex( $paexID );
        }
    }

	/**
     * Validates input on content object level
     *
     * @return eZInputValidator::STATE_ACCEPTED or eZInputValidator::STATE_INVALID if
     *         the values are accepted or not
     */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $integerValidator = new eZIntegerValidator(0);

        $passwordlifetime = false;
        $expirationnotification = false;
        $updatechildren = false;

        if ( $http->hasPostVariable( $base . "_data_paex_passwordlifetime_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $passwordlifetime = $http->postVariable( $base . "_data_paex_passwordlifetime_" . $contentObjectAttribute->attribute( "id" ) );
        }
        if ( $http->hasPostVariable( $base . "_data_paex_expirationnotification_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $expirationnotification = $http->postVariable( $base . "_data_paex_expirationnotification_" . $contentObjectAttribute->attribute( "id" ) );
        }

        // Check if passwordlifetime is set, that it's an integer >= 0
        $statusPasswordlifetime = $integerValidator->validate( $passwordlifetime );
        if ( $statusPasswordlifetime != eZInputValidator::STATE_ACCEPTED && trim( $passwordlifetime ) )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'mbpaex/classes/datatypes',
                                                                 'The password lifetime must be an integer >= 0' ) );
            return eZInputValidator::STATE_INVALID;
        }

        // Check if expirationnotification is set, that it's an integer >= 86400 (1 day)
        $integerValidator->setRange( 86400, false );
        $statusExpirationnotification = $integerValidator->validate( $expirationnotification );
        if ( $statusExpirationnotification != eZInputValidator::STATE_ACCEPTED && trim( $expirationnotification ) )
        {
            $contentObjectAttribute->setValidationError( ezpI18n::tr( 'mbpaex/classes/datatypes',
                                                                 'The expiration notification time must be an integer >= 86400' ) );
            return eZInputValidator::STATE_INVALID;
        }

        // Check if password validates regexp
        $contentObjectID = $contentObjectAttribute->attribute( "contentobject_id" );

        // Check if paex object for the current coID exists, use default one if needed.
        $paex = $contentObjectAttribute->content();
        if ( $paex === null )
        {
            $paex = eZPaEx::create( $contentObjectID );
        }

        // If the current contentobject is a user, check if the password has changed and validate if needed
        if ( $paex->isUser() )
        {
            // Search for password entered in the form
            foreach ( $http->postVariable( $base . '_id' ) as $coaid )
            {
                if ($http->hasPostVariable($base . '_data_user_password_' . $coaid))
                {
                    $newPassword = $http->postVariable($base . '_data_user_password_' . $coaid);
                    break;
                }
            }

            if (trim($newPassword) && ($newPassword != "_ezpassword"))
            {
                if ( $paex->canEdit() )
                {
                    if ($http->hasPostVariable( $base . "_data_paex_passwordvalidationregexp_" . $contentObjectAttribute->attribute( "id" ) ))
                    {
                        $paex->setAttribute('passwordvalidationregexp', $http->postVariable( $base . "_data_paex_passwordvalidationregexp_" . $contentObjectAttribute->attribute( "id" ) ));
                    }
                }
                if (!$paex->validatePassword($newPassword))
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'mbpaex/classes/datatypes',
                                                                         "The password doesn't match the validation rule.
                                                                         Previous password will be preserved if there is any." ) );
                    return eZInputValidator::STATE_INVALID;
                }
            }
        }
        else // If we are updating a group, try to check the updatechildren checkbox
        {
            if ($http->hasPostVariable( $base . "_data_paex_updatechildren_" . $contentObjectAttribute->attribute( "id" ) ))
            {
                $updatechildren = $http->postVariable( $base . "_data_paex_updatechildren_" . $contentObjectAttribute->attribute( "id" ) );
            }
            // Check if updatechildren is set, that it's an integer 0 or 1 (1 day)
            $integerValidator->setRange(0, 1);
            $statusUpdatechildren = $integerValidator->validate($updatechildren);
            if ( $statusUpdatechildren != eZInputValidator::STATE_ACCEPTED && trim($updatechildren) )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'mbpaex/classes/datatypes',
                                                                     'Wrong value in updatechildren field' ) );
                return eZInputValidator::STATE_INVALID;
            }
        }
        return eZInputValidator::STATE_ACCEPTED;
    }

    /**
     * Fetches all variables from the object
     *
     * @return bool true if fetching of class attributes are successfull, false if not
     */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $contentObjectID = $contentObjectAttribute->attribute( "contentobject_id" );

        // check if paex object for the current coID exists, create if needed.
        $paex = $contentObjectAttribute->content();
        if ( $paex === null )
        {
            $paex = eZPaEx::create( $contentObjectID );
        }

        // Set current values as default ones
        $passwordvalidationregexp   = $paex->attribute( 'passwordvalidationregexp' );
        $passwordlifetime           = $paex->attribute( 'passwordlifetime' );
        $expirationnotification     = $paex->attribute( 'expirationnotification' );
        $passwordLastUpdated        = $paex->attribute( 'password_last_updated' );
        $updatechildren             = $paex->attribute( 'updatechildren' );
        $expirationnotificationSent = $paex->attribute( 'expirationnotification_sent' );

        // Update current values with new ones entered in the form if there are any
        if ($http->hasPostVariable( $base . "_data_paex_passwordvalidationregexp_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $passwordvalidationregexp = $http->postVariable( $base . "_data_paex_passwordvalidationregexp_" . $contentObjectAttribute->attribute( "id" ) );
        }

        if ($http->hasPostVariable( $base . "_data_paex_passwordlifetime_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $passwordlifetime = $http->postVariable( $base . "_data_paex_passwordlifetime_" . $contentObjectAttribute->attribute( "id" ) );
        }

        if ($http->hasPostVariable( $base . "_data_paex_expirationnotification_" . $contentObjectAttribute->attribute( "id" ) ) )
        {
            $expirationnotification = $http->postVariable( $base . "_data_paex_expirationnotification_" . $contentObjectAttribute->attribute( "id" ) );
        }
        // Be sure passwordlifetime is set
        if ( trim( $passwordlifetime ) == '' )
        {
            $passwordlifetime = eZPaEx::NOT_DEFINED;
        }

        // Be sure expirationnotification is set
        if ( trim( $expirationnotification ) == '' )
        {
            $expirationnotification = eZPaEx::NOT_DEFINED;
        }

        // If we are editing a user account set it's password_last_updated as needed.
        if ( $paex->isUser() )
        {
            // Search for password entered in the form
            $newPassword = "";
            foreach ( $http->postVariable( $base . '_id' ) as $coaid )
            {
                if ( $http->hasPostVariable( $base . '_data_user_password_' . $coaid ) )
                {
                    $newPassword = $http->postVariable( $base . '_data_user_password_' . $coaid );
                    break;
                }
            }
            // Check if the password has changed
            if ( trim( $newPassword ) && ( $newPassword != "_ezpassword" ) )
            {
                $currentUserID = eZUser::currentUserID();
                if ( $currentUserID == $contentObjectID )
                {
                    // If self editing, set last_updated to current time
                    $passwordLastUpdated = time();

                    // if audit is enabled password changes should be logged
                    eZAudit::writeAudit( 'user-password-change-self', array( ) );
                }
                else if ( $currentUserID == eZUser::anonymousId() )
                {
                    // register, @see http://issues.ez.no/15391
                    $passwordLastUpdated = time();
                }
                else
                {
                    // If changing other user's password, set last_updated to 0 to force
                    // password change in the next connection
                    $passwordLastUpdated = 0;

                    // if audit is enabled password changes should be logged
                    $targetUser = eZUser::fetch( $contentObjectID );
                    eZAudit::writeAudit( 'user-password-change', array( 'User id' => $targetUser->attribute( 'contentobject_id' ), 'User login' => $targetUser->attribute( 'login' ) ) );
                }
                // Password has changed, reset expirationnotification_sent flag to send again a notification when this new password be about to expire
                $expirationnotificationSent = 0;
            }
        }
        else
        {
            // If we are updating a user group and don't have the updatechildren post var, set updatechildren flag to disabled
            if ($http->hasPostVariable( $base . "_data_paex_updatechildren_" . $contentObjectAttribute->attribute( "id" ) ))
            {
                $updatechildren = $http->postVariable( $base . "_data_paex_updatechildren_" . $contentObjectAttribute->attribute( "id" ) );
            }
            else
            {
                $updatechildren = 0;
            }
        }

		if ( $paex->canEdit() )
        {
            // If user has permission, update full paex object with possible new values
            $paex->setInformation( $contentObjectID, $passwordvalidationregexp, $passwordlifetime, $expirationnotification, $passwordLastUpdated, $updatechildren, $expirationnotificationSent );
        }
        else
        {
            // If user don't have permission to update paex data, only update the password_last_updated and expirationnotification_sent fields
            $paex->setAttribute( 'password_last_updated',$passwordLastUpdated );
            $paex->setAttribute( 'expirationnotification_sent',$expirationnotificationSent );
        }

        $contentObjectAttribute->setContent( $paex );
        return true;
    }

	/**
     * Store the content.
     */
    function storeObjectAttribute( $contentObjectAttribute )
    {
        $paex = $contentObjectAttribute->content();
        if ( !$paex instanceof eZPaEx )
        {
			// create a default paex object
            $paex = eZPaEx::create( $contentObjectAttribute->attribute( "contentobject_id" ) );
        }
        $paex->store();
        $contentObjectAttribute->setContent( $paex );
    }

    /**
     * Get empty paex data values from parent.
     *
     * @return bool True if the value was stored correctly.
     */
    function onPublish( $contentObjectAttribute, $contentObject, $publishedNodes )
    {
        eZDebug::writeDebug( 'Start', __METHOD__ );
        $paex = $contentObjectAttribute->content();
        if ( !$paex instanceof eZPaEx )
        {
            return true;
        }

        // Update empty paex data from parent paex
        // NOTE: if the current user don't have permission to edit paex data, and is
        // creating a new object (publishing version 1), force paex object update
        // to get values set in parent
        if ( !$paex->canEdit() && $contentObject->attribute( 'current_version' ) == 1 )
            $paex->updateFromParent( true );
        else
            $paex->updateFromParent();

        eZDebug::writeDebug( 'End', __METHOD__ );
        return true;
    }

    /**
     * @return bool true if the datatype finds any content in the attribute \a $contentObjectAttribute.
     */
    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $paex = $this->objectAttributeContent( $contentObjectAttribute );
        if ( is_object( $paex ) )
            return true;
        return false;
    }

	/**
     * Returns the content.
     */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $paexID = $contentObjectAttribute->attribute( "contentobject_id" );
        $paex = eZPaEx::fetch( $paexID );
        return $paex;
    }

    /**
     * Returns the meta data used for storing search indeces.
     */
    function metaData( $contentObjectAttribute )
    {
		return $contentObjectAttribute->attribute('id');
	}

    /**
     * Returns the value as it will be shown if this attribute is used in the
	 * object name pattern.
     */
    function title( $contentObjectAttribute, $name = null )
    {
		return $contentObjectAttribute->attribute('id');
    }

    /**
     * @return true if the datatype can be indexed
     */
    function isIndexable()
    {
        return true;
    }
}

eZDataType::register( ezpaextype::DATA_TYPE_STRING, "ezpaextype" );
?>
