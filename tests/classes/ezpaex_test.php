<?php
/**
 * File containing the eZPaExTest class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @package tests
 */

class eZPaExTest extends ezpDatabaseTestCase
{
    protected $backupGlobals = false;

    /*
    // Commented out since dataProviders don't work if constructor is overloaded
    public function __construct( $name = null, $data = array(), $dataName = '' )
    {
        parent::__construct( $name, $data, $dataName );
        $this->setName( "eZ Password Expiry Unit Tests" );
    }*/

    /**
     * setUp():
     * - creates a PAEX object for the admin user
     **/
    public function setUp()
    {
        parent::setUp();

        $this->paexTime = time();

        $row = array( 'contentobject_id' => 14,
                      'passwordvalidationregexp' => '',
                      'passwordlifetime' => 3,
                      'expirationnotification' => eZPaEx::NOT_DEFINED,
                      'password_last_updated' => $this->paexTime,
                      'updatechildren' => 0,
                      'expirationnotification_sent' => 0 );
        $this->paex = new eZPaEx( $row );

        $this->paexINI = eZINI::instance( 'mbpaex.ini' );
    }

    /**
     * tearDown():
     * - deletes the admin PAEX object if it still exists
     **/
    public function tearDown()
    {
        parent::tearDown();
        
        // remove the PAEX object if it hasn't been removed by the test
        if ( eZPaEx::fetch( $this->paex->attribute( 'contentobject_id' ) ) )
            eZPaEx::removePaex( $this->paex->attribute( 'contentobject_id' ) );
    }

    /**
    * Unit test for eZPaEx::__construct()
    **/
    public function testeZPaEx()
    {
        $this->assertInstanceOf( 'eZPaEx', $this->paex );

        $this->assertEquals( 14,              $this->paex->attribute( 'contentobject_id' ) );
        $this->assertEquals( '',              $this->paex->attribute( 'passwordvalidationregexp' ) );
        $this->assertEquals( 3,               $this->paex->attribute( 'passwordlifetime' ) );
        $this->assertEquals( eZPaEx::NOT_DEFINED, $this->paex->attribute( 'expirationnotification' ) );
        $this->assertEquals( $this->paexTime, $this->paex->attribute( 'password_last_updated' ) );
        $this->assertEquals( 0,               $this->paex->attribute( 'updatechildren' ) );
        $this->assertEquals( 0,               $this->paex->attribute( 'expirationnotification_sent' ) );
    }

    /**
    * Unit test for eZPaEx::create()
    **/
    public function testCreate()
    {
        // Create a user instance
        $user = self::createUser( __FUNCTION__ );

        // Create a paex instanced based on this user's content object ID
        $paex = eZPaEx::create( $user->attribute( 'contentobject_id' ) );
        $this->assertInstanceOf( 'eZPaEx', $paex );
        $this->assertEquals( $this->paexINI->variable( 'mbpaexSettings', 'PasswordValidationRegexp' ),
            $paex->attribute( 'passwordvalidationregexp' ),
            "Password validation regexp value doesn't match" );
        $this->assertEquals( $this->paexINI->variable( 'mbpaexSettings', 'DefaultPasswordLifeTime' ),
            $paex->attribute( 'passwordlifetime' ),
            "Password lifetime value doesn't match" );
        $this->assertEquals( $this->paexINI->variable( 'mbpaexSettings', 'ExpirationNotification' ),
            $paex->attribute( 'expirationnotification' ),
            "Password expiration notification value doesn't match" );
        $this->assertEquals( $user->attribute( 'contentobject_id' ),
            $paex->attribute( 'contentobject_id' ),
            "Paex contentobject ID doesn't match" );
    }

    /**
    * Unit test for eZPaEx::setInformation()
    **/
    public function testSetInformation()
    {
        $user = self::createUser( __FUNCTION__ );

        $paex = new eZPaEx( array() );
        $paex->setInformation(
            $contentObjectID = $user->attribute( 'id' ),
            $regexp = '',
            $passwordLifetime = 10,
            $expirationNotification = eZPaEx::NOT_DEFINED,
            $time = time(),
            $updateChildren = 0,
            $expirationNotificationSent = 1 );

        $this->assertEquals( $contentObjectID, $paex->attribute( 'contentobject_id' ),
            "attribute contentobject_id doesn't match" );
        $this->assertEquals( $regexp, $paex->attribute( 'passwordvalidationregexp' ),
            "attribute passwordvalidationregexp doesn't match" );
        $this->assertEquals( $passwordLifetime, $paex->attribute( 'passwordlifetime' ),
            "attribute passwordlifetime doesn't match" );
        $this->assertEquals( $expirationNotification, $paex->attribute( 'expirationnotification' ),
            "attribute expirationnotification doesn't match" );
        $this->assertEquals( $time, $paex->attribute( 'password_last_updated' ),
            "attribute password_last_updated doesn't match" );
        $this->assertEquals( $updateChildren, $paex->attribute( 'updatechildren' ),
            "attribute updatechildren doesn't match" );
        $this->assertEquals( $expirationNotificationSent, $paex->attribute( 'expirationnotification_sent' ),
            "attribute expirationnotification_sent doesn't match" );
    }

