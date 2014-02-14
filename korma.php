<?php
/*
Korma - Experimental ORM layer for Moodle
tonyblundell@gmail.com
v.0.1.3
 */

defined('MOODLE_INTERNAL') || die();

class Model {

    protected static $table;
    protected static $fields;
    protected static $one_to_many_relations = array();
    protected static $many_to_one_relations = array();

    /**
     * Gets all matching rows from the database then returns as an array
     * of instances of this class.
     * See the comments within get_records_sql for details on how the
     * parameters are interpreted.
     * 
     * @param array $clauses A key/value pair array of where clauses
     * @param string $order  The ORDER BY column of the query
     * @param int $limit     The number of records to return
     * @param int $offset    The offset from the start of the table to start the query.
     * 
     * @return array[Model] An array of instances of the underlying model.
     */
    public static function get(
        array $clauses = array(), $order = 'id', $limit = 0, $offset = 0
    ) {
        global $DB;
        $sql = static::generate_sql($clauses, $order, $limit, $offset);
        $records = $DB->get_records_sql($sql);
        $instances = array_map(array('static', 'record_to_instance'), $records);
        return $instances;
    }

    /**
     * Returns the first found instance based on the clauses.
     * 
     * @param array $clauses A key/value pair array of where clauses
     * 
     * @return stdClass An object representing a row of the table.
     */
    public static function get_one(array $clauses = array()) {
        // Makes a call to get, then returns the first found instance.
        // If no matching instances are found, this would return boolean false
        // which is the result of calling reset on an empty array in PHP.
        // See the comments within get_records_sql for details on how the
        // parameters are interpreted.
        $instances = static::get($clauses, 'id', 1);
        return reset($instances);
    }

    /**
     * Returns the number of entries that would be returned if the query
     * with the specified clauses is run
     * 
     * @param array $clauses A key/value pair array of where clauses
     * 
     * @return int The number of records that would be returned.
     */
    public static function count(array $clauses = array()) {
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

    /**
     * Deletes a record specified by the constraints in the clauses passed in.
     * 
     * @param array $clauses A key/value pair array of where clauses
     * 
     * @return boolean TRUE always.
     */
    public static function delete(array $clauses = array()) {
        // See the comments within get_records_sql for details on how the
        // parameters are interpreted.
        global $DB;
        $clauses_sql = static::generate_sql_where_clause($clauses);
        $clauses_sql = preg_replace('/^WHERE/', '', $clauses_sql);
        $clauses_sql = preg_replace('/base\./', '', $clauses_sql);
        return $DB->delete_records_select(static::$table, $clauses_sql);
    }

    /**
     * Class constructor, accepts an optional $attrs argument which should
     * be an associative array if passed.
     * For every item in the array, an attribute is set on the instance
     * being constructed.
     * 
     * @param array $attrs
     */
    public function __construct(array $attrs = array()) {
        foreach($attrs as $attr => $value) {
            $this->$attr = $value;
        }
    }
 
    /**
     * Updates the record if it already exists, creates if not.
     * 
     * @return stdClass The model with its amended data.
     */ 
    public function save() {
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

    /**
     * Refreshes the model with the data currently in the database
     * 
     * @return stdClass The updated instance.
     */ 
    public function refresh() {
        // Pulls the record from the database, updates $this's attributes
        // accordingly.
        $got = static::get_one(array('id__eq' => $this->id));
        foreach (get_object_vars($got) as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * Get related objects based on foreign keys on this model.
     * 
     * @param string $relation The relation to follow
     * 
     * @return array An array of related objects, each being an instance of
     *               the specified class.
     */
    public function get_related($relation) {
        // Returns a list of related objects, each item would be an instance
        // of the specified class.
        $rel = static::$one_to_many_relations[$relation];
        return $rel['model']::get(
            array($rel['field'].'__eq' => $this->id)
        );
    }

    /**
     * Resets all relationships so only the items specified will be
     * related to this model.
     * 
     * @param string $relation The relation to set
     * @param array  $items    The items to be newly related to the model.
     * 
     * @return bool TRUE always.
     */ 
    public function set_related($relation, array $items) {
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
        foreach($items as $item) {
            $ids[] = is_object($item) ? $item->id : $item; 
        }
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = $this->id 
            WHERE id IN (".implode(', ', $ids).")
        ");
    }

    /**
     * Adds the specified items as additional relations to the model.
     * 
     * @param string $relation The relation to change
     * @param array  $items    The additional items to be related to
     *                         the model
     * 
     * @return bool TRUE always.
     */ 
    public function add_related($relation, array $items) {
        // Adds a related item by setting it's 'FK' field to $this's ID.
        // Accepts an array of instances or IDs.
        global $DB;
        $rel = static::$one_to_many_relations[$relation]; 
        $ids = array();
        foreach($items as $item) {
            $ids[] = is_object($item) ? $item->id : $item; 
        }
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = $this->id 
            WHERE id IN (".implode(', ', $ids).")
        ");
    }

    /**
     * Removes the specified items from those with a relation to the model.
     * 
     * @param string $relation The relation to change
     * @param array  $items    The items to remove from the relation.
     * 
     * @return bool TRUE always.
     */
    public function remove_related($relation, $items) {
        // Removes the specified related objects by NULLing the 'FK' field.
        // If the field is set to NOT NULL in the database, this will error.
        // In those cases model::delete() may be more suitable.
        global $DB;
        $rel = static::$one_to_many_relations[$relation]; 
        $ids = array();
        foreach($items as $item) {
            $ids[] = is_object($item) ? $item->id : $item; 
        }
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = NULL 
            WHERE id IN (".implode(', ', $ids).")
        ");
    }

    /**
     * Generates the SQL required based on passed in clauses, order, limit and
     * offset.
     * 
     * @param array  $clauses The where clauses to use
     * @param string $order   The column to order by
     * @param int    $limit   The LIMIT statement within SQL
     * @param int    $offset  The OFFSET statement within SQl
     * 
     * @return string A valid SQL statement
     */ 
    private static function generate_sql(array $clauses, $order, $limit, $offset) {
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

    /**
     * Generates a WHERE clause based on the conditions passed in.
     * 
     * @param array $clauses The clauses for the WHERE statement
     * 
     * @return string A valid WHERE statement
     */
    private static function generate_sql_where_clause(array $clauses) {
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

    /**
     * Parses a clause for the type of condition.
     * Expects field to be of the form fieldname__clausetype
     * 
     * @param string $field The field and clause type.
     * @param mixed  $value The value to compare to.
     * 
     * @return string A part of a where clause.
     */
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

    /**
     * Takes a record as returned by moodle and converts it into an
     * Object related to the table it came from.
     * 
     * @param stdClass $record The record as returned from a moodle SQL query
     * 
     * @return Model An instance of the model relating to the table.
     */
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
