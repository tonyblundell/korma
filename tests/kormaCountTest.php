<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_count_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_count() {
        $before = User::count();
        $this->gen->create_user();
        $this->gen->create_user();
        $after = User::count();
        $this->assertEquals($before+2, $after);
    }

    public function test_count_condition_equals() {
        $this->gen->create_user(array('username'=>'john'));
        $this->gen->create_user(array('username'=>'John'));
        $num_users = User::count(array('username__eq'=>'john'));
        $this->assertEquals(1, $num_users);
    }
    
    public function test_count_condition_iequals() {
        $this->gen->create_user(array('username'=>'john'));
        $this->gen->create_user(array('username'=>'John'));
        $num_users = User::count(array('username__ieq'=>'john'));
        $this->assertEquals(2, $num_users);
    }

    public function test_count_condition_greater_than() {
        $before = Course::count(array('newsitems__gt'=>2));
        $this->gen->create_course(array('newsitems'=>1));
        $this->gen->create_course(array('newsitems'=>2));
        $this->gen->create_course(array('newsitems'=>3));
        $this->gen->create_course(array('newsitems'=>4));
        $after = Course::count(array('newsitems__gt'=>2));
        $this->assertEquals($before+2, $after);
    }

    public function test_count_condition_greater_than_or_equal_to() {
        $before = Course::count(array('newsitems__gte'=>2));
        $this->gen->create_course(array('newsitems'=>1));
        $this->gen->create_course(array('newsitems'=>2));
        $this->gen->create_course(array('newsitems'=>3));
        $this->gen->create_course(array('newsitems'=>4));
        $after = Course::count(array('newsitems__gte'=>2));
        $this->assertEquals($before+3, $after);
    }

   public function test_count_condition_less_than() {
        $before = Course::count(array('newsitems__lt'=>2));
        $this->gen->create_course(array('newsitems'=>1));
        $this->gen->create_course(array('newsitems'=>2));
        $this->gen->create_course(array('newsitems'=>3));
        $this->gen->create_course(array('newsitems'=>4));
        $after = Course::count(array('newsitems__lt'=>2));
        $this->assertEquals($before+1, $after);
    }

    public function test_count_condition_less_than_or_equal_to() {
        $before = Course::count(array('newsitems__lte'=>2)); 
        $this->gen->create_course(array('newsitems'=>1)); 
        $this->gen->create_course(array('newsitems'=>2));
        $this->gen->create_course(array('newsitems'=>3)); 
        $this->gen->create_course(array('newsitems'=>4));
        $after = Course::count(array('newsitems__lte'=>2));
        $this->assertEquals($before+2, $after);
    }

   public function test_count_condition_startswith() {
        $this->gen->create_user(array('username'=>'paul.mccartney'));
        $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $num_users = User::count(array('username__startswith'=>'paul'));
        $this->assertEquals(1, $num_users);
    }

    public function test_count_condition_istartswith() {
        $this->gen->create_user(array('username'=>'paul.mccartney'));
        $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $num_users = User::count(array('username__istartswith'=>'paul'));
        $this->assertEquals(2, $num_users);
    }

    public function test_count_condition_endswith() {
        $this->gen->create_user(array('username'=>'paul.mccartney'));
        $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $num_users = User::count(array('username__endswith'=>'mccartney'));
        $this->assertEquals(1, $num_users);
    }

    public function test_count_condition_iendswith() {
        $this->gen->create_user(array('username'=>'paul.mccartney'));
        $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $num_users = User::count(array('username__iendswith'=>'mccartney'));
        $this->assertEquals(2, $num_users);
    }

    public function test_count_condition_contains() {
        $this->gen->create_user(array('username'=>'john.winston.lennon'));
        $this->gen->create_user(array('username'=>'John.Winston.Lennon'));
        $num_users = User::count(array('username__contains'=>'winston'));
        $this->assertEquals(1, $num_users);
    }

    public function test_count_condition_icontains() {
        $this->gen->create_user(array('username'=>'john.winston.lennon'));
        $this->gen->create_user(array('username'=>'John.Winston.Lennon'));
        $num_users = User::count(array('username__icontains'=>'winston'));
        $this->assertEquals(2, $num_users);
    }

    public function test_count_condition_in() {
        $beatles = array('john', 'paul', 'ringo', 'george');
        $this->gen->create_user(array('username'=>'john'));
        $this->gen->create_user(array('username'=>'paul'));
        $this->gen->create_user(array('username'=>'mick'));
        $num_users = User::count(array('username__in'=>$beatles));
        $this->assertEquals(2, $num_users);
    }

    public function test_count_and() {
        $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Jones'));
        $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jones'));
        $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Smith'));
        $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $this->gen->create_user(array('firstname'=>'jack', 'lastname'=>'Smithe'));
        $this->gen->create_user(array('firstname'=>'john', 'lastname'=>'Smithe'));
        $num_users = User::count( array('firstname__ieq'=>'john', 'lastname__startswith'=>'Smi'));
        $this->assertEquals(2, $num_users);
    } 

    public function test_count_or() {
        $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $num_users = User::count(array(
            array('firstname__eq'=>'John'),
            array('firstname__eq'=>'Ringo')
        ));
        $this->assertEquals(2, $num_users);
    }
    
    public function test_count_multiple_or_and() {
        $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $num_users = User::count(array(
            array('firstname__startswith'=>'Joh', 'lastname__endswith'=>'non'),
            array('firstname__startswith'=>'Pau', 'lastname__endswith'=>'ney'),
            array('firstname__startswith'=>'Rin', 'lastname__endswith'=>'arr')
        ));
        $this->assertEquals(3, $num_users);
    }
    
}
