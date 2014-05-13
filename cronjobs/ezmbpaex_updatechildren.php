<?php
/**
 * File containing the updatechildren CLI script
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

$mbpaexIni = eZINI::instance( 'mbpaex.ini' );
// Fetch the user to use in the process
$updateChildrenUser = $mbpaexIni->variable( 'mbpaexSettings','UpdateChildrenUser' );
// Default to admin if user is not found in the ini
if ( !trim( $updateChildrenUser ) )
    $updateChildrenUser = 'admin';

$user = eZUser::fetchByName( $updateChildrenUser );
eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'contentobject_id' ) );

if ( $user->isLoggedIn() )
{
    $cli->output( "eZPaEx: Update children process start" );

    ini_set( 'max_execution_time', 0 );
    ini_set( 'memory_limit', '-1' );

    eZDebug::addTimingPoint( 'Fetch update pending list' );
    // Get list of paex objects marked to updatechildren
    $pendingList = eZPaEx::fetchUpdateChildrenPendingList();
    $pendingListCount = count( $pendingList );

    if ( !$pendingListCount )
    {
        $cli->output( "No pending update subtrees found" );
    }
    else
    {
        $cli->output( "Found " . $pendingListCount . " ezpaex objects with pending updatechildren" );
        $pendingIdList = array();
        foreach ($pendingList as $pendingObject)
        {
            $pendingIdList[] = $pendingObject->attribute( 'contentobject_id' );
        }

        // Fetch array of nodes corresponding to objects in the pendingIDList to sort them by depth
        $nodeArray =  eZContentObjectTreeNode::findMainNodeArray( $pendingIdList, false );

        // Make an array of objects ids with its deph based on the corresponding node
        $objectDepthList = array();
        foreach ($nodeArray as $key => $node)
        {
            $objectDepthList[0][$key] = $node["depth"];
            $objectDepthList[1][$key] = $node["contentobject_id"];
        }
        // Sort objectDepthList by depth to apply updatechildren in the right order
        if ( !array_multisort( $objectDepthList[0], SORT_ASC, $objectDepthList[1], SORT_ASC ) )
        {
            eZDebug::writeError( 'Error in array_multisort', 'ezmbpaex_updatechildren.php' );
        }
        else
        {
            // Generate array of paex objects to update
            $paexObjectArray = array();
            foreach ( $objectDepthList[1] as $contentobjectId )
            {
                if ( isset( $pendingList[$contentobjectId] ) )
                {
                    // Generate update children data for every pending object in pendingIDList
                    $paexObjectArray = $pendingList[$contentobjectId]->generateUpdateChildren( $paexObjectArray );
                }
                else
                {
                    eZDebug::writeError( 'Found contentobject_id [' . $contentobjectId . '] not present in the pendingIDList', 'ezmbpaex_updatechildren.php' );
                }
            }

            // Reset pending object updatechildren attribute in pendingIDList objects and add to the array of paex objects to update
            foreach ( $pendingList as $pendingObject )
            {
                $pendingObject->setAttribute( 'updatechildren', 0 );
                $paexObjectArray[$pendingObject->attribute( 'contentobject_id' )] = $pendingObject;
            }

            // Store updated paex objects in the DB
            $db = eZDB::instance();
            $db->begin();

            foreach ( $paexObjectArray as $paexObject )
            {
                $paexObject->store();
                eZContentCacheManager::clearContentCacheIfNeeded( $paexObject->attribute( 'contentobject_id' ) );
            }
            $db->commit();
        }
    }

    $cli->output( "eZPaEx: Update children process end" );
}

?>