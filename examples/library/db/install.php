<?php

function xmldb_local_library_install() {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/library/models.php'; 

    $orwell = new Author(array('firstname' => 'George', 'lastname' => 'Orwell'));
    $orwell->save();

    $animal = new Book(array('author' => $orwell, 'title' => 'Animal Farm'));
    $animal->save();

    $nineteen = new Book(array('author' => $orwell, 'title' => '1984'));
    $nineteen->save();

    return true;
}
