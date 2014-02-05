<?php

defined('MOODLE_INTERNAL') || die();

class Model {

    protected static $table;
    protected static $fields;
    protected static $one_to_many_relations = array();
    protected static $many_to_one_relations = array();

    public static function get($clauses=array(), $order='id', $limit=0, $offset=0) {
        global $DB;
        $sql = static::generate_sql($clauses, $order, $limit, $offset);
        $records = $DB->get_records_sql($sql);
        $instances = array_map(array('static', 'record_to_instance'), $records);
        return $instances;
    }

    public static function get_one($clauses=array()) {
        $instances = static::get($clauses, 'id', 0);
        return reset($instances);
    }

    public static function count($clauses=array()) {
        global $DB;
        $sql_where = static::generate_sql_where_clause($clauses);
        $sql = "SELECT COUNT(*) FROM {" . static::$table ."} AS base ";
        if ($sql_where) {
            $sql .= "WHERE $sql_where";
        }
        return $DB->count_records_sql($sql);
    }

    public static function delete($clauses=array()) {
        global $DB;
        $clauses_sql = static::generate_sql_where_clause($clauses);
        $clauses_sql = preg_replace('/^WHERE/', '', $clauses_sql);
        $clauses_sql = preg_replace('/base\./', '', $clauses_sql);
        return $DB->delete_records_select(static::$table, $clauses_sql);
    }

    public function __construct($attrs=array()) {
        foreach($attrs as $attr => $value) {
            $this->$attr = $value;
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
        $got = static::get_one(array('id__eq'=>$this->id));
        foreach (get_object_vars($got) as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    public function get_related($relation) {
        $rel = static::$one_to_many_relations[$relation];
        return $rel['model']::get(
            array($rel['field'].'__eq'=>$this->id)
        );
    }

    public function set_related($relation, $items) {
        global $DB;
        $rel = static::$one_to_many_relations[$relation]; 
        $DB->execute("UPDATE {".$rel['model']::$table."} SET {$rel['field']} = 0 WHERE {$rel['field']} = $this->id");
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
        global $DB;
        $rel = static::$one_to_many_relations[$relation]; 
        $ids = array();
        foreach(is_array($items) ? $items : array($items) as $item) {
            $ids[] = is_object($item) ? $item->id : $item; 
        }
        $DB->execute("
            UPDATE {".$rel['model']::$table."} 
            SET {$rel['field']} = 0 
            WHERE id IN (".implode(', ', $ids).")
        ");
    }

    private static function generate_sql($clauses, $order, $limit, $offset) {
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

    private static function get_related_field_info($relation) {
        return static::$one_to_many_relations[$relation];
    }

}
