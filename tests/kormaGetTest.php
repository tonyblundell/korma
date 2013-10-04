<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(__FILE__)) . '/korma.php';


class User_G extends Model {
    protected static $table = 'user';
    protected static $fields = array(
        'id' => 'integer',
        'username' => 'string',
        'firstname' => 'string',
        'lastname' => 'string'
    );
}


class Course_G extends Model {
    protected static $table = 'course';
    protected static $fields = array(
        'id' => 'integer',
        'newsitems' => 'string'
    );
}


class korma_get_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_get() {
        $before = User_G::get();
        $john = $this->gen->create_user(array(
            'username'=>'john.lennon', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul.mccartney', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        $after = User_G::get();
        $this->assertEquals(count($before)+2, count($after));
        foreach(array($john, $paul) as $beatle) {
            $this->assertEquals($beatle->id, $after[$beatle->id]->id);
            $this->assertEquals($beatle->username, $after[$beatle->id]->username);
            $this->assertEquals($beatle->firstname, $after[$beatle->id]->firstname);
            $this->assertEquals($beatle->lastname, $after[$beatle->id]->lastname);
        }
        foreach($after as $user) {
            $this->assertInstanceOf('User_G', $user);
        }
    }

    public function test_get_field_types() {
        global $DB;
        $john = $this->gen->create_user(array(
            'username'=>'john', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        $got = User_G::get();
        foreach(array($john, $paul) as $beatle) {
            $this->assertInternalType('integer', $got[$beatle->id]->id);
            $this->assertInternalType('string', $got[$beatle->id]->username);
            $this->assertInternalType('string', $got[$beatle->id]->firstname);
            $this->assertInternalType('string', $got[$beatle->id]->lastname);
        }
    }

    public function test_get_condition_equals() {
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'John'));
        $users = User_G::get(array('username__eq'=>'john'));
        $this->assertEquals(1, count($users));
        $this->assertEquals($john_lower->username, $users[$john_lower->id]->username);
    }
    
    public function test_get_condition_iequals() {
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'John'));
        $users = User_G::get(array('username__ieq'=>'john'));
        $this->assertEquals(2, count($users));
        foreach(array($john_lower, $john_upper) as $john) {
            $this->assertEquals($john->username, $users[$john->id]->username);
        }
    }

    public function test_get_condition_greater_than() {
        $before = Course_G::get(array('newsitems__gt'=>2));
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $after = Course_G::get(array('newsitems__gt'=>2));
        $this->assertEquals(count($before)+2, count($after));
        foreach(array($three, $four) as $course) {
            $this->assertEquals($course->newsitems, $after[$course->id]->newsitems);
        }
    }

    public function test_get_condition_greater_than_or_equal_to() {
        $before = Course_G::get(array('newsitems__gte'=>2));
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $after = Course_G::get(array('newsitems__gte'=>2));
        $this->assertEquals(count($before)+3, count($after));
        foreach(array($two, $three, $four) as $course) {
            $this->assertEquals($course->newsitems, $after[$course->id]->newsitems);
        }
    }

    public function test_get_condition_less_than() {
        $before = Course_G::get(array('newsitems__lt'=>2));
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $after = Course_G::get(array('newsitems__lt'=>2));
        $this->assertEquals(count($before)+1, count($after));
        foreach(array($one) as $course) {
            $this->assertEquals($course->newsitems, $after[$course->id]->newsitems);
        }
    }

    public function test_get_condition_less_than_or_equal_to() {
        $before = Course_G::get(array('newsitems__lte'=>2));
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $after = Course_G::get(array('newsitems__lte'=>2));
        $this->assertEquals(count($before)+2, count($after));
        foreach(array($one, $two) as $course) {
            $this->assertEquals($course->newsitems, $after[$course->id]->newsitems);
        }
    }

    public function test_get_condition_startswith() {
        $paul_lower = $this->gen->create_user(array('username'=>'paul.mccartney'));
        $paul_upper = $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $users = User_G::get(array('username__startswith'=>'paul'));
        $this->assertEquals(1, count($users));
        $this->assertEquals($paul_lower->username, $users[$paul_lower->id]->username);
    }

    public function test_get_condition_istartswith() {
        $paul_lower = $this->gen->create_user(array('username'=>'paul.mccartney'));
        $paul_upper = $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $users = User_G::get(array('username__istartswith'=>'paul'));
        $this->assertEquals(2, count($users));
        foreach(array($paul_lower, $paul_upper) as $paul) {
            $this->assertEquals($paul->username, $users[$paul->id]->username);
        }
    }

   public function test_get_condition_endswith() {
        $paul_lower = $this->gen->create_user(array('username'=>'paul.mccartney'));
        $paul_upper = $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $users = User_G::get(array('username__endswith'=>'mccartney'));
        $this->assertEquals(1, count($users));
        $this->assertEquals($paul_lower->username, $users[$paul_lower->id]->username);
    }

    public function test_get_condition_iendswith() {
        $paul_lower = $this->gen->create_user(array('username'=>'paul.mccartney'));
        $paul_upper = $this->gen->create_user(array('username'=>'Paul.McCartney'));
        $users = User_G::get(array('username__iendswith'=>'mccartney'));
        $this->assertEquals(2, count($users));
        foreach(array($paul_lower, $paul_upper) as $paul) {
            $this->assertEquals($paul->username, $users[$paul->id]->username);
        }
    }

    public function test_get_condition_contains() {
        $john_lower = $this->gen->create_user(array('username'=>'john.winston.lennon'));
        $john_upper = $this->gen->create_user(array('username'=>'John.Winston.Lennon'));
        $users = User_G::get(array('username__contains'=>'winston'));
        $this->assertEquals(1, count($users));
        $this->assertEquals($john_lower->username, $users[$john_lower->id]->username);
    }

    public function test_get_condition_icontains() {
        $john_lower = $this->gen->create_user(array('username'=>'john.winston.lennon'));
        $john_upper = $this->gen->create_user(array('username'=>'John.Winston.Lennon'));
        $users = User_G::get(array('username__icontains'=>'winston'));
        $this->assertEquals(2, count($users));
        foreach(array($john_lower, $john_upper) as $john) {
            $this->assertEquals($john->username, $users[$john->id]->username);
        }
    }

    public function test_get_condition_in() {
        $beatles = array('john', 'paul', 'ringo', 'george');
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $mick = $this->gen->create_user(array('username'=>'mick'));
        $users = User_G::get(array('username__in'=>$beatles));
        $this->assertEquals(2, count($users));
        foreach(array($john, $paul) as $beatle) {
            $this->assertEquals($beatle->username, $users[$beatle->id]->username);
        }
    }

    public function test_get_and() {
        $jack_jones = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Jones'));
        $john_jones = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jones'));
        $jack_smith = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Smith'));
        $john_smith = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $jack_smithe = $this->gen->create_user(array('firstname'=>'jack', 'lastname'=>'Smithe'));
        $john_smithe = $this->gen->create_user(array('firstname'=>'john', 'lastname'=>'Smithe'));
        $users = User_G::get(array('firstname__ieq'=>'john', 'lastname__startswith'=>'Smi'));
        $this->assertEquals(2, count($users));
        foreach(array($john_smith, $john_smithe) as $user) {
            $this->assertEquals($user->firstname, $users[$user->id]->firstname);
            $this->assertEquals($user->lastname, $users[$user->id]->lastname);
        }
    } 

    public function test_get_or() {
        $john = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $ringo = $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $george = $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $users = User_G::get(
            array('firstname__eq'=>'John'),
            array('firstname__eq'=>'Ringo')
        );
        $this->assertEquals(2, count($users));
        foreach(array($john, $ringo) as $user) {
            $this->assertEquals($user->firstname, $users[$user->id]->firstname);
            $this->assertEquals($user->lastname, $users[$user->id]->lastname);
        }
    }
    
    public function test_get_multiple_or_and() {
        $johnl = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $johns = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $ringo = $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $george = $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $users = User_G::get(
            array('firstname__startswith'=>'Joh', 'lastname__endswith'=>'non'),
            array('firstname__startswith'=>'Pau', 'lastname__endswith'=>'ney'),
            array('firstname__startswith'=>'Rin', 'lastname__endswith'=>'arr')
        );
        $this->assertEquals(3, count($users));
        foreach(array($johnl, $paul, $ringo) as $user) {
            $this->assertEquals($user->firstname, $users[$user->id]->firstname);
            $this->assertEquals($user->lastname, $users[$user->id]->lastname);
        }
   }
    
}
