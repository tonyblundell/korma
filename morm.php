<?php

defined('MOODLE_INTERNAL') || die();

class Model {

    protected static $table;
    protected static $fields;

    public static function count() {
        global $DB;
        $tables_sql = static::get_tables_sql();
        $clauses_sql = static::get_clauses_sql(func_get_args());
        $sql = "SELECT COUNT(*) $tables_sql $clauses_sql";
        return $DB->count_records_sql($sql);
    }

    public static function get() {
        global $DB;
        $sql = static::get_get_sql(func_get_args());
        $records = $DB->get_records_sql($sql);
        return static::record_array_to_instance_array($records);
    }

    public static function get_one() {
        global $DB;
        $sql = static::get_get_sql(func_get_args());
        $record = $DB->get_record_sql($sql, null, IGNORE_MULTIPLE);
        if ($record) {
            return static::record_to_instance($record);
        }
    }

    public function __construct($attrs=NULL) {
        if ($attrs) {
            foreach($attrs as $attr => $value) {
                $this->$attr = $value;
            }
        }
    }
 
    public function as_array() {
        $arr = array();
        foreach(static::$fields as $field_name => $field_type) {
            if (isset($this->{$field_name})) {
                $arr[$field_name] = $this->{$field_name};
            }
        }
        if (isset(static::$relations)) {
            foreach(static::$relations as $relation) {
                list($model_field, $id_field, $class) = $relation;
                if (isset($this->{$model_field})) {
                    $arr[$id_field] = $this->{$model_field}->id;
                }
            }
        }
        return $arr;
    }

    public function as_obj() {
        return (object)$this->as_array();
    }
 
    public function save() {
        global $DB;
        $obj = $this->as_obj();
        if (isset($this->id)) {
            $DB->update_record(static::$table, $obj);
        } else {
            $this->id = $DB->insert_record(static::$table, $obj);
        }
    }

    private static function record_to_instance($record) {
        $instance = new static((array)$record);
        foreach(static::$fields as $field_name => $field_type) {
            settype($instance->{$field_name}, $field_type);
        }
        if (isset(static::$relations)) {
            foreach(static::$relations as $relation) {
                list($model_field, $id_field, $class) = $relation;
                if (isset($instance->{$id_field})) {
                    $instance->{$model_field} = $class::get_one(
                        array('id__eq'=>$instance->{$id_field})
                    );
                }
                if ($id_field !== $model_field) {
                    unset($instance->{$id_field});
                }
            }
        }
        return $instance;
    }

    private static function record_array_to_instance_array($record_array) {
        return array_map(array('static', 'record_to_instance'), $record_array);
    }

    private static function get_moodlified_table_name($table) {
        return '{'.$table.'}';
    }

    private static function get_tables_sql() {
        $tables = array();
        array_push($tables, static::get_moodlified_table_name(static::$table));
        if (property_exists(get_called_class(), 'relation')) {
            foreach(static::$relations as $relation => $column) {
                array_push($tables, $static::get_moodlifield_table_name($relation));
            }
        }
        return 'FROM '.implode(', ', $tables);
    }

    private static function parse_clause($field, $value) {
        $table = static::get_moodlified_table_name(static::$table);
        $exploded = explode('__', $field);
        $clause = $exploded[count($exploded)-1];
        $field = $exploded[count($exploded)-2];
        switch ($clause) {
            case 'eq': return "$table.$field = '$value'";
            case 'ieq': return "$table.$field ILIKE '$value'";
            case 'gt': return "$table.$field > $value";
            case 'gte': return "$table.$field >= $value";
            case 'lt': return "$table.$field < $value";
            case 'lte': return "$table.$field <= $value";
            case 'startswith': return "$table.$field LIKE '$value%'";
            case 'istartswith': return "$table.$field ILIKE '$value%'";
            case 'endswith': return "$table.$field LIKE '%$value'";
            case 'iendswith': return "$table.$field ILIKE '%$value'";
            case 'contains': return "$table.$field LIKE '%$value%'";
            case 'icontains': return "$table.$field ILIKE '%$value%'";
            case 'in':
                $value = implode("', '", $value);
                return "$table.$field IN ('$value')";
        }
    }

    private static function parse_clause_list($clause_list) {
        $clauses = array();
        foreach($clause_list as $field=>$value) {
            $clauses[] = static::parse_clause($field, $value);
        }
        return '('.implode(' AND ', $clauses).')';
    }

    private static function get_clauses_sql($clause_lists) {
        $clauses = array();
        foreach($clause_lists as $clause_list) {
            $clauses [] = static::parse_clause_list($clause_list);
        }
        if ($clauses) {
            return 'WHERE'.implode(' OR ', $clauses);
        }
        return '';
    }

    private static function get_select_sql() {
        $table = static::get_moodlified_table_name(static::$table);
        $fields = array();
        foreach(static::$fields as $field_name => $field_type) {
            $fields[] = "$table.$field_name";
        }
        if (isset(static::$relations)) {
            foreach(static::$relations as $relation) {
                list($model_field, $id_field, $class) = $relation;
                $fields[] = "$table.$id_field";
            }
        }
        return 'SELECT '.implode(', ', $fields);
    }

    private static function get_order_sql() {
        return 'ORDER BY '.static::get_moodlified_table_name(static::$table);
    }

    private static function get_get_sql($clause_lists) {
        $select_sql = static::get_select_sql();
        $tables_sql = static::get_tables_sql();
        $clauses_sql = static::get_clauses_sql($clause_lists);
        $order_sql = static::get_order_sql();
        $sql = "$select_sql $tables_sql $clauses_sql $order_sql";
        return $sql;
    }
}
