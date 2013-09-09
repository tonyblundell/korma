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
    protected static $fields = array(
        'id' => 'integer'
    );
    protected static $relations_single = array(
        array('user', 'userid', 'User_R'),
        array('course', 'course', 'Course_R')
    );
}


class morm_relations_single_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_one_to_many_rels() {
        // Create users, a course and course completion
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $help = $this->gen->create_course(array('shortname'=>'Help!'));
        $comp = new CourseCompletion_R(
            array('user'=>$john, 'course'=>$help)
        );
        $comp->save();
        // Pull the objects back via morm
        $john_from_db = User_R::get_one(array('id__eq'=>$john->id));
        $paul_from_db = User_R::get_one(array('id__eq'=>$paul->id));
        $help_from_db = Course_R::get_one(array('id__eq'=>$help->id));
        $comp_from_db = CourseCompletion_R::get_one(array(
            'userid__eq'=>$john->id, 'course__eq'=>$help->id
        ));
        // Assert that the relation attributes are set to the correct objects
        $this->assertInstanceOf('CourseCompletion_R', $comp_from_db);
        $this->assertEquals($john_from_db, $comp_from_db->user);
        $this->assertEquals($help_from_db, $comp_from_db->course);
        // Change the relations, re-pull, assert they are set to the new objects
        $comp->user = $paul;
        $comp->save();
        $comp_from_db = CourseCompletion_R::get_One(array(
            'id__eq'=>$comp->id
        ));
        $this->assertEquals($paul_from_db, $comp_from_db->user);
    } 
    
}
