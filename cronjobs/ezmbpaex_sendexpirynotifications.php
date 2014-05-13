<?php
/**
 * File containing the sendexpirynotifications CLI script
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

$cli->output( "eZPaEx: Send expiry notifications process start" );

ini_set( 'max_execution_time', 0);
ini_set( 'memory_limit', '-1' );

eZDebug::addTimingPoint( 'Fetch notification pending list' );

$paexPendingList = eZPaEx::fetchExpiryNotificationPendingList();
$paexPendingListCount = count( $paexPendingList );

if ( !$paexPendingListCount )
{
    $cli->output( "No pending update notifications found" );
}
else
{
    $cli->output( "Found " . $paexPendingListCount . " objects pending notification, send notifications:" );
    $totalNotificationsSent = 0;

    eZDebug::addTimingPoint( 'Send notifications' );
    foreach ( $paexPendingList as $paexObject )
    {
        $userID = $paexObject->attribute( 'contentobject_id' );
        $userObject = eZUser::fetch( $userID );
        if ( $userObject instanceof eZUser && $userObject->isEnabled() )
        {
            if ( !$paexObject->sendExpiryNotification( $userObject ) )
            {
                $cli->output( "Error sending notification. UserID: " . $userID );
            }
            else
            {
                $cli->output( "Notification sent ok. UserID: " . $userID );
                $paexObject->setExpiryNotificationSent();
                $totalNotificationsSent++;
            }
        }
        else
        {
            $cli->output( "Error user_object disabled or not found. UserID: ". $userID );
        }
    }
    $cli->output( "Sent " . $totalNotificationsSent . " notifications." );
}

$cli->output( "eZPaEx: Send expiry notifications process end" );

?>
