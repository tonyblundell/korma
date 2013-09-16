<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(dirname(__FILE__)) . '/morm.php';


class User_R extends Model {
    protected static $table = 'user';
    protected static $fields = array(
        'id' => 'integer',
        'username' => 'string'
    );
    protected static $reverse_relations = array(
        array('course_completions', 'userid', 'CourseCompletion_R')
    );
}


class Course_R extends Model {
    protected static $table = 'course';
    protected static $fields = array(
        'id' => 'integer',
        'shortname' => 'string'
    );
    protected static $reverse_relations = array(
        array('course_completions', 'course', 'CourseCompletion_R')
    );
}


class CourseCompletion_R extends Model {
    protected static $table = 'course_completions';
    protected static $fields = array(
        'id' => 'integer'
    );
    protected static $relations = array(
        array('user', 'userid', 'User_R'),
        array('course', 'course', 'Course_R')
    );
}


class morm_relations_test extends advanced_testcase {

    protected function setUp() {
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    public function test_relations() {
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

    public function test_reverse_relations() {
        // Create users, courses and course completions
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $help = $this->gen->create_course(array('shortname'=>'Help!'));
        $yesterday = $this->gen->create_course(array('shortname'=>'Yesterday'));
        $girl = $this->gen->create_course(array('shortname'=>'Girl'));
        $john_help = new CourseCompletion_R(
            array('user'=>$john, 'course'=>$help)
        );
        $john_help->save();
        $john_girl = new CourseCompletion_R(
            array('user'=>$john, 'course'=>$girl)
        );
        $john_girl->save();
        $paul_help = new CourseCompletion_R(
            array('user'=>$paul, 'course'=>$help)
        );
        $paul_help->save();
        // Pull the objects back via morm
        $john_from_db = User_R::get_one(array('id__eq'=>$john->id));
        $paul_from_db = User_R::get_one(array('id__eq'=>$paul->id));
        $help_from_db = Course_r::get_one(array('id__eq'=>$help->id));
        $girl_from_db = Course_r::get_one(array('id__eq'=>$girl->id));
        $john_from_db_rel = $john_from_db->get_related('course_completions');
        $paul_from_db_rel = $paul_from_db->get_related('course_completions');
        $help_from_db_rel = $help_from_db->get_related('course_completions');
        $girl_from_db_rel = $girl_from_db->get_related('course_completions');
        // Assert that the relation attributes are set to the correct objects
        $john_comps = CourseCompletion_R::get(
            array('userid__eq'=>$john->id)
        );
        $paul_comps = CourseCompletion_R::get(
            array('userid__eq'=>$paul->id)
        );
        $help_comps = CourseCompletion_R::get(
            array('course__eq'=>$help->id)
        );
        $girl_comps = CourseCompletion_R::get(
            array('course__eq'=>$girl->id)
        );
        $this->assertEquals($john_comps, $john_from_db_rel);
        $this->assertEquals($paul_comps, $paul_from_db_rel);
        $this->assertEquals($help_comps, $help_from_db_rel);
        $this->assertEquals($girl_comps, $girl_from_db_rel);
    } 
    
}
