# Korma

Experimental ORM layer for Moodle

## How To Use

1.  Drop korma.php into your project
2.  Add a require('korma.php') to your script

## Tests

Unit tests are included. Run them with PHPunit in the usual way.

## Defining Models

```php
class Author extends Model {
    protected static $table = 'author';
    protected static $fields = array(
        'firstname' => 'string',s
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
```

You don't have to describe all fields in the database. Just the ones you 
want to use.

## Retrieving Instances

```php
Book::get();
```

Returns an array of all books in the DB.

```php
Book::get_one();
```
    
Returns the first book in the database. If a filter was specified, would return
the first matching result. If there are no matches in the database, returns 
boolean false.

```php
Book::count();
```

Returns a count of all books in the DB. Can also be used with filters.

## Filters

```php
Book::get(array(
    'year' => 1929
));
```

Returns a list of books published in 1929.

Can also be written as:
        
```php
Book::get(array(
    'year__eq' => 1929
));
```

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

```php
Book::get(array(
    'year__gt' => 2010
    'title__contains' => 'PHP'
));
```

Returns all books that have PHP in the title *AND* were published since 2010.

## OR-ing Filters

```php
Book::get(array(
    array(
        'title__contains' => 'Javascript'
    ), array(
        'title__contains' => 'jQuery'
    )
));
```

Returns all that have Javascript *OR* jQuery in the title.

## Selecting Across Tables

```php
Book::get(array(
    'title__startswith' => 'A',
    'author__lastname__startswith' => 'A'
));
```

Returns all books where the title and the author's last name start with A.

## Creating And Saving Instances

```php
$emily = new Author();
$emily->first_name = 'Emily';
$emily->last_name = 'Bronte';
$emily->save();
```

Creates the book and saves it to the database.

Can also be written as:
        
```php
$emily = new Book(array(
    'first_name' => 'Emily',
    'last_name' => 'Bronte'
));
$emily->save();
```

Alter properties and re-save:
    
```php
$emily->last_name => 'Brontë';
$emily->save();
```

Properties can be other objects if many-to-one relationships are defined.

```php
$heights = Book::get(array(
    'title' => 'Wuthering Heights'
));
$heights->author = $emily;
$heights->save();
```

## Deleting Instances

```php
Book::delete(array(
    'year__lt' => 1984
));
```

Deletes all books released prior to 1984.

## Working With One-To-Many Relations

```php
$emily->get_related('books');
```
        
Returns a list of Emily's books.

```php
$emily->add_related('books', array($heights));
```

Sets $height's authorid to $emily->id.

```php
$emily->remove_related('books', array($heights));
```

Removes Emily's books by NULLing the book->authorid field. 
Note: some 'FK' fields in Moodle aren't NULLable (course_completions.course, 
for example). In these cases, using model::delete may be more suitable.

```php
$emily->set_related('books', array($heights));
```

Removes Emily's existing books then adds the ones specified.

All of these functions will accept an array of instances or IDs. 

## Refreshing An Instance From The Database

```php
$emily->refresh();
```

Pulls all fields directly from the database and updates the instance.

## Raw SQL queries

For queries that are too complex to be expressed with the array structure described above, use the following functions which all accept a raw SQL where clause.

```php
Author::get("lastname = 'Brontë'");
```

```php
Author::get_one("lastname = 'Brontë'");
```

```php
Author::count("lastname = 'Brontë'");
```

```php
Author::delete("lastname = 'Brontë'");
```

## Example Plugin

A very basic, Silex-based Moodle local plugin is included in the examples folder.
