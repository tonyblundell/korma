<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_get_one_raw_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_get_one_raw() {
        global $DB;
        $DB->delete_records('user');
        $john = $this->gen->create_user(array(
            'username'=>'john', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        $got = User::get_one_raw("base.username = 'paul'");
        $this->assertInstanceOf('User', $got);
        $this->assertEquals($paul->id, $got->id);
        $this->assertEquals($paul->username, $got->username);
        $this->assertEquals($paul->firstname, $got->firstname);
        $this->assertEquals($paul->lastname, $got->lastname);
    }

    public function test_get_one_raw_empty() {
        global $DB;
        $DB->delete_records('user');
        $john = $this->gen->create_user(array(
            'username'=>'john', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $got = User::get_one_raw();
        $this->assertInstanceOf('User', $got);
        $this->assertEquals($john->id, $got->id);
        $this->assertEquals($john->username, $got->username);
        $this->assertEquals($john->firstname, $got->firstname);
        $this->assertEquals($john->lastname, $got->lastname);
    }

    public function test_get_one_raw_field_types() {
        global $DB;
        $DB->delete_records('user');
        $john = $this->gen->create_user(array(
            'username'=>'john', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $got = User::get_one_raw();
        $this->assertInternalType('integer', $got->id);
        $this->assertInternalType('string', $got->username);
        $this->assertInternalType('string', $got->firstname);
        $this->assertInternalType('string', $got->lastname);
    }

    public function test_get_one_raw_multiple_matches() {
        global $DB;
        $DB->delete_records('user');
        $john = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $got = User::get_one_raw();
        $this->assertInstanceOf('User', $got);
        $this->assertEquals($john->id, $got->id);
        $this->assertEquals($john->firstname, $got->firstname);
        $this->assertEquals($john->lastname, $got->lastname);
    }

    public function test_get_one_raw_no_matches() {
        global $DB;
        $DB->delete_records('user');
        $got = User::get_one_raw();
        $this->assertEquals(false, $got);
    }

}
