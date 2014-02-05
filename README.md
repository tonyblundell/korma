# Korma - Expermental ORM layer for Moodle

## How To Use

1.  Drop korma.php into your project
2.  Add a require('korma.php') to your script

## Tests

Unit tests are included. Run them with PHPunit in the usual way.

## Defining Models

        class Author extends Model {
            protected static $table = 'author';
            protected static $fields = array(
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
            protected static $table = 'book';
            protected static $fields = array(
                'title' => 'string',
                'year' => 'integer'
            );
            protected static $many_to_one_relations = array(
                'author' => array(
                    'model' => 'Author',
                    'field' => 'authorid'
                )
            );
        }

    You don't have to describe all fields in the database. Just the ones you 
want to use.

## Retrieving Instances

        Book::get();

Returns an array of all books in the DB.

        Book::get_one();
    
Returns the first book in the database
      
        Book::count();

Returns a count of all books in the DB.

These methods can also be used with filters.

## Filters

        Book::get(array(
            'year' => 1929
        ));

Returns a list of books published in 1929.

Can also be written as:
        
        Book::get(array(
            'year__eq' => 1929
        ));

Other filters are available:

        *   ieq (case insensitive)
        *   gt
        *   gte
        *   lt
        *   lte
        *   startswith
        *   istartswith
        *   endswith
        *   iendswith
        *   contains
        *   icontains
        *   in

## AND-ing Filters

        Book::get(array(
            'year__gt' => 2010
            'title__contains' => 'PHP'
        ));

Returns all PHP books published since 2010.

## OR-ing Filters

        Book::get(array(
            'title__contains' => 'Javascript'
        ), array(
            'title__contains' => 'jQuery'
        ));

Returns all books about Javascript OR jQuery.

## Creating And Saving Instances

        $emily = new Author();
        $emily->first_name = 'Emily';
        $emily->last_name = 'Bronte';
        $emily->save();

Creates the book and saves it to the database.

Can also be written as:
        
        $emily = new Book(array(
            'first_name' => 'Emily',
            'last_name' => 'Bronte'
        ));
        $emily->save();

Alter properties and re-save:
    
        $emily->last_name => 'Brontë';
        $emily->save();

Properties can be other objects if 'many to one' relationships are defined.
        
        $heights = Book::get(array(
            'title' => 'Wuthering Heights'
        ));
        $heights->author = $emily;
        $heights->save();

## Deleting Instances
    
        Book::delete(array(
            'year__lt' => 1984
        ));

Deletes all books released prior to 1984.

## Working With 'One To Many' Relations

        $emily->get_related('books');
        
Returns a list of Emily's books.

        $emily->add_related('books', array($heights));
        $emily->remove_related('books', array($heights));
        $emily->set_related('books', array($heights));

Updates the related objects accordingly. Elements in the array can be either
instances or IDs.

## Refresh An Instance From The Database

        $emily->refresh();

Pulls all fields directly from the database and updates the instance.

    
