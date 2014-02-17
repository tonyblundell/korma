<?php

defined('MOODLE_INTERNAL') || die();

require_once dirname(__FILE__) . '/models.php';

class korma_relations_test extends advanced_testcase {

    protected function setUp() {
        // We temporarily make course_completions.userid NULLABLE for this test,
        // so we can test the removing of related objects.
        global $DB;
        $DB->execute('ALTER TABLE {course_completions} ALTER COLUMN userid DROP NOT NULL');
        $this->resetAfterTest();
        $this->gen = $this->getDataGenerator();
    }

    protected function tearDown() {
        global $DB;
        $DB->delete_records_select('course_completions', 'userid IS NULL');
        $DB->execute('ALTER TABLE {course_completions} ALTER COLUMN userid SET NOT NULL');
    }

    public function test_many_to_one_relations() {
        // Create users, a course and course completion
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $help = $this->gen->create_course(array('shortname'=>'Help!'));
        $comp = new CourseCompletion(
            array('user'=>$john, 'course'=>$help)
        );
        $comp->save();
        // Pull the objects back via korma
        $john_from_db = User::get_one(array('id__eq'=>$john->id));
        $paul_from_db = User::get_one(array('id__eq'=>$paul->id));
        $help_from_db = Course::get_one(array('id__eq'=>$help->id));
        $comp_from_db = CourseCompletion::get_one(array(
            'userid__eq'=>$john->id, 'course__eq'=>$help->id
        ));
        // Assert that the relation attributes are set to the correct objects
        $this->assertInstanceOf('CourseCompletion', $comp_from_db);
        $this->assertEquals($john_from_db, $comp_from_db->user);
        $this->assertEquals($help_from_db, $comp_from_db->course);
        // Change the relations, re-pull, assert they are set to the new objects
        $comp->user = $paul;
        $comp->save();
        $comp_from_db = CourseCompletion::get_one(array(
            'id__eq'=>$comp->id
        ));
        $this->assertEquals($paul_from_db, $comp_from_db->user);
    } 

    public function test_select_across_tables() {
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $help = $this->gen->create_course(array('shortname'=>'Help!'));
        $girl = $this->gen->create_course(array('shortname'=>'Girl'));
        $john_help = new CourseCompletion(
            array('user'=>$john, 'course'=>$help)
        );
        $john_help->save();
        $john_girl = new CourseCompletion(
            array('user'=>$john, 'course'=>$girl)
        );
        $john_girl->save();
        $paul_help = new CourseCompletion(
            array('user'=>$paul, 'course'=>$help)
        );
        $paul_help->save();
        $from_db_implicit_clause = CourseCompletion::get(array(
            'user__username' => 'john'
        ));
        $from_db_explicit_clause = CourseCompletion::get(array(
            'user__username__eq' => 'john'
        ));
        $this->assertEquals($from_db_implicit_clause, $from_db_explicit_clause);
        $this->assertEquals(2, count($from_db_implicit_clause));
        $this->assertEquals($john_help, $from_db_implicit_clause[$john_help->id]);
        $this->assertEquals($john_girl, $from_db_implicit_clause[$john_girl->id]);
    }

    public function test_get_related() {
        // Create users, courses and course completions
        $john = $this->gen->create_user(array('username'=>'john'));
        $paul = $this->gen->create_user(array('username'=>'paul'));
        $help = $this->gen->create_course(array('shortname'=>'Help!'));
        $yesterday = $this->gen->create_course(array('shortname'=>'Yesterday'));
        $girl = $this->gen->create_course(array('shortname'=>'Girl'));
        $john_help = new CourseCompletion(
            array('user'=>$john, 'course'=>$help)
        );
        $john_help->save();
        $john_girl = new CourseCompletion(
            array('user'=>$john, 'course'=>$girl)
        );
        $john_girl->save();
        $paul_help = new CourseCompletion(
            array('user'=>$paul, 'course'=>$help)
        );
        $paul_help->save();
        // Pull the objects back via korma
        $john_from_db = User::get_one(array('id__eq'=>$john->id));
        $paul_from_db = User::get_one(array('id__eq'=>$paul->id));
        $help_from_db = Course::get_one(array('id__eq'=>$help->id));
        $girl_from_db = Course::get_one(array('id__eq'=>$girl->id));
        $john_from_db_rel = $john_from_db->get_related('course_completions');
        $paul_from_db_rel = $paul_from_db->get_related('course_completions');
        $help_from_db_rel = $help_from_db->get_related('course_completions');
        $girl_from_db_rel = $girl_from_db->get_related('course_completions');
        // Assert that the relation attributes are set to the correct objects
        $john_comps = CourseCompletion::get(
            array('userid__eq'=>$john->id)
        );
        $paul_comps = CourseCompletion::get(
            array('userid__eq'=>$paul->id)
        );
        $help_comps = CourseCompletion::get(
            array('course__eq'=>$help->id)
        );
        $girl_comps = CourseCompletion::get(
            array('course__eq'=>$girl->id)
        );
        $this->assertEquals($john_comps, $john_from_db_rel);
        $this->assertEquals($paul_comps, $paul_from_db_rel);
        $this->assertEquals($help_comps, $help_from_db_rel);
        $this->assertEquals($girl_comps, $girl_from_db_rel);
    } 

    public function test_set_related_ids() {
        $john = new User(array('username'=>'john'));
        $john->save();
        $paul = new User(array('username'=>'paul'));
        $paul->save();
        $help = new Course(array('shortname'=>'Help'));
        $help->save();
        $yesterday = new Course(array('shortname'=>'Yesterday'));
        $yesterday->save();
        $girl = new Course(array('shortname'=>'Girl'));
        $girl->save();
        $comp_help = new CourseCompletion(array('user'=>$paul, 'course'=>$help));
        $comp_help->save();
        $comp_yesterday = new CourseCompletion(array('user'=>$paul, 'course'=>$yesterday));
        $comp_yesterday->save();
        $comp_girl = new CourseCompletion(array('user'=>$john, 'course'=>$girl));
        $comp_girl->save();
        $john->set_related('course_completions', array($comp_help->id, $comp_yesterday->id));
        $comp_help->refresh();
        $comp_yesterday->refresh();
        $this->assertEquals(array(
            $comp_help->id => $comp_help,
            $comp_yesterday->id => $comp_yesterday
        ), $john->get_related('course_completions'));
    }
    
    public function test_set_related_objects() {
        $john = new User(array('username'=>'john'));
        $john->save();
        $paul = new User(array('username'=>'paul'));
        $paul->save();
        $help = new Course(array('shortname'=>'Help'));
        $help->save();
        $yesterday = new Course(array('shortname'=>'Yesterday'));
        $yesterday->save();
        $girl = new Course(array('shortname'=>'Girl'));
        $girl->save();
        $comp_help = new CourseCompletion(array('user'=>$paul, 'course'=>$help));
        $comp_help->save();
        $comp_yesterday = new CourseCompletion(array('user'=>$paul, 'course'=>$yesterday));
        $comp_yesterday->save();
        $comp_girl = new CourseCompletion(array('user'=>$john, 'course'=>$girl));
        $comp_girl->save();
        $john->set_related('course_completions', array($comp_help, $comp_yesterday));
        $comp_help->refresh();
        $comp_yesterday->refresh();
        $this->assertEquals(array(
            $comp_help->id => $comp_help,
            $comp_yesterday->id => $comp_yesterday
        ), $john->get_related('course_completions'));
    }

    public function test_add_related_ids() {
        $john = new User(array('username'=>'john'));
        $john->save();
        $paul = new User(array('username'=>'paul'));
        $paul->save();
        $help = new Course(array('shortname'=>'Help'));
        $help->save();
        $yesterday = new Course(array('shortname'=>'Yesterday'));
        $yesterday->save();
        $girl = new Course(array('shortname'=>'Girl'));
        $girl->save();
        $comp_help = new CourseCompletion(array('user'=>$john, 'course'=>$help));
        $comp_help->save();
        $comp_yesterday = new CourseCompletion(array('user'=>$paul, 'course'=>$yesterday));
        $comp_yesterday->save();
        $comp_girl = new CourseCompletion(array('user'=>$paul, 'course'=>$girl));
        $comp_girl->save();
        $this->assertEquals(array(
            $comp_help->id => $comp_help
        ), $john->get_related('course_completions'));
        $john->add_related('course_completions', array(
            $comp_yesterday->id,
            $comp_girl->id
        ));
        $comp_yesterday->refresh();
        $comp_girl->refresh();
        $this->assertEquals(array(
            $comp_help->id => $comp_help,
            $comp_yesterday->id => $comp_yesterday,
            $comp_girl->id =>$comp_girl
        ), $john->get_related('course_completions'));
    }
    
    public function test_add_related_objects() {
        $john = new User(array('username'=>'john'));
        $john->save();
        $paul = new User(array('username'=>'paul'));
        $paul->save();
        $help = new Course(array('shortname'=>'Help'));
        $help->save();
        $yesterday = new Course(array('shortname'=>'Yesterday'));
        $yesterday->save();
        $girl = new Course(array('shortname'=>'Girl'));
        $girl->save();
        $comp_help = new CourseCompletion(array('user'=>$john, 'course'=>$help));
        $comp_help->save();
        $comp_yesterday = new CourseCompletion(array('user'=>$paul, 'course'=>$yesterday));
        $comp_yesterday->save();
        $comp_girl = new CourseCompletion(array('user'=>$paul, 'course'=>$girl));
        $comp_girl->save();
        $this->assertEquals(array(
            $comp_help->id => $comp_help
        ), $john->get_related('course_completions'));
        $john->add_related('course_completions', array(
            $comp_yesterday,
            $comp_girl
        ));
        $comp_yesterday->refresh();
        $comp_girl->refresh();
        $this->assertEquals(array(
            $comp_help->id => $comp_help,
            $comp_yesterday->id => $comp_yesterday,
            $comp_girl->id =>$comp_girl
        ), $john->get_related('course_completions'));
    }

    public function test_remove_related_ids() {
        $john = new User(array('username'=>'john'));
        $john->save();
        $help = new Course(array('shortname'=>'Help'));
        $help->save();
        $yesterday = new Course(array('shortname'=>'Yesterday'));
        $yesterday->save();
        $girl = new Course(array('shortname'=>'Girl'));
        $girl->save();
        $comp_help = new CourseCompletion(array('user'=>$john, 'course'=>$help));
        $comp_help->save();
        $comp_yesterday = new CourseCompletion(array('user'=>$john, 'course'=>$yesterday));
        $comp_yesterday->save();
        $comp_girl = new CourseCompletion(array('user'=>$john, 'course'=>$girl));
        $comp_girl->save();
        $this->assertEquals(array(
            $comp_help->id => $comp_help,
            $comp_yesterday->id => $comp_yesterday,
            $comp_girl->id => $comp_girl
        ), $john->get_related('course_completions'));
        $john->remove_related('course_completions', array(
            $comp_help->id,
            $comp_yesterday->id
        ));
        $this->assertEquals(array(
            $comp_girl->id =>$comp_girl
        ), $john->get_related('course_completions'));
    }
    
    public function test_remove_related_objects() {
        $john = new User(array('username'=>'john'));
        $john->save();
        $help = new Course(array('shortname'=>'Help'));
        $help->save();
        $yesterday = new Course(array('shortname'=>'Yesterday'));
        $yesterday->save();
        $girl = new Course(array('shortname'=>'Girl'));
        $girl->save();
        $comp_help = new CourseCompletion(array('user'=>$john, 'course'=>$help));
        $comp_help->save();
        $comp_yesterday = new CourseCompletion(array('user'=>$john, 'course'=>$yesterday));
        $comp_yesterday->save();
        $comp_girl = new CourseCompletion(array('user'=>$john, 'course'=>$girl));
        $comp_girl->save();
        $this->assertEquals(array(
            $comp_help->id => $comp_help,
            $comp_yesterday->id => $comp_yesterday,
            $comp_girl->id => $comp_girl
        ), $john->get_related('course_completions'));
        $john->remove_related('course_completions', array(
            $comp_help,
            $comp_yesterday
        ));
        $this->assertEquals(array(
            $comp_girl->id =>$comp_girl
        ), $john->get_related('course_completions'));
    }
}
