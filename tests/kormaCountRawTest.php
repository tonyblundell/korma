<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_count_raw_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_count_raw() {
        $before = User::count_raw("firstname = 'John'");
        $john_smith = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $john_jones = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jones'));
        $john_jackson = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jackson'));
        $jack_johnson = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Johnson'));
        $after = User::count_raw("firstname = 'John'");
        $this->assertEquals($before+3, $after);
    }
    
    public function test_count_raw_empty() {
        $before = User::count_raw();
        $this->gen->create_user();
        $this->gen->create_user();
        $after = User::count_raw();
        $this->assertEquals($before+2, $after);
    }
}
