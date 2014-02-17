<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_delete_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_delete() {
        $john = $this->gen->create_user(array(
            'username'=>'john.lennon', 'firstname'=>'John', 'lastname'=>'Lennon')
        );
        $paul = $this->gen->create_user(array(
            'username'=>'paul.mccartney', 'firstname'=>'Paul', 'lastname'=>'McCartney')
        );
        User::delete();
        $after = User::get();
        $this->assertEquals(0, count($after));
    }

    public function test_delete_condition_equals() {
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'John'));
        User::delete(array('username__eq'=>'john'));
        $lower_from_db = User::get(array('username__eq'=>'john'));
        $upper_from_db = User::get(array('username__eq'=>'John'));
        $this->assertEquals(0, count($lower_from_db));
        $this->assertEquals(1, count($upper_from_db));
    }
    
    public function test_get_condition_iequals() {
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'John'));
        $paul_lower = $this->gen->create_user(array('username'=>'paul'));
        $paul_upper = $this->gen->create_user(array('username'=>'Paul'));
        User::delete(array('username__ieq'=>'john'));
        $john_lower_from_db = User::get(array('username__eq'=>'john'));
        $john_upper_from_db = User::get(array('username__eq'=>'John'));
        $paul_lower_from_db = User::get(array('username__eq'=>'paul'));
        $paul_upper_from_db = User::get(array('username__eq'=>'Paul'));
        $this->assertEquals(0, count($john_lower_from_db));
        $this->assertEquals(0, count($john_upper_from_db));
        $this->assertEquals(1, count($paul_lower_from_db));
        $this->assertEquals(1, count($paul_upper_from_db));
    }

    public function test_get_condition_greater_than() {
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $count_before = Course::count(array('id__gt'=>1));
        Course::delete(array('newsitems__gt'=>2));
        $count_after = Course::count(array('id__gt'=>1));
        $this->assertEquals(4, $count_before);
        $this->assertEquals(2, $count_after);
        $courses = Course::get(array('id__gt'=>1));
        foreach($courses as $course) {
            $this->assertLessThan(3, $course->newsitems);
        }
    }

    public function test_get_condition_greater_than_or_equal_to() {
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $count_before = Course::count(array('id__gt'=>1));
        Course::delete(array('newsitems__gte'=>2));
        $count_after = Course::count(array('id__gt'=>1));
        $this->assertEquals(4, $count_before);
        $this->assertEquals(1, $count_after);
        $courses = Course::get(array('id__gt'=>1));
        foreach($courses as $course) {
            $this->assertLessThan(2, $course->newsitems);
        }
    }

    public function test_get_condition_less_than() {
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $count_before = Course::count(array('id__gt'=>1));
        Course::delete(array('newsitems__lt'=>2));
        $count_after = Course::count(array('id__gt'=>1));
        $this->assertEquals(4, $count_before);
        $this->assertEquals(3, $count_after);
        $courses = Course::get(array('id__gt'=>1));
        foreach($courses as $course) {
            $this->assertGreaterThan(1, $course->newsitems);
        }
    }

    public function test_get_condition_less_than_or_equal_to() {
        $one = $this->gen->create_course(array('newsitems'=>1));
        $two = $this->gen->create_course(array('newsitems'=>2));
        $three = $this->gen->create_course(array('newsitems'=>3));
        $four = $this->gen->create_course(array('newsitems'=>4));
        $count_before = Course::count(array('id__gt'=>1));
        Course::delete(array('newsitems__lte'=>2));
        $count_after = Course::count(array('id__gt'=>1));
        $this->assertEquals(4, $count_before);
        $this->assertEquals(2, $count_after);
        $courses = Course::get(array('id__gt'=>1));
        foreach($courses as $course) {
            $this->assertGreaterThan(2, $course->newsitems);
        }
    }

    public function test_get_condition_startswith() {
        User::delete();
        $this->assertEquals(0, User::count());
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'John'));
        $paul_lower = $this->gen->create_user(array('username'=>'paul'));
        $paul_upper = $this->gen->create_user(array('username'=>'Paul'));
        $this->assertEquals(4, User::count());
        User::delete(array('username__startswith'=>'paul'));
        $this->assertEquals(3, User::count());
    }
    
    public function test_get_condition_istartswith() {
        User::delete();
        $this->assertEquals(0, User::count());
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'John'));
        $paul_lower = $this->gen->create_user(array('username'=>'paul'));
        $paul_upper = $this->gen->create_user(array('username'=>'Paul'));
        $this->assertEquals(4, User::count());
        User::delete(array('username__istartswith'=>'paul'));
        $this->assertEquals(2, User::count());
    }

    public function test_get_condition_endswith() {
        User::delete();
        $this->assertEquals(0, User::count());
        $john = $this->gen->create_user(array(
            'username'=>'john',
            'firstname' => 'John',
            'lastname'  =>'Lennon'
        ));
        $paul = $this->gen->create_user(array(
            'username'=>'paul',
            'firstname' => 'Paul',
            'lastname'  =>'McCartney'
        ));
        $ringo = $this->gen->create_user(array(
            'username'=>'ringo',
            'firstname' => 'Ringo',
            'lastname'  =>'Starr'
        ));
        $george = $this->gen->create_user(array(
            'username'=>'george',
            'firstname' => 'George',
            'lastname'  =>'Harrison'
        ));
        $john_upper = $this->gen->create_user(array(
            'username'=>'johnu',
            'firstname' => 'JOHN',
            'lastname'  =>'LENNON'
        ));
        $paul_upper = $this->gen->create_user(array(
            'username'=>'paulu',
            'firstname' => 'PAUL',
            'lastname'  =>'MCCARTNEY'
        ));
        $ringo_upper = $this->gen->create_user(array(
            'username'=>'ringou',
            'firstname' => 'RINGO',
            'lastname'  =>'STARR'
        ));
        $george_upper = $this->gen->create_user(array(
            'username'=>'georgeu',
            'firstname' => 'GEORGE',
            'lastname'  =>'HARRISON'
        ));
        $this->assertEquals(8, User::count());
        User::delete(array('lastname__endswith'=>'on'));
        $this->assertEquals(6, User::count());
    }

    public function test_get_condition_iendswith() {
        User::delete();
        $this->assertEquals(0, User::count());
        $john = $this->gen->create_user(array(
            'username'=>'john',
            'firstname' => 'John',
            'lastname'  =>'Lennon'
        ));
        $paul = $this->gen->create_user(array(
            'username'=>'paul',
            'firstname' => 'Paul',
            'lastname'  =>'McCartney'
        ));
        $ringo = $this->gen->create_user(array(
            'username'=>'ringo',
            'firstname' => 'Ringo',
            'lastname'  =>'Starr'
        ));
        $george = $this->gen->create_user(array(
            'username'=>'george',
            'firstname' => 'George',
            'lastname'  =>'Harrison'
        ));
        $john_upper = $this->gen->create_user(array(
            'username'=>'johnu',
            'firstname' => 'JOHN',
            'lastname'  =>'LENNON'
        ));
        $paul_upper = $this->gen->create_user(array(
            'username'=>'paulu',
            'firstname' => 'PAUL',
            'lastname'  =>'MCCARTNEY'
        ));
        $ringo_upper = $this->gen->create_user(array(
            'username'=>'ringou',
            'firstname' => 'RINGO',
            'lastname'  =>'STARR'
        ));
        $george_upper = $this->gen->create_user(array(
            'username'=>'georgeu',
            'firstname' => 'GEORGE',
            'lastname'  =>'HARRISON'
        ));
        $this->assertEquals(8, User::count());
        User::delete(array('lastname__iendswith'=>'on'));
        $this->assertEquals(4, User::count());
    }

    public function test_get_condition_contains() {
        User::delete();
        $this->assertEquals(0, User::count());
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'JOHN'));
        $paul_lower = $this->gen->create_user(array('username'=>'paul'));
        $paul_upper = $this->gen->create_user(array('username'=>'PAUL'));
        $this->assertEquals(4, User::count());
        User::delete(array('username__contains'=>'au'));
        $this->assertEquals(3, User::count());
    }

    public function test_get_condition_icontains() {
        User::delete();
        $this->assertEquals(0, User::count());
        $john_lower = $this->gen->create_user(array('username'=>'john'));
        $john_upper = $this->gen->create_user(array('username'=>'JOHN'));
        $paul_lower = $this->gen->create_user(array('username'=>'paul'));
        $paul_upper = $this->gen->create_user(array('username'=>'PAUL'));
        $this->assertEquals(4, User::count());
        User::delete(array('username__icontains'=>'au'));
        $this->assertEquals(2, User::count());
    }

    public function test_get_condition_in() {
        User::delete();
        $this->assertEquals(0, User::count());
        $beatles = array('john', 'paul', 'ringo', 'george');
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $mick = $this->gen->create_user(array('username'=>'mick'));
        $this->assertEquals(3, User::count());
        User::delete(array('username__in'=>$beatles));
        $this->assertEquals(1, User::count());
    }

    public function test_get_and() {
        User::delete();
        $this->assertEquals(0, User::count());
        $jack_jones = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Jones'));
        $john_jones = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Jones'));
        $jack_smith = $this->gen->create_user(array('firstname'=>'Jack', 'lastname'=>'Smith'));
        $john_smith = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $jack_smithe = $this->gen->create_user(array('firstname'=>'jack', 'lastname'=>'Smithe'));
        $john_smithe = $this->gen->create_user(array('firstname'=>'john', 'lastname'=>'Smithe'));
        $this->assertEquals(6, User::count());
        User::delete(array(
            'firstname__ieq' => 'john',
            'lastname__startswith' => 'Smi'
        ));
        $this->assertEquals(4, User::count());
    } 

    public function test_get_or() {
        User::delete();
        $this->assertEquals(0, User::count());
        $john = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $ringo = $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $george = $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $this->assertEquals(4, User::count());
        User::delete(array(
            array('firstname__eq' => 'John'),
            array('firstname__eq' => 'Ringo')
        ));
        $this->assertEquals(2, User::count());
    }
    
    public function test_get_multiple_or_and() {
        User::delete();
        $this->assertEquals(0, User::count());
        $johnl = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Lennon'));
        $johns = $this->gen->create_user(array('firstname'=>'John', 'lastname'=>'Smith'));
        $paul = $this->gen->create_user(array('firstname'=>'Paul', 'lastname'=>'McCartney'));
        $ringo = $this->gen->create_user(array('firstname'=>'Ringo', 'lastname'=>'Starr'));
        $george = $this->gen->create_user(array('firstname'=>'George', 'lastname'=>'Harrison'));
        $this->assertEquals(5, User::count());
        $users = User::delete(array(
            array('firstname__startswith'=>'Joh', 'lastname__endswith'=>'non'),
            array('firstname__startswith'=>'Pau', 'lastname__endswith'=>'ney'),
            array('firstname__startswith'=>'Rin', 'lastname__endswith'=>'arr')
        ));
        $this->assertEquals(2, User::count());
    }
}
