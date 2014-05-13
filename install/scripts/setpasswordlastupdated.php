<?php
/**
 * File containing the setpasswordlastupdated CLI script
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package ezmbpaex
 */

ini_set( 'max_execution_time', 0 );
ini_set( 'memory_limit', '-1' );

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'debug-message' => '',
                                      'use-session' => true,
                                      'use-modules' => false,
                                      'use-extensions' => true ) );

$script->startup();

$endl = $cli->endlineString();
$webOutput = $cli->isWebOutput();

$debugOutput = false;
$allowedDebugLevels = false;
$useDebugAccumulators = false;
$useDebugTimingpoints = false;
$useIncludeFiles = false;
$showSQL = false;

function help()
{
    $argv = $_SERVER['argv'];
    $cli = eZCLI::instance();
    $cli->output( "Usage: " . $argv[0] . "\n" .
                  "Update password_last_updated field to current time in ALL existing users.\n\n" .
                  "General options:\n" .
                  "  -h,--help          display this help and exit \n" .
                  "  --sql              display sql queries\n" );
}

for ( $i = 1; $i < count( $argv ); ++$i )
{
    $arg = $argv[$i];
    if ( strlen( $arg ) > 0 and
         $arg[0] == '-' )
    {
        if ( strlen( $arg ) > 1 and
             $arg[1] == '-' )
        {
            $flag = substr( $arg, 2 );
            if ( $flag == 'debug' )
            {
                $debugOutput = true;
            }
            else if ( $flag == 'sql' )
            {
                $showSQL = true;
            }
            else
            {
                help();
                exit();
            }
        }
        else
        {
            $flag = substr( $arg, 1, 1 );
            if ( $flag == 'd' )
            {
                $debugOutput = true;
                if ( strlen( $arg ) > 2 )
                {
                    $levels = explode( ',', substr( $arg, 2 ) );
                    $allowedDebugLevels = array();
                    foreach ( $levels as $level )
                    {
                        if ( $level == 'all' )
                        {
                            $useDebugAccumulators = true;
                            $allowedDebugLevels = false;
                            $useDebugTimingpoints = true;
                            break;
                        }
                        if ( $level == 'accumulator' )
                        {
                            $useDebugAccumulators = true;
                            continue;
                        }
                        if ( $level == 'timing' )
                        {
                            $useDebugTimingpoints = true;
                            continue;
                        }
                        if ( $level == 'include' )
                        {
                            $useIncludeFiles = true;
                        }
                        if ( $level == 'error' )
                            $level = eZDebug::LEVEL_ERROR;
                        else if ( $level == 'warning' )
                            $level = eZDebug::LEVEL_WARNING;
                        else if ( $level == 'debug' )
                            $level = eZDebug::LEVEL_DEBUG;
                        else if ( $level == 'notice' )
                            $level = eZDebug::LEVEL_NOTICE;
                        else if ( $level == 'timing' )
                            $level = eZDebug::LEVEL_TIMING;
                        $allowedDebugLevels[] = $level;
                    }
                }
            }
            else
            {
                help();
                exit();
            }
        }
    }
    else
    {
        help();
        exit();
    }
}
$script->setUseDebugOutput( $debugOutput );
$script->setAllowedDebugLevels( $allowedDebugLevels );
$script->setUseDebugAccumulators( $useDebugAccumulators );
$script->setUseDebugTimingPoints( $useDebugTimingpoints );
$script->setUseIncludeFiles( $useIncludeFiles );

$script->setDebugMessage( "\n\n" . str_repeat( '#', 36 ) . $cli->style( 'emphasize' ) . " DEBUG " . $cli->style( 'emphasize-end' )  . str_repeat( '#', 36 ) . "\n" );

$script->initialize();
if ( !$script->isInitialized() )
{
    $cli->error( 'Error initializing script: ' . $script->initializationError() . '.' );
    $script->shutdown();
    exit();
}


$cli->output( "eZPaEx: Set password last updated to current time in all existing users" );

$db = eZDB::instance();
$db->setIsSQLOutputEnabled( $showSQL );
$db->begin();

$def = eZPaEx::definition();

$table = $def['name'];

$current_time = time();

$query = "UPDATE $table SET password_last_updated = $current_time";

$db->query( $query );
$db->commit();

// The transaction check
$transactionCounterCheck = eZDB::checkTransactionCounter();
if ( isset( $transactionCounterCheck['error'] ) )
    $cli->error( $transactionCounterCheck['error'] );

$cli->output( "eZPaEx: Done." );

$script->shutdown();
exit();

?>
