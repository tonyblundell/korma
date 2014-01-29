<?php

defined('MOODLE_INTERNAL') || die();

class Model {

    // REQUIRED CLASS LEVEL CONSTANTS

    protected static $table;
    protected static $fields;

    // OPTIONAL CLASS LEVEL CONSTANTS
    protected static $one_to_many_relations = array();
    protected static $many_to_one_relations = array();

    // CLASS LEVEL INTERFACE
 
    public static function count() {
        global $DB;
        $clause_lists = func_get_args();
        $clauses_sql = static::generate_clauses_sql($clause_lists);
        $sql = "SELECT COUNT(*) FROM {" . static::$table ."} AS base $clauses_sql";
        return $DB->count_records_sql($sql);
    }

    public static function get() {
        $clause_lists = func_get_args();
        return static::get_instances($clause_lists);
    }

    public static function get_one() { 
        $clause_lists = func_get_args();
        $instances = static::get_instances($clause_lists);
        $instance = reset($instances);
        if ($instance) {
            return $instance;
        } 
        return NULL;
    }

    public static function delete() {
        global $DB;
        $clause_lists = func_get_args();
        $clauses_sql = static::generate_clauses_sql($clause_lists);
        $clauses_sql = preg_replace('/^WHERE/', '', $clauses_sql);
        $clauses_sql = preg_replace('/base\./', '', $clauses_sql);
        return $DB->delete_records_select(static::$table, $clauses_sql);
    }

    // INSTANCE LEVEL INTERFACE

    public function __construct($attrs=NULL) {
        if ($attrs) {
            foreach($attrs as $attr => $value) {
                $this->$attr = $value;
            }
        }
    }
 
    public function save() {
        global $DB;
        $record = new stdClass();
        foreach(static::$fields as $field_name => $field_type) {
            if (isset($this->{$field_name})) {
                $record->{$field_name} = $this->{$field_name};
            }
        }
        if (isset(static::$many_to_one_relations)) {
            foreach(static::$many_to_one_relations as $relation) {
                list($rel_name, $rel_field, $rel_class) = $relation;
                if (isset($this->{$rel_name})) {
                    $record->{$rel_field} = $this->{$rel_name}->id;
                }
            }
        }
        if(isset($this->id)) {
            $DB->update_record(static::$table, $record);
        } else {
            $this->id = $DB->insert_record(static::$table, $record);
        }
        return $this->id;
    }

    public function get_related($relation) {
        foreach(static::$one_to_many_relations as $rel) {
            list($rel_name, $rel_field, $rel_class) = $rel;
            if ($rel_name === $relation) {
                return $rel_class::get(
                    array($rel_field.'__eq'=>$this->id)
                );
            }
        }
    }

    // PRIVATE FUNCTIONS

    private static function generate_clauses_sql($clause_lists) {
        $clauses = array();
        foreach($clause_lists as $clause_list) {
            $clauses [] = static::parse_clause_list($clause_list);
        }
        if ($clauses) {
            return 'WHERE'.implode(' OR ', $clauses);
        }
        return '';
    }

    private static function parse_clause_list($clause_list) {
        $clauses = array();
        foreach($clause_list as $field=>$value) {
            $clauses[] = static::parse_clause($field, $value);
        }
        return '('.implode(' AND ', $clauses).')';
    }

    private static function parse_clause($field, $value) {
        $table = static::$table;
        $exploded = explode('__', $field);
        $exploded_count = count($exploded);
        $clause = $exploded[$exploded_count-1];
        $field = $exploded[$exploded_count-2];
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
    
    private static function get_instances($clause_lists, $limit=0) {
        global $DB;
        $sql = static::generate_sql($clause_lists, $limit);
        $records = $DB->get_records_sql($sql);
        $instances = array_map(array('static', 'record_to_instance'), $records);
        return $instances;
    }

    private static function generate_sql($clause_lists, $limit=0) {
        $select_sql = "SELECT ";
        $from_sql = " FROM {" . static::$table . "} AS base ";
        $where_sql = static::generate_clauses_sql($clause_lists);
        foreach (static::$fields as $field => $type) {
            $select_sql .= "base.$field AS base__$field, "; 
        }
        foreach (static::$many_to_one_relations as $relation) {
            list($rel_name, $rel_field, $rel_class) = $relation;
            $rel_table = $rel_class::$table;
            $from_sql .= "JOIN {{$rel_table}} AS korma__$rel_name ";
            $from_sql .= "ON (korma__{$rel_name}.id = base.$rel_field) ";
            foreach ($rel_class::$fields as $field => $type) {
                $select_sql .= "korma__{$rel_name}.$field AS {$rel_name}__$field, ";
            }
        }
        $select_sql = rtrim($select_sql, ", ");
        $sql = "$select_sql $from_sql $where_sql";
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        return $sql;
    }

    private static function record_to_instance($record) {
        $instance = new static();
        foreach(static::$fields as $field_name => $field_type) {
            $instance->{$field_name} = $record->{"base__$field_name"};
            settype($instance->{$field_name}, $field_type);
        }
        if (isset(static::$many_to_one_relations)) {
            foreach(static::$many_to_one_relations as $relation) {
                list($rel_name, $rel_field, $rel_class) = $relation;
                $rel_instance = new $rel_class();
                foreach($rel_class::$fields as $field_name => $field_type) {
                    $rel_instance->{$field_name} = $record->{"{$rel_name}__$field_name"};
                    settype($rel_instance->{$field_name}, $field_type);
                }
                $instance->{$rel_name} = $rel_instance;
            }
        }
        return $instance;
    }

}
