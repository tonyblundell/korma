<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(__FILE__)) . '/morm.php';


class User_S extends Model {
    protected static $table = 'user';
    protected static $fields = array(
        'id' => 'integer',
        'username' => 'string',
        'firstname' => 'string',
        'lastname' => 'string'
    );
}


class morm_save_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_save() {
        global $DB;
        $DB->delete_records('user');
        $ringo = new User_S(array(
            'username'=>'ringo', 'firstname'=>'Richard', 'lastname'=>'Starkey'
        ));
        $got = User_S::get_one();
        $this->assertEquals(false, $got);

        $ringo->save();
        $got = User_S::get_one();
        $this->assertEquals($ringo->id, $got->id);
        $this->assertEquals($ringo->username, $got->username);
        $this->assertEquals($ringo->firstname, $got->firstname);
        $this->assertEquals($ringo->lastname, $got->lastname);

        $ringo->firstname = 'Ringo';
        $ringo->lastname = 'Starr';
        $ringo->save();
        $got = User_S::get_one();
        $this->assertEquals($ringo->id, $got->id);
        $this->assertEquals($ringo->username, $got->username);
        $this->assertEquals($ringo->firstname, $got->firstname);
        $this->assertEquals($ringo->lastname, $got->lastname);
    }
    
}
