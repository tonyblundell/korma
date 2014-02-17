<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_raw_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_get_raw() {
        global $DB;
        $DB->delete_records('user');
        $john_smith = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $john_jones = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jones'));
        $john_jackson = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jackson'));
        $jack_johnson = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Johnson'));
        $from_db = User::get_raw("base.firstname = 'John'");
        $this->assertEquals(count($from_db), 3);
        foreach(array($john_smith, $john_jones, $john_jackson) as $john) {
            $this->assertEquals($john->id, $from_db[$john->id]->id);
            $this->assertEquals($john->firstname, $from_db[$john->id]->firstname);
            $this->assertEquals($john->lastname, $from_db[$john->id]->lastname);
        }
        foreach($from_db as $john) {
            $this->assertInstanceOf('User', $john);
        }
    }

    public function test_get_raw_empty() {
        $before = User::get_raw();
        $john = $this->gen->create_user(array(
            'username'=>'john.lennon', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul.mccartney', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        $after = User::get_raw();
        $this->assertEquals(count($before)+2, count($after));
        foreach(array($john, $paul) as $beatle) {
            $this->assertEquals($beatle->id, $after[$beatle->id]->id);
            $this->assertEquals($beatle->username, $after[$beatle->id]->username);
            $this->assertEquals($beatle->firstname, $after[$beatle->id]->firstname);
            $this->assertEquals($beatle->lastname, $after[$beatle->id]->lastname);
        }
        foreach($after as $user) {
            $this->assertInstanceOf('User', $user);
        }
    }

    public function test_get_raw_order() {
        global $DB;
        $DB->delete_records('user');
        $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $john = User::get_one(array('firstname'=>'John'));
        $paul = User::get_one(array('firstname'=>'Paul'));
        $ringo = User::get_one(array('firstname'=>'Ringo'));
        $george = User::get_one(array('firstname'=>'George'));
        $this->assertEquals(array(
                $george->id => $george, 
                $john->id => $john, 
                $paul->id => $paul, 
                $ringo->id => $ringo
        ), User::get_raw('', 'firstname'));
        $this->assertEquals(array(
                $ringo->id => $ringo,
                $paul->id => $paul, 
                $john->id => $john, 
                $george->id => $george
        ), User::get_raw('', '-firstname'));
    } 

    public function test_get_raw_limit_and_offset() {
        global $DB;
        $this->gen->create_user(array('username'=>'0'));
        $this->gen->create_user(array('username'=>'1'));
        $this->gen->create_user(array('username'=>'2'));
        $this->gen->create_user(array('username'=>'3'));
        $this->gen->create_user(array('username'=>'4'));
        $this->gen->create_user(array('username'=>'5'));
        $this->gen->create_user(array('username'=>'6'));
        $this->gen->create_user(array('username'=>'7'));
        $this->gen->create_user(array('username'=>'8'));
        $this->gen->create_user(array('username'=>'9'));
        $_0 = User::get_one(array('username__eq'=>'0'));
        $_1 = User::get_one(array('username__eq'=>'1'));
        $_2 = User::get_one(array('username__eq'=>'2'));
        $_3 = User::get_one(array('username__eq'=>'3'));
        $_4 = User::get_one(array('username__eq'=>'4'));
        $_5 = User::get_one(array('username__eq'=>'5'));
        $_6 = User::get_one(array('username__eq'=>'6'));
        $_7 = User::get_one(array('username__eq'=>'7'));
        $_8 = User::get_one(array('username__eq'=>'8'));
        $_9 = User::get_one(array('username__eq'=>'9'));
        $users = User::get_raw('', 'username', 3, 2);
        $this->assertEquals(array(
            $_2->id => $_2,
            $_3->id => $_3,
            $_4->id => $_4
        ), User::get_raw('', 'username', 3, 2));
    }

    public function test_get_raw_field_types() {
        global $DB;
        $john = $this->gen->create_user(array(
            'username'=>'john', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        $got = User::get_raw();
        foreach(array($john, $paul) as $beatle) {
            $this->assertInternalType('integer', $got[$beatle->id]->id);
            $this->assertInternalType('string', $got[$beatle->id]->username);
            $this->assertInternalType('string', $got[$beatle->id]->firstname);
            $this->assertInternalType('string', $got[$beatle->id]->lastname);
        }
    }

}
