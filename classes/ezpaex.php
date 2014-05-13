<?php
/**
 * File containing the eZPaEx class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

class eZPaEx extends eZPersistentObject
{
    const NOT_DEFINED = -1;

    function eZPaEx( $row )
    {
        $this->eZPersistentObject( $row );
    }

    static function definition()
    {
        return array( 'fields' => array( 'contentobject_id' => array( 'name' => 'ContentObjectID',
                                                                      'datatype' => 'integer',
                                                                      'default' => 0,
                                                                      'required' => true,
                                                                      'foreign_class' => 'eZContentObject',
                                                                      'foreign_attribute' => 'id',
                                                                      'multiplicity' => '0..1' ),
                                         'passwordvalidationregexp' => array( 'name' => 'PasswordValidationRegexp',
                                                           'datatype' => 'string',
                                                           'default' => '',
                                                           'required' => false ),
                                         'passwordlifetime' => array( 'name' => 'PasswordLifeTime',
                                                           'datatype' => 'integer',
                                                           'default' => '',
                                                           'required' => false ),
                                         'expirationnotification' => array( 'name' => 'ExpirationNotification',
                                                                   'datatype' => 'integer',
                                                                   'default' => '',
                                                                   'required' => false ),
                                         'password_last_updated' => array( 'name' => 'PasswordLastUpdated',
                                                                   'datatype' => 'integer',
                                                                   'default' => '',
                                                                   'required' => false ),
                                         'updatechildren' => array( 'name' => 'UpdateChildren',
                                                                   'datatype' => 'integer',
                                                                   'default' => '0',
                                                                   'required' => false ),
                                         'expirationnotification_sent' => array( 'name' => 'ExpirationNotificationSent',
                                                                   'datatype' => 'integer',
                                                                   'default' => '0',
                                                                   'required' => false ) ),
                      'keys' => array( 'contentobject_id' ),
                      'function_attributes' => array( 'contentobject' => 'contentObject',
                                                      'is_expired' => 'isExpired',
													  'is_user' => 'isUser',
													  'has_regexp' => 'hasRegexp',
													  'has_lifetime' => 'hasLifeTime',
													  'has_notification' => 'hasNotification',
                                                      'can_edit' => 'canEdit',
                                                      'is_updatechildrenpending' => 'isUpdateChildrenPending' ),
                      'relations' => array( 'contentobject_id' => array( 'class' => 'ezcontentobject',
                                                                         'field' => 'id' ) ),
                      'class_name' => 'eZPaEx',
                      'name' => 'ezx_mbpaex' );
    }

    /**
	 * Creates a default ezpaex object for the corresponding contentobject_id
     *
	 * @param int $contentObjectID contentobject id of the ezpaex object
	 * @return eZPaEx object
	 */
	static function create( $contentObjectID )
    {
        // Still missing data for paex, try to complete it from user groups
        $ini = eZINI::instance( 'mbpaex.ini' );

        // Get default values from ini
        $iniPasswordValidationRegexp = $ini->variable( 'mbpaexSettings', 'PasswordValidationRegexp' );
        $iniDefaultPasswordLifeTime  = $ini->variable( 'mbpaexSettings', 'DefaultPasswordLifeTime' );
        $iniExpirationNotification   = $ini->variable( 'mbpaexSettings', 'ExpirationNotification' );

        $row = array(
            'contentobject_id' => $contentObjectID,
            'passwordvalidationregexp' => $iniPasswordValidationRegexp,
            'passwordlifetime' => $iniDefaultPasswordLifeTime,
            'expirationnotification' => $iniExpirationNotification,
            'password_last_updated' => 0,
            'updatechildren' => self::NOT_DEFINED,
            'expirationnotification_sent' => 0
            );

        return new eZPaEx( $row );
    }

    /**
     * Fills in the $id, $passwordvalidationregexp, $passwordlifetime
     * and $expirationnotification $password_last_updated for the paex
     *
     * @param int $id ID of the contentobject this paex belongs to
     * @param string $passwordvalidationregexp Regexp to use in password validation
     * @param int $passwordlifetime Max password life time
     * @param int $expirationnotification Time before expiration to send a notification
     * @param int $password_last_updated Time the password for the contentobject this paex belongs to was updated
     * @param int $updatechildren 1 if the children of the main node have to be updated, 0 if not
     */
    function setInformation( $id, $passwordvalidationregexp, $passwordlifetime,
                             $expirationnotification, $password_last_updated = 0,
                             $updatechildren = 0, $expirationnotification_sent = 0 )
    {
        $this->setAttribute( "contentobject_id", $id );
        $this->setAttribute( "passwordvalidationregexp", $passwordvalidationregexp );
        $this->setAttribute( "passwordlifetime", $passwordlifetime );
        $this->setAttribute( "expirationnotification", $expirationnotification );
        $this->setAttribute( "password_last_updated", $password_last_updated );
        $this->setAttribute( "updatechildren", $updatechildren );
        $this->setAttribute( "expirationnotification_sent", $expirationnotification_sent );
    }

	/**
	 * Store the content of the ezpaex object in the database
	 */
	function store( $fieldFilters = null )
    {
        eZPersistentObject::store();
    }

    /**
     * Removes the ezpaex data from the mbpaex table.
     *
     * @param int $paexID ID of the paex object to remove
     */
    static function removePaex( $paexID )
    {
        eZPersistentObject::removeObject( eZPaEx::definition(),
                                          array( 'contentobject_id' => $paexID ) );
    }

	/**
	 *	Fetch the eZPaEx persitentobject
     *
	 * @param int $id contentobject_id to fetch
	 * @param bool $asObject	return the PO as an object or as an array
     *
	 * @return eZPaEx as PO or array
	 */
	static function fetch( $id, $asObject = true )
    {
        if ( !$id )
            return null;

        return eZPersistentObject::fetchObject( eZPaEx::definition(),
                                                null,
                                                array( 'contentobject_id' => $id ),
                                                $asObject );
    }

	/**
	 * Check if the password in the current paex object is expired
     *
     * @return true if the difference between today and password_last_updated date is
     *         greater than the value set in passwordlifetime
	 */
	function isExpired()
	{
        eZDebug::writeDebug( 'Check expiration', __METHOD__ );

        // If passwordlifetime is not defined or 0 (zero, infinite lifetime) passwords never expire.
        if ( ( $this->attribute( 'passwordlifetime' ) == self::NOT_DEFINED ) ||
             ( $this->attribute( 'passwordlifetime' ) == 0 ) )
            return false;

        $passwordLastUpdated = $this->attribute( 'password_last_updated' );
        $actualPasswordlifetime = ceil( ( ( ( ( time() - $passwordLastUpdated ) / 60 ) / 60 ) / 24 ) );

        eZDebug::writeDebug( $passwordLastUpdated, __METHOD__ );
        eZDebug::writeDebug( $actualPasswordlifetime, __METHOD__ );

        if ( ( $actualPasswordlifetime > $this->attribute( 'passwordlifetime' ) ) ||
               $passwordLastUpdated == 0 ||
               $passwordLastUpdated == self::NOT_DEFINED )
        {
            return true;
        }

		return false;
	}

	/**
	 * Check if the object that contains the ezpaex attribute is an user
     *
	 * @return bool if the contentobject is a user or not
	 */
	function isUser()
	{
		$ezpo = eZPersistentObject::fetchObject( eZContentObject::definition(),
                                                null,
                                                array( 'id' => $this->attribute('contentobject_id') ),
                                                true );
		return eZUser::isUserObject( $ezpo );
	}

	/**
	 * Check if validation regexp is defined or not
     *
	 * @return true/false
  	 */
	function hasRegexp()
	{
		if ( !is_null( $this->attribute('passwordvalidationregexp' ) ) &&
			( $this->attribute( 'passwordvalidationregexp' ) != self::NOT_DEFINED ) &&
			( $this->attribute( 'passwordvalidationregexp' ) != '' ) )
		{
			return true;
		}
		else
		{
		    return false;
		}
	}

	/**
	 * Check if default password lifetime is defined or not
     *
	 * @return bool
	 */
	function hasLifeTime()
	{
		if ( !is_null($this->attribute( 'passwordlifetime' ) ) &&
			( $this->attribute( 'passwordlifetime' ) != self::NOT_DEFINED ) &&
			( $this->attribute( 'passwordlifetime' ) != '' ) )
			return true;
		else
			return false;
	}

	/**
	 * Check if expiration notification is defined or not
     *
	 * @return bool
	 */
	function hasNotification()
	{
		if (!is_null( $this->attribute( 'expirationnotification' ) ) &&
			( $this->attribute( 'expirationnotification' ) != self::NOT_DEFINED ) &&
			( $this->attribute( 'expirationnotification' ) != '' ) )
			return true;
		else
			return false;
	}

	/**
	 * Check if password matches regexp validation
     *
     * @param string $password Actual password to check
	 * @return bool
	*/
    function validatePassword( $password )
    {
        eZDebug::writeDebug( 'Validate Password Start','eZPaEx::validatePassword' );
        if ( $this->hasRegexp() && ( $password != '_ezpassword' ) )
        {
            $regexp = preg_replace( array('/(^\/)/', '/(\/$)/'), '', $this->attribute( 'passwordvalidationregexp' ) );
            if ( !preg_match( '/' . $regexp . '/', $password ) )
            {
                eZDebug::writeDebug( 'Password KO', 'eZPaEx::validatePassword' );
                return false;
            }
        }
        eZDebug::writeDebug( 'Password OK', 'eZPaEx::validatePassword' );

        return true;
    }

    /**
     * Get actual values for PaEx data for the given contentobject id.
     * If not defined for the given coID, use defaults.
     *
     * @param int $ezcoid Contentobject id (user id) to get PaEx for
     * @param bool $checkIfUserHasDatatype See if user has paex datatype, default false
     * @return eZPaEx|null Actual PaEx applicable data, null if $checkIfUserHasDatatype = true
     *                     and object does not have ezpaex datatype
    */
    static function getPaEx( $ezcoid, $checkIfUserHasDatatype = false )
    {
        $currentPaex = eZPaEx::fetch( $ezcoid );

        // If we don't have paex object for the current object id, create a default one
        if ( !$currentPaex instanceof eZPaEx )
        {
            // unless user does not have paex datatype
            if ( $checkIfUserHasDatatype )
            {
                //eZContentObject::fetch( $ezcoid );
                $paexDataTypeCount = eZPersistentObject::count( eZContentObjectAttribute::definition(),
                                                array( 'contentobject_id' => $ezcoid,
                                                       'data_type_string' => ezpaextype::DATA_TYPE_STRING ),
                                                'id' );
                if ( !$paexDataTypeCount )
                {
                    eZDebug::writeDebug( "User id {$ezcoid} does not have paex datatype", __METHOD__ );
                    return null;
                }
            }
            return eZPaEx::create( $ezcoid );
        }

        // Get default paex values from ini to use in case there is anyone missing in the object
        $ini = eZINI::instance( 'mbpaex.ini' );
        $iniPasswordValidationRegexp = $ini->variable( 'mbpaexSettings', 'PasswordValidationRegexp' );
        $iniDefaultPasswordLifeTime  = $ini->variable( 'mbpaexSettings', 'DefaultPasswordLifeTime' );
        $iniExpirationNotification   = $ini->variable( 'mbpaexSettings', 'ExpirationNotification' );

        // If still any empty values in the paex object, set defaults from ini
        if ( !$currentPaex->hasRegexp() )
        {
            $currentPaex->setAttribute( 'passwordvalidationregexp', $iniPasswordValidationRegexp );
            eZDebug::writeDebug( 'Regexp empty, used default: "' . $iniPasswordValidationRegexp .'"', 'eZPaEx::getPaEx' );
        }
        if ( !$currentPaex->hasLifeTime() )
        {
            $currentPaex->setAttribute( 'passwordlifetime', $iniDefaultPasswordLifeTime );
            eZDebug::writeDebug( 'PasswordLifeTime empty, used default: "' . $iniDefaultPasswordLifeTime . '"', 'eZPaEx::getPaEx' );
        }
        if ( !$currentPaex->hasNotification() )
        {
            $currentPaex->setAttribute( 'expirationnotification', $iniExpirationNotification );
            eZDebug::writeDebug( 'ExpirationNotification empty, used default: "' . $iniPasswordValidationRegexp . '"', 'eZPaEx::getPaEx' );
        }

        eZDebug::writeDebug( 'PasswordLastUpdated value: "' . $currentPaex->attribute( 'password_last_updated' ) . '"', 'eZPaEx::getPaEx' );

        return $currentPaex;
    }

    /**
     * Check if current user has permissions to edit the paex data
     *
     * @return true if the user has editpaex policy, false if not.
     */
    public static function canEdit()
    {
		// Get current user
		$user = eZUser::currentUser();
		$user_has_access = $user->hasAccessTo( 'userpaex', 'editpaex' );

		if ( $user_has_access['accessWord'] == "yes" )
            return true;

        return false;
    }

    /**
     * Generate array of paex objects to update based on updatechildren status
     *
     * @param array $paex_to_update     Array of already updated paex objects
     * @return array of paex objects to update, with final values set.
     */
    function generateUpdateChildren( $paexToUpdate = array() )
    {
        eZDebug::writeDebug( 'Start', __METHOD__ );

        if ( !$this->attribute( 'updatechildren' ) )
        {
            eZDebug::writeDebug( 'UpdateChildren flag disabled. Nothing to do.', __METHOD__ );
        }
        else
        {
            $newPasswordvalidationregexp = $this->attribute( 'passwordvalidationregexp' );
            $newPasswordlifetime = $this->attribute( 'passwordlifetime' );
            $newExpirationnotification = $this->attribute( 'expirationnotification' );

            // Fetch the main node that belongs to the current paex
            $mainNodeID = eZContentObjectTreeNode::findMainNode( $this->attribute( 'contentobject_id' ) );
            eZDebug::writeDebug( 'Going to update subtree starting at node ' . $mainNodeID . '.', __METHOD__ );

            // Fetch the full subtree array to update
            $fullSubtree = eZContentObjectTreeNode::subTreeByNodeID( array( "MainNodeOnly" => false,
                                                                            "AsObject" => false ),
                                                                    $mainNodeID );

            foreach ( $fullSubtree as $node )
            {
                // Fetch the paex for each node
                $addPaexToUpdate = eZPaEx::fetch( $node["contentobject_id"] );
                // If the paex is not marked to updatechildren, update the paex object
                if ( $addPaexToUpdate instanceof eZPaEx  &&
                     $addPaexToUpdate->attribute( 'updatechildren' ) != 1 )
                {
                    $addPaexToUpdate->setAttribute('passwordvalidationregexp', $newPasswordvalidationregexp );
                    $addPaexToUpdate->setAttribute('passwordlifetime', $newPasswordlifetime );
                    $addPaexToUpdate->setAttribute('expirationnotification', $newExpirationnotification );
                    $paexToUpdate[$node["contentobject_id"]] = $addPaexToUpdate;
                }
                else
                {
                    eZDebug::writeDebug( 'Skipping object ' . $add_paex_to_update['contentobject_id'] , __METHOD__ );
                }
            }
        }
        eZDebug::writeDebug( 'End', __METHOD__ );

        return $paexToUpdate;
    }

    /**
     * Check if children update is pending
     *
     * @return true if updatechildren is set to 1 (pending update)
    */
    function isUpdateChildrenPending()
    {
        if ( $this->attribute( 'updatechildren' ) == 1 )
            return true;

        return false;
    }

    /**
     * Update current empty paex fields with values get from paex object of
     * parent of current main node.
     *
     * @param bool $forceUpdate
     * @return true
    */
    function updateFromParent( $forceUpdate = false )
    {
        $mainNode = eZContentObjectTreeNode::findMainNode( $this->attribute( 'contentobject_id' ), true );
        if ( !is_object( $mainNode ) )
        {
            eZDebug::writeDebug( 'mainNode not found', 'eZPaEx::updateFromParent' );
        }
        elseif ( $mainNode->attribute( 'depth' ) > 1 )
        {
            $parentMainNodeID = $mainNode->attribute('parent_node_id');
            $parentContentObject = eZContentObject::fetchByNodeID( $parentMainNodeID );
            $parentPaex = eZPaEx::getPaEx($parentContentObject->attribute('id'));
            if ( $parentPaex instanceof eZPaEx )
            {
                $paexUpdated = false;

                if ( !$this->hasRegexp() || $forceUpdate )
                {
                    $this->setAttribute( 'passwordvalidationregexp', $parentPaex->attribute( 'passwordvalidationregexp' ) );
                    $paexUpdated = true;
                }

                if ( !$this->hasLifeTime() || $forceUpdate )
                {
                    $this->setAttribute( 'passwordlifetime', $parentPaex->attribute( 'passwordlifetime' ) );
                    $paexUpdated = true;
                }

                if ( !$this->hasNotification() || $forceUpdate )
                {
                    $this->setAttribute( 'expirationnotification', $parentPaex->attribute( 'expirationnotification' ) );
                    $paexUpdated = true;
                }

                if ( $paexUpdated )
                {
                    eZDebug::writeDebug( 'Paex updated from parent', 'eZPaEx::updateFromParent' );
                    $this->store();
                }
            }
        }

        return true;
    }

    /**
     * Fetch the eZPaEx objects that have their password about to expire
     *
     * @return array of eZPaEx objects corresponding to users that have to be notified
     */
    static function fetchExpiryNotificationPendingList()
    {
        $currentTime = time();
        $conds = array( 'expirationnotification_sent' => 0,
                        'passwordlifetime' => array( '>', 0 ) );

        $customConds = ' AND (passwordlifetime *86400 - (' . $currentTime . ' - password_last_updated ) ) < expirationnotification ';

        $userClassIDs = array();
        foreach ( eZUser::fetchUserClassList( true ) as $userClass )
        {
            $userClassIDs[] = $userClass->attribute( 'id' );
        }
        if ( empty( $userClassIDs ) )
        {
            return array();
        }
        $customConds .= ' AND ezcontentobject.id = contentobject_id ';
        $customConds .= ' AND ' . eZDB::instance()->generateSQLINStatement( $userClassIDs, 'ezcontentobject.contentclass_id' );

        return eZPersistentObject::fetchObjectList( eZPaEx::definition(),
                                                    null,
                                                    $conds,
                                                    null,
                                                    null,
                                                    true,
                                                    false,
                                                    null,
                                                    array( 'ezcontentobject' ),
                                                    $customConds );
    }

    /**
     * Send password expiry notification to user
     *
     * @param eZUser $user ezuser object that contains the destination email address
     * @return true if notification sent correctly, false if not.
     */
    function sendExpiryNotification( $user )
    {
        $userToSendEmail = $user;
        $receiver = $userToSendEmail->attribute( 'email' );

        $mail = new eZMail();
        if ( !$mail->validate( $receiver ) )
        {
            eZDebug::writeError( 'Invalid email address set in user ' . $user->attribute( 'contentobject_id' ), 'sendExpiryNotification' );
            return false;
        }
        $tpl = eZTemplate::factory();
        $tpl->setVariable( 'user', $userToSendEmail );

        $http = eZHTTPTool::instance();
        $http->UseFullUrl = false;

        $templateResult = $tpl->fetch( 'design:userpaex/expirynotificationmail.tpl' );

        $ini = eZINI::instance();
        $emailSender = $ini->variable( 'MailSettings', 'EmailSender' );
        if ( !$emailSender )
            $emailSender = $ini->variable( 'MailSettings', 'AdminEmail' );

        $mail->setSender( $emailSender );
        $mail->setReceiver( $receiver );

        $subject = ezpI18n::tr( 'mbpaex/userpaex', 'Your password is about to expire' );
        if ( $tpl->hasVariable( 'subject' ) )
            $subject = $tpl->variable( 'subject' );

        $mail->setSubject( $subject );
        $mail->setBody( $templateResult );

        return eZMailTransport::send( $mail );
    }

    /**
     * Set attribute expirationnotification_sent to true to prevent multiple
     * notifications sent to the same user.
     */
    function setExpiryNotificationSent()
    {
        $this->setAttribute( 'expirationnotification_sent', 1 );
        $this->store();
    }

    /**
     * Function called after password update.
     * Set attribute password_last_updated to current_time
     * Set attribute expirationnotification_sent to false
     */
    function resetPasswordLastUpdated()
    {
        $this->setAttribute( "password_last_updated", time() );
        $this->setAttribute( "expirationnotification_sent", 0 );
        $this->store();
    }

    /**
     * Fetch the eZPaEx objects that have updatechildren flag set to 1
     *
     * @param bool $asObject
     * @return array of contentobject_id's corresponding to users that have to be notified
     */
    static function fetchUpdateChildrenPendingList( $asObject = true )
    {
        $resultArray = array();

        $conds = array( 'updatechildren' => 1 );

        $pendingList = eZPersistentObject::fetchObjectList( eZPaEx::definition(),
                                                    null,
                                                    $conds,
                                                    null,
                                                    null,
                                                    $asObject );
        if (is_array( $pendingList ) && count( $pendingList ) )
        {
            foreach ( $pendingList as $pendingItem )
            {
                $resultArray[$pendingItem->attribute( 'contentobject_id' )] = $pendingItem;
            }
        }

        return $resultArray;
    }

    /**
     * DEPRECATED since querying a non-existent table will always trigger a fatal error
     *
     * Checks if the database schema has been created, in order to prevent the
     * datatype from being registered if it hasn't
     *
     * @deprecated
     */
    static function schemaCreated()
    {
        $db = eZDB::instance();
        return ( $db->query( "SELECT * FROM ezx_mbpaex" ) !== false );
    }
}

?>
