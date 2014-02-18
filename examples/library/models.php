<?php

global $CFG;
require_once $CFG->dirroot . '/korma.php';

class Author extends Model {
    protected static $table = 'local_library_author';

    protected static $fields = array(
        'id' => 'integer',
        'firstname' => 'string',
        'lastname' => 'string'
    );

    protected static $one_to_many_relations = array(
        'books' => array(
            'model' => 'Book',
            'field' => 'authorid'
        )
    );
}

class Book extends Model {
    protected static $table = 'local_library_book';

    protected static $fields = array(
        'id' => 'integer',
        'title' => 'string',
    );

    protected static $many_to_one_relations = array(
        'author' => array(
            'model' => 'Author',
            'field' => 'authorid'
        ),
        'loanee' => array(
            'model' => 'User',
            'field' => 'loaneeid'
        )
    );

    public function is_checkoutable($userid) {
        return !$this->loanee;
    }

    public function is_returnable($userid) {
        return $this->loanee->id == $userid;
    }
}

class User extends Model {
    protected static $table = 'user';

    protected static $fields = array(
        'id' => 'integer',
        'username' => 'string',
        'firstname' => 'string',
        'lastname' => 'string'
    );

    protected static $one_to_many_relations = array(
        'loans' => array(
            'model' => 'Book',
            'field' => 'loaneeid'
        )
    );
}
