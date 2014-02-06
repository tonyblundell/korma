<?php
/*
Korma - Experimental ORM layer for Moodle
tony.blundell@gmail.com
v.0.1.0
 */

defined('MOODLE_INTERNAL') || die();

class Model {

    protected static $table;
    protected static $fields;
    protected static $one_to_many_relations = array();
    protected static $many_to_one_relations = array();

    public static function get($clauses=array(), $order='id', $limit=0, $offset=0) {
        // Gets all matching rows from the database then returns as an array
        // of instances of this class.
        // See the comments within get_records_sql for details on how the
        // parameters are interpreted.
        global $DB;
        $sql = static::generate_sql($clauses, $order, $limit, $offset);
        $records = $DB->get_records_sql($sql);
        $instances = array_map(array('static', 'record_to_instance'), $records);
        return $instances;
    }

    public static function get_one($clauses=array()) {
        // Makes a call to get, then returns the first found instance.
        // If no matching instances are found, this would return boolean false
        // which is the result of calling reset on an empty array in PHP.
        // See the comments within get_records_sql for details on how the
        // parameters are interpreted.
        $instances = static::get($clauses, 'id', 0);
        return reset($instances);
    }

    public static function count($clauses=array()) {
        // See the comments within get_records_sql for details on how the
        // parameters are interpreted.
        global $DB;
        $sql_where = static::generate_sql_where_clause($clauses);
        $sql = "SELECT COUNT(*) FROM {" . static::$table ."} AS base ";
        if ($sql_where) {
            $sql .= "WHERE $sql_where";
        }
        return $DB->count_records_sql($sql);
    }

    public static function delete($clauses=array()) {
        // See the comments within get_records_sql for details on how the
        // parameters are interpreted.
        global $DB;
        $clauses_sql = static::generate_sql_where_clause($clauses);
        $clauses_sql = preg_replace('/^WHERE/', '', $clauses_sql);
        $clauses_sql = preg_replace('/base\./', '', $clauses_sql);
        return $DB->delete_records_select(static::$table, $clauses_sql);
    }

    public function __construct($attrs=array()) {
        // Class constructor, accepts an optional $attrs argument which should
        // be an associative array if passed.
        // For every item in the array, we set an attribute on the instance
        // being constructed.
        foreach($attrs as $attr => $value) {
            $this->$attr = $value;
        }
    }
 
    public function save() {
        // Updates the record if it already exists, creates if not.
        // Creates a stdClass instance to give to the $DB functions, then 
        // populates it with each field value based on $this's attrs.
        // Also loops through any many-to-one relations and sets the 
        // appropriate ID field.
        // Finally refreshes from the DB to get any fields we didn't specify
        // but will have had default values saved by the DB.
        global $DB;
        $record = new stdClass();
        foreach(static::$fields as $field_name => $field_type) {
            if (isset($this->{$field_name})) {
                $record->{$field_name} = $this->{$field_name};
            }
        }
        if (isset(static::$many_to_one_relations)) {
            foreach(static::$many_to_one_relations as $rel_name => $rel) {
                if (isset($this->{$rel_name})) {
                    $record->{$rel['field']} = $this->{$rel_name}->id;
                }
            }
        }
        if(isset($this->id)) {
            $DB->update_record(static::$table, $record);
        } else {
            $this->id = $DB->insert_record(static::$table, $record);
        }
        return $this->refresh();
    }

    public function refresh() {
        // Pulls the record from the database, updates $this's attributes
        // accordingly.
        $got = static::get_one(array('id__eq'=>$this->id));
        foreach (get_object_vars($got) as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    public function get_related($relation) {
        // Returns a list of related objects, each item would be an instance
        // of the specified class.
        $rel = static::$one_to_many_relations[$relation];
        return $rel['model']::get(
            array($rel['field'].'__eq'=>$this->id)
        );
    }

    public function set_related($relation, $items) {
        // First removes existing related objects (by NULLing the 'FK' field),
        // then adds the new related objects.
        global $DB;
        $rel = static::$one_to_many_relations[$relation]; 
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = NULL 
            WHERE {$rel['field']} = $this->id
        ");
        $ids = array();
        foreach(is_array($items) ? $items : array($items) as $item) {
            $ids[] = is_object($item) ? $item->id : $item; 
        }
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = $this->id 
            WHERE id IN (".implode(', ', $ids).")
        ");
    }

    public function add_related($relation, $items) {
        // Adds a related item by setting it's 'FK' field to $this's ID.
        // Accepts an array of instances or IDs.
        global $DB;
        $rel = static::$one_to_many_relations[$relation]; 
        $ids = array();
        foreach(is_array($items) ? $items : array($items) as $item) {
            $ids[] = is_object($item) ? $item->id : $item; 
        }
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = $this->id 
            WHERE id IN (".implode(', ', $ids).")
        ");
    }

    public function remove_related($relation, $items) {
        // Removes the specified related objects by NULLing the 'FK' field.
        // If the field is set to NOT NULL in the database, this will error.
        // In those cases model::delete() may be more suitable.
        global $DB;
        $rel = static::$one_to_many_relations[$relation]; 
        $ids = array();
        foreach(is_array($items) ? $items : array($items) as $item) {
            $ids[] = is_object($item) ? $item->id : $item; 
        }
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = NULL 
            WHERE id IN (".implode(', ', $ids).")
        ");
    }

    private static function generate_sql($clauses, $order, $limit, $offset) {
        // Private function for generating the SQL select clause.
        // Clauses must be an array. Items in the array are AND-ed together,
        // if an array of arrays is specified, the arrays are OR-ed.
        // See README.md for examples.
        // Loops through fields, adding a line to select each one, then loops 
        // through many-to-one relations, joining each table and selecting each
        // of it's fields.
        // Applies LIMIT and OFFSET directly.
        // Applies ORDER ascending by default, or descending if it the field
        // name is preceded by a minus sign.
        $select_sql = "SELECT ";
        $from_sql = " FROM {" . static::$table . "} AS base ";
        $where_sql = static::generate_sql_where_clause($clauses);
        if ($where_sql) {
            $where_sql = "WHERE $where_sql";
        }
        foreach (static::$fields as $field => $type) {
            $select_sql .= "base.$field AS base__$field, "; 
        }
        foreach (static::$many_to_one_relations as $rel_name => $rel) {
            $rel_table = $rel['model']::$table;
            $from_sql .= "JOIN {{$rel_table}} AS korma__$rel_name ";
            $from_sql .= "ON (korma__{$rel_name}.id = base.{$rel['field']}) ";
            foreach ($rel['model']::$fields as $field => $type) {
                $select_sql .= "korma__{$rel_name}.$field AS {$rel_name}__$field, ";
            }
        }
        $select_sql = rtrim($select_sql, ", ");
        $sql = "$select_sql $from_sql $where_sql";
        if ($order) {
            if (strpos($order, '-') === 0) {
                $order = substr($order, 1);
                $sql .= " ORDER BY base.$order DESC ";
            } else {
                $sql .= " ORDER BY base.$order ASC ";
            }
        }
        if ($limit) {
            $sql .= " LIMIT $limit ";
        }
        if ($offset) {
            $sql .= " OFFSET $offset ";
        }
        return $sql;
    }

    private static function generate_sql_where_clause($clauses) {
        // Private function for generating the 'WHERE' part of an SQL statement.
        // See the comments within generate_sql for details of how clauses
        // are interpreted.
        if (array_keys($clauses) !== range(0, count($clauses) - 1)) {
            $clauses = array($clauses);
        }
        $sql = '';
        foreach ($clauses as $list) {
            $sql .= '(';
            foreach($list as $field => $value) {
                $sql .= '(';
                $sql .= static::parse_clause($field, $value);
                $sql .= ') AND ';
            }
            $sql = rtrim($sql, 'AND ');
            $sql .= ') OR ';
        } 
        $sql = rtrim($sql, 'OR ');
        if ($sql == '()') { 
            $sql = '';
        }
        return $sql;
    }

    private static function parse_clause($field, $value) {
        // Private function for parsing a field clause to SQL.
        // Expects the field to be given in the format fieldname__clausetype.
        // If just a field name is specified, defaults to an equals clause-type.
        $table = static::$table;
        $exploded = explode('__', $field);
        $count = count($exploded);
        $field = $exploded[0];
        $clause  = $count > 1 ? $exploded[$count-1] : 'eq';
        switch ($clause) {
            case 'eq': return "base.$field = '$value'";
            case 'ieq': return "base.$field ILIKE '$value'";
            case 'gt': return "base.$field > $value";
            case 'gte': return "base.$field >= $value";
            case 'lt': return "base.$field < $value";
            case 'lte': return "base.$field <= $value";
            case 'startswith': return "base.$field LIKE '$value%'";
            case 'istartswith': return "base.$field ILIKE '$value%'";
            case 'endswith': return "base.$field LIKE '%$value'";
            case 'iendswith': return "base.$field ILIKE '%$value'";
            case 'contains': return "base.$field LIKE '%$value%'";
            case 'icontains': return "base.$field ILIKE '%$value%'";
            case 'in':
                $value = implode("', '", $value);
                return "base.$field IN ('$value')";
        }
    }

    private static function record_to_instance($record) {
        // Private function for casting a record object returned by $DB
        // to an instance of this class.
        // Loops through each field, setting the associated attribute,
        // then loops through many-to-one relations, creating child instances.
        $instance = new static();
        foreach(static::$fields as $field_name => $field_type) {
            $instance->{$field_name} = $record->{"base__$field_name"};
            settype($instance->{$field_name}, $field_type);
        }
        if (isset(static::$many_to_one_relations)) {
            foreach(static::$many_to_one_relations as $rel_name => $rel) {
                $rel_instance = new $rel['model']();
                foreach($rel['model']::$fields as $field_name => $field_type) {
                    $rel_instance->{$field_name} = $record->{"{$rel_name}__$field_name"};
                    settype($rel_instance->{$field_name}, $field_type);
                }
                $instance->{$rel_name} = $rel_instance;
            }
        }
        return $instance;
    }

}
