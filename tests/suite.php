<?php
/**
 * File containing the eZPeExTestSuite class
 *
 * @copyright Copyright (C) 1999-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class eZPeExTestSuite extends ezpDatabaseTestSuite
{
    protected $sqlFiles = array( array( 'extension/ezmbpaex/sql/', 'mbpaex.sql' ) );

    public function __construct()
    {
        parent::__construct();
        $this->setName( "eZ Publish Password Expiry Test Suite" );

        $this->addTestSuite( 'eZPaExTest' );
        $this->addTestSuite( 'eZPaExTypeTest' );
    }

    public static function suite()
    {
        return new self();
    }
}

?>