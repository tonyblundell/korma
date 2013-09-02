<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(__FILE__)) . '/morm.php';


class User_R extends Model {
    protected static $table = 'user';
    protected static $fields = array(
        'id' => 'integer',
        'username' => 'string'
    );
}


class Course_R extends Model {
    protected static $table = 'course';
    protected static $fields = array(
        'id' => 'integer',
        'shortname' => 'string'
    );
}


class CourseCompletion_R extends Model {
    protected static $table = 'course_completions';
    protected static $relations = array(
        array('user', 'userid', 'User_R'),
        array('course', 'course', 'Course_R')
    );
    protected static $fields = array(
        'id' => 'integer'
    );
}


class morm_relations_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_one_to_many_rels() {
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $help = $this->gen->create_course(array('shortname'=>'Help!'));
        $john_help = new CourseCompletion_R(
            array('user'=>$john, 'course'=>$help)
        );
        $john_help->save();
        $paul_help = new CourseCompletion_R(
            array('user'=>$paul, 'course'=>$help)
        );
        $paul_help->save();
        $john_from_db = User_R::get_one(array('id__eq'=>$john->id));
        $help_from_db = Course_R::get_one(array('id__eq'=>$help->id));
        $john_help_from_db = CourseCompletion_R::get_one(array(
            'userid__eq'=>$john->id, 'course__eq'=>$help->id
        ));
        $all_from_db = CourseCompletion_R::get();
        $this->assertEquals(2, count($all_from_db));
        $this->assertInstanceOf('CourseCompletion_R', $john_help_from_db);
        $this->assertEquals($john_from_db, $john_help_from_db->user);
        $this->assertEquals($help_from_db, $john_help_from_db->course);
    } 
    
}
