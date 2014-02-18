<?php

// Use Symfony's Request/Response classes
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Bootstrap Moodle
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
global $CFG;

// Create & configure the Silex app
require_once "{$CFG->dirroot}/vendor/autoload.php";
$app = new Silex\Application();
$app['debug'] = true;
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => dirname(__FILE__),
    'twig.options' => array(
        'cache' => false
    ),
));

// Forbid access if a user is not logged in.
$app->before(function(Request $request) use ($app) {
    if (!isloggedin()) {
        return new Response('403 FORBIDDEN', 403);
    }
});

// Grab all books, display via template.
$app->get('/', function() use($app) {
    require_once 'models.php';
    global $USER;
    return $app['twig']->render('index.twig', array(
        'userid' => $USER->id,
        'books' => Book::get()
    ));
});

// Check-out or return a book.
$app->post('/', function(Request $request) use($app) {
    require_once 'models.php';
    global $USER;
    $user = User::get_one(array('id'=>$USER->id));
    $book = Book::get_one(array('id'=>$request->get('bookid')));

    // If book is already checked-out by user, return it.
    if ($book->loanee) {
        if ($book->loanee == $user) {
            $book->loanee = null;
            $book->save();
        }

    // If book isn't already checked-out, check it out.
    } else {
        $book->loanee = $user;
        $book->save();
    }

    return $app->redirect('/local/library');
});

$app->run();