    /**
    * Unit test for eZPaEx::store()
    **/
    public function testStore()
    {
        $user = self::createUser( __FUNCTION__ );
        $contentObjectID = $user->attribute( 'id' );
        $paex = eZPaEx::create( $contentObjectID );

        // First try fetching the object from the database
        // @expected null
        $this->assertNull( eZPaEx::fetch( $contentObjectID ),
            "PaEx #$contentObjectID hasn't been stored yet, fetch() should have returned null" );

        // store, and try to fetch again
        $paex->store();
        $fetchedPaex = eZPaEx::fetch( $contentObjectID );
        $this->assertInstanceOf( 'eZPaEx', $fetchedPaex,
            "PaEx #$contentObjectID has been stored, fetch() should have returned the object" );
    }

    /**
    * Unit test for eZPaEx::removePaex()
    **/
    public function testRemovePaex()
    {
        $user = self::createUser( __FUNCTION__ );
        $paex = eZPaEx::create( $user->attribute( 'id' ) );
        $paex->store();

        $id = $paex->attribute( 'contentobject_id' );
        unset( $paex );

        // fetch again to be sure it is stored
        $this->assertInstanceOf( 'eZPaEx', eZPaEx::fetch( $id ),
            "eZPaEx::fetch() should have returned an object" );

        // remove, and check if fetch fails as expected
        eZPaEx::removePaex( $id );
        $this->assertEquals( null, eZPaEx::fetch( $id ),
            "eZPaEx::fetch() should have returned null as the object should no longer exist" );
    }

    /**
    * Unit test for eZPaEx::fetch()
    **/
    public function testFetch()
    {
        // Create a user
        $user = self::createUser( __FUNCTION__ );
        $contentObjectID = $user->attribute( 'id' );

        // Try fetching this user's ID. Should fail.
        $this->assertNull( eZPaEx::fetch( $contentObjectID ),
            "eZPaEx::fetch() should be null as the object hasn't been created yet" );

        // Create & store the PaEx
        eZPaEx::create( $contentObjectID )->store();

        // Try fetching it again
        $this->assertInstanceOf( 'eZPaEx', eZPaEx::fetch( $contentObjectID ) );
    }

    /**
     * Unit test for eZPaEx::isExpired
     *
     * @param int $passwordLifetime
     * @param int $passwordLastUpdated
     * @param bool $expected
     *
     * @dataProvider providerForTestIsExpired
     **/
    public function testIsExpired( $passwordLifetime, $passwordLastUpdated, $expected )
    {
        $this->paex->setAttribute( 'passwordlifetime', $passwordLifetime );
        $this->paex->setAttribute( 'password_last_updated', $passwordLastUpdated );

        $this->assertEquals( $expected, $this->paex->isExpired() );
    }
    
    /**
     * Unit test for eZPaEx::isUser()
     */
    public function testIsUser()
    {
        $this->assertTrue( $this->paex->isUser(), "PaEx object should have been a user" );
        
        $paex = new eZPaEx( array() );
        $this->assertFalse( $paex->isUser(), "PaEx object shouldn't have been a user" );
    }
    
    /**
     * Unit test for eZPaEx::hasRegexp
     */
    function testHasRegexp()
    {
        // No regexp on the default object
        $this->assertFalse( $this->paex->hasRegexp(), "PaEx object shouldn't have a regexp" );
        
        // Set a regexp and test again
        $this->paex->setAttribute( 'passwordvalidationregexp', '/^[a-z0-9]$/' );
        $this->assertTrue( $this->paex->hasRegexp(), "PaEx object should have a regexp" );
    }
    
