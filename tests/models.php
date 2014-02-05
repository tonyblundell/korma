<?php

require_once dirname(dirname(__FILE__)) . '/korma.php';

class User extends Model {
    protected static $table = 'user';
    protected static $fields = array(
        'id' => 'integer',
        'username' => 'string',
        'firstname' => 'string',
        'lastname' => 'string'
    );
    protected static $one_to_many_relations = array(
        'course_completions' => array(
            'model' => 'CourseCompletion',
            'field' => 'userid'
        )
    );
}

class Course extends Model {
    protected static $table = 'course';
    protected static $fields = array(
        'id' => 'integer',
        'shortname' => 'string',
        'newsitems' => 'integer'
    );
    protected static $one_to_many_relations = array(
        'course_completions' => array(
            'model' => 'CourseCompletion',
            'field' => 'course'
        )
    );
}

class CourseCompletion extends Model {
    protected static $table = 'course_completions';
    protected static $fields = array(
        'id' => 'integer'
    );
    protected static $many_to_one_relations = array(
        'user' => array(
            'model' => 'User',
            'field' => 'userid'
        ),
        'course' => array(
            'model' => 'Course',
            'field' => 'course'
        )
    );
}
