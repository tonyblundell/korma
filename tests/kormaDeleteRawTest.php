<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_delete_raw_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_delete_raw() {
        $before = User::get();
        $john = $this->gen->create_user(array(
            'username'=>'john.lennon', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul.mccartney', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        User::delete_raw("username = 'john.lennon'");
        $after = User::get();
        $this->assertEquals(count($before)+1, count($after));
        $this->assertEquals($paul->id, $after[$paul->id]->id);
        $this->assertEquals($paul->username, $after[$paul->id]->username);
        $this->assertEquals($paul->firstname, $after[$paul->id]->firstname);
        $this->assertEquals($paul->lastname, $after[$paul->id]->lastname);
    }

    public function test_delete_raw_empty() {
        $john = $this->gen->create_user(array(
            'username'=>'john.lennon', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul.mccartney', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        User::delete_raw();
        $after = User::get();
        $this->assertEquals(0, count($after));
    }
}