    /**
     * Unit test for eZPaEx::hasLifeTime()
     */
    function testHasLifeTime()
    {
        $this->assertTrue( $this->paex->hasLifeTime(), "PaEx object should have a lifetime" );
        
        // Set lifetime to an empty value
        $this->paex->setAttribute( 'passwordlifetime', '' );
        $this->assertFalse( $this->paex->hasLifeTime(), "PaEx object shouldn't have a lifetime (empty string)" );
        
        // Set lifetime to the NOT_DEFINED constant
        $this->paex->setAttribute( 'passwordlifetime', eZPaEx::NOT_DEFINED );
        $this->assertFalse( $this->paex->hasLifeTime(), "PaEx object shouldn't have a lifetime (eZPaEx::NOT_DEFINED)" );
    }
    
    /**
     * Unit test for eZPaEx::hasNotification()
     */
    function testHasNotification()
    {
        // default object has expirationnotification set to eZPaEx::NOT_DEFINED
        $this->assertFalse( $this->paex->hasNotification(), "PaEx object should not have a lifetime" );
        
        // check with null
        $this->paex->setAttribute( 'expirationnotification', null );
        $this->assertFalse( $this->paex->hasNotification(), "PaEx object should not have a lifetime" );
        
        // check with empty string
        $this->paex->setAttribute( 'expirationnotification', '' );
        $this->assertFalse( $this->paex->hasNotification(), "PaEx object should not have a lifetime" );
        
        // check with an actual value
        $this->paex->setAttribute( 'expirationnotification', 172800 );
        $this->assertTrue( $this->paex->hasNotification(), "PaEx object should have a lifetime" );
    }

    /**
     * Unit test for eZPaEx::validatePassword()
     * 
     *  @dataProvider providerForTestValidatePassword
     */
    function testValidatePassword( $regexp, $expected, $password = 'publish' )
    {
        // The default object has an empty regexp, will always be valid
        $this->paex->setAttribute( 'passwordvalidationregexp', $regexp );
        if ( $expected === true )
        {
            $this->assertTrue( $this->paex->validatePassword( $password ) );
        }
        elseif ( $expected === false )
        {
            $this->assertFalse( $this->paex->validatePassword( $password ) );
        }
        else
        {
            $this->markTestSkipped( "Unknown value for \$expected" );
        }
    }
    
    /**
     * Data provider for testValidatePassword()
     * 
     * @see eZPaExTest::testValidatePassword()
     */
    public static function providerForTestValidatePassword()
    {
        return array(
            // empty values
            array( '', true ),
            array( null, true ),
            array( eZPaEx::NOT_DEFINED, true ),
            
            // test different regexp forms
            array( '^[0-9a-z]+$', true ),
            array( '/^[0-9a-z]+$/', true),
            
            // a few rejected passwords
            array( '^[0-9a-z]+$', false, 'my_password' ),
            array( '^[0-9]+$', false, '#@~&' )
        );
    }
    
    /**
     * dataProvider for testIsExpired()
     **/
    public static function providerForTestIsExpired()
    {
        return array(
            // undefined password lifetime
            array( eZPaEx::NOT_DEFINED, time(), false ),
            array( -1, time(), false ),
            // defined password lifetime, never updated
            array( 15, 0, true ),
            // defined password lifetime, updated less than lifetime days ago
            array( 15, strtotime( 'now - 3 days' ), false ),
            // defined password lifetime, updated more than lifetime days ago
            array( 15, strtotime( 'now - 20 days' ), true ),
        );
    }
    
    /**
     * Helper method that creates a new user.
     * Currently only creates in users/guest_accounts.
     * First and last name will be a splitup of username
     *
     * @param string $username
     * @param string $password If not provided, uses the username as password
     * @param string $email If not provided, uses '<username><at>test.ez.no'
     *
     * @return eZContentObject
     */
    protected static function createUser( $username, $password = false, $email = false )
    {
        $firstname = substr( $username, 0, floor( strlen( $username ) / 2 ) );
        $lastname  = substr( $username, ceil( strlen( $username ) / 2 ) );

        if ( $email === false )
            $email = "$username@test.ez.no";
        if ( $password === false )
            $password = $username;

        $user = new ezpObject( 'user', eZContentObjectTreeNode::fetchByPath( 'users/guest_accounts' ) );
        $user->first_name = $firstname;
        $user->last_name = $lastname;
        $user->user_account = $account = sprintf( '%s|%s|%s|%d',
            $username, $email,
            eZUser::createHash( $username, $password, eZUser::site(), eZUser::PASSWORD_HASH_MD5_USER ),
            eZUser::PASSWORD_HASH_MD5_USER );
        $user->publish();
        $user->refresh();

        return $user->object;
    }

    /**
    * PaEx creation time
    * @var int
    **/
    protected $paexTime;

    /**
    * PaEx test instance
    * @var eZPaEx
    **/
    protected $paex;

    /**
    * @var eZINI
    **/
    protected $paexINI;
}

?>