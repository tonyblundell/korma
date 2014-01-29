<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_save_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_save() {
        global $DB;
        $DB->delete_records('user');
        $ringo = new User(array(
            'username'=>'ringo', 'firstname'=>'Richard', 'lastname'=>'Starkey'
        ));
        $this->assertEquals(0, User::count());
        $ringo->save();
        $got = User::get_one();
        $this->assertEquals($ringo->id, $got->id);
        $this->assertEquals($ringo->username, $got->username);
        $this->assertEquals($ringo->firstname, $got->firstname);
        $this->assertEquals($ringo->lastname, $got->lastname);
        $ringo->firstname = 'Ringo';
        $ringo->lastname = 'Starr';
        $ringo->save();
        $got = User::get_one();
        $this->assertEquals($ringo->id, $got->id);
        $this->assertEquals($ringo->username, $got->username);
        $this->assertEquals($ringo->firstname, $got->firstname);
        $this->assertEquals($ringo->lastname, $got->lastname);
    }
    
}
