<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_get_one_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_get_one() {
        global $DB;
        $DB->delete_records('user');
        $john = $this->gen->create_user(array(
            'username'=>'john', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $got = User::get_one();
        $this->assertInstanceOf('User', $got);
        $this->assertEquals($john->id, $got->id);
        $this->assertEquals($john->username, $got->username);
        $this->assertEquals($john->firstname, $got->firstname);
        $this->assertEquals($john->lastname, $got->lastname);
    }

    public function test_get_one_field_types() {
        global $DB;
        $DB->delete_records('user');
        $john = $this->gen->create_user(array(
            'username'=>'john', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $got = User::get_one();
        $this->assertInternalType('integer', $got->id);
        $this->assertInternalType('string', $got->username);
        $this->assertInternalType('string', $got->firstname);
        $this->assertInternalType('string', $got->lastname);
    }

    public function test_get_one_multiple_matches() {
        global $DB;
        $DB->delete_records('user');
        $john = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $got = User::get_one();
        $this->assertInstanceOf('User', $got);
        $this->assertEquals($john->id, $got->id);
        $this->assertEquals($john->firstname, $got->firstname);
        $this->assertEquals($john->lastname, $got->lastname);
    }

    public function test_get_one_no_matches() {
        global $DB;
        $DB->delete_records('user');
        $got = User::get_one();
        $this->assertEquals(false, $got);
    }

    public function test_get_one_condition_equals() {
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $got = User::get_one(array('username__eq'=>'john'));
        $this->assertEquals($john_lower->id, $got->id);
        $this->assertEquals($john_lower->firstname, $got->firstname);
        $this->assertEquals($john_lower->lastname, $got->lastname);
    }
    
    public function test_get_one_condition_iequals() {
        $john_upper = $this->gen->create_user(array('username'=>'John'));
        $got = User::get_one(array('username__ieq'=>'john'));
        $this->assertEquals($john_upper->id, $got->id);
        $this->assertEquals($john_upper->firstname, $got->firstname);
        $this->assertEquals($john_upper->lastname, $got->lastname);
    }

    public function test_get_one_condition_greater_than() {
        $one = $this->gen->create_course(array('newsitems'=>101));
        $two = $this->gen->create_course(array('newsitems'=>102));
        $three = $this->gen->create_course(array('newsitems'=>103));
        $got = Course::get_one(array('newsitems__gt'=>102));
        $this->assertEquals($three->id, $got->id);
        $this->assertEquals($three->newsitems, $got->newsitems);
    }

    public function test_get_one_condition_greater_than_or_equal_to() {
        $one = $this->gen->create_course(array('newsitems'=>101));
        $two = $this->gen->create_course(array('newsitems'=>102));
        $three = $this->gen->create_course(array('newsitems'=>103));
        $got = Course::get_one(array('newsitems__gte'=>102));
        $this->assertEquals($two->id, $got->id);
        $this->assertEquals($two->newsitems, $got->newsitems);
    }

    public function test_get_one_condition_less_than() {
        global $DB;
        $DB->update_record('course', array('id'=>1, 'newsitems'=>999));
        $one = $this->gen->create_course(array('newsitems'=>101));
        $two = $this->gen->create_course(array('newsitems'=>102));
        $three = $this->gen->create_course(array('newsitems'=>103));
        $got = Course::get_one(array('newsitems__lt'=>102));
        $this->assertEquals($one->id, $got->id);
        $this->assertEquals($one->newsitems, $got->newsitems);
    }

    public function test_get_one_condition_less_than_or_equal_to() {
        global $DB;
        $DB->update_record('course', array('id'=>1, 'newsitems'=>999));
        $one = $this->gen->create_course(array('newsitems'=>101));
        $two = $this->gen->create_course(array('newsitems'=>102));
        $three = $this->gen->create_course(array('newsitems'=>103));
        $got = Course::get_one(array('newsitems__lte'=>102));
        $this->assertEquals($one->id, $got->id);
        $this->assertEquals($one->newsitems, $got->newsitems);
    }

    public function test_get_one_condition_startswith() {
        $paul_lower = $this->gen->create_user(array('username'=>'paul.mccartney'));
        $got = User::get_one(array('username__startswith'=>'paul'));
        $this->assertEquals($paul_lower->id, $got->id);
        $this->assertEquals($paul_lower->username, $got->username);
    }

    public function test_get_one_condition_istartswith() {
        $paul_upper = $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $got = User::get_one(array('username__istartswith'=>'paul'));
        $this->assertEquals($paul_upper->id, $got->id);
        $this->assertEquals($paul_upper->username, $got->username);
    }

   public function test_get_one_condition_endswith() {
        $paul_lower = $this->gen->create_user(array('username'=>'paul.mccartney'));
        $got = User::get_one(array('username__endswith'=>'mccartney'));
        $this->assertEquals($paul_lower->id, $got->id);
        $this->assertEquals($paul_lower->username, $got->username);
    }

    public function test_get_one_condition_iendswith() {
        $paul_upper = $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $got = User::get_one(array('username__iendswith'=>'mccartney'));
        $this->assertEquals($paul_upper->id, $got->id);
        $this->assertEquals($paul_upper->username, $got->username);
    }

    public function test_get_one_condition_contains() {
        $john_lower = $this->gen->create_user(array('username'=>'john.winston.lennon'));
        $got = User::get_one(array('username__contains'=>'winston'));
        $this->assertEquals($john_lower->id, $got->id);
        $this->assertEquals($john_lower->username, $got->username);
    }

    public function test_get_one_condition_icontains() {
        $john_upper = $this->gen->create_user(array('username'=>'John.Winston.Lennon'));
        $got = User::get_one(array('username__icontains'=>'winston'));
        $this->assertEquals($john_upper->id, $got->id);
        $this->assertEquals($john_upper->username, $got->username);
    }

    public function test_get_one_condition_in() {
        $beatles = array('john', 'paul', 'ringo', 'george');
        $mick = $this->gen->create_user(array('username'=>'mick'));
        $keith = $this->gen->create_user(array('username'=>'keith'));
        $john = $this->gen->create_user(array('username'=>'john'));
        $got = User::get_one(array('username__in'=>$beatles));
        $this->assertEquals($john->id, $got->id);
        $this->assertEquals($john->username, $got->username);
    }

    public function test_get_one_and() {
        $jack_jones = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Jones'));
        $john_jones = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jones'));
        $jack_smith = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Smith'));
        $john_smith = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $got = User::get_one(array('firstname__ieq'=>'john', 'lastname__startswith'=>'Smi'));
        $this->assertEquals($john_smith->id, $got->id);
        $this->assertEquals($john_smith->username, $got->username);
    } 

    public function test_get_one_or() {
        $john = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $ringo = $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $george = $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $got = User::get_one(array(
            array('firstname__eq'=>'Mick'),
            array('firstname__eq'=>'Ringo')
        ));
        $this->assertEquals($ringo->id, $got->id);
        $this->assertEquals($ringo->username, $got->username);
    }
    
    public function test_get_one_multiple_or_and() {
        $johnl = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $johns = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $ringo = $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $george = $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $got = User::get_one(array(
            array('firstname__startswith'=>'Mic', 'lastname__endswith'=>'ger'),
            array('firstname__startswith'=>'Kei', 'lastname__endswith'=>'rds'),
            array('firstname__startswith'=>'Rin', 'lastname__endswith'=>'arr')
        ));
        $this->assertEquals($ringo->id, $got->id);
        $this->assertEquals($ringo->username, $got->username);
   }
    
}
