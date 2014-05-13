<?php
/**
 * File containing the eZPeExTestSuite class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
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