<?php

/**
 * This file is part of the PhpPivot
 * 
 * @author Jan Krakora
 */

namespace PhpPivot;

/**
 * Pivot class
 *
 * For more see http://stackoverflow.com/questions/7674786/mysql-pivot-table        

 */

class Pivot {

    public $description = "Pivot table query";
    public $source_table;
    public $rows;
    public $columns;
    public $columns_constraints = array();
    public $values;
    public $filters = array();

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function data() {
        $this->buildquery();
        return $this->execDbCommand($this->sqlquery);
    }
    
    public function buildquery() {
        $this->sqlquery = $this->buildSqliteQuery();
    }
    
    public function execDbCommand($query) {
        try {            
            $res = $this->pdo->query($query);            
            $data = $res->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } catch (Exception $e) {
            echo "SQL query: $query";
            echo $e->getTraceAsString();
            exit(0);
        }
    }
    
    public function pivotColumnValuesGetFromDb($pivot_column) {
        $c = $pivot_column;
        $table = $this->source_table;
        $query = "SELECT \"$c\" FROM \"$table\" GROUP BY \"$c\";";
        $data = $this->execDbCommand($query);
        $column_names = array();
        foreach ($data as $value) {
            $column_names[] = $value[$c];
        }
        return $column_names;
    }
    
    public function pivotColumnValuesSort() {
        return sort($this->column_values);
    }
    
    public function buildSqliteQuery() {
        // add description
        $str = "-- " . $this->description . "\n";
        $str .= "SELECT \"" . implode("\", \"", $this->rows) . "\"," . "\n";
        // get grouped column values
        $pivot_column = reset($this->columns);
        $this->column_values = $this->pivotColumnValuesGetFromDb($pivot_column);
        $this->pivotColumnValuesSort();
        // get values to be evaluated
        $pivot_eval = reset($this->values);
        // get eval method (SUM, COUNT, MIN, MAX, AVG)
        $pivot_eval_method = end($this->values);
        // build cases
        $cases = array();
        foreach ($this->column_values as $k => $value) {
                // evaluation method
                $qstr  = "$pivot_eval_method(";
                // case
                $qstr .= "  CASE";
                $qstr .= "    WHEN";
                $qstr .= "      $pivot_column = '$value'";
                // possible constraints
                foreach ($this->columns_constraints as $kk => $cstr) {
                        $qstr .= "  AND $cstr ";
                }
                $qstr .= "    THEN";
                $qstr .= "      $pivot_eval";
                $qstr .= "    ELSE"; 
                $qstr .= "      0";
                $qstr .= "  END";
                $qstr .= "  )";
                // column name
                $column_name = preg_replace("/\s/", "_", $value);
                $qstr .= "  AS " . $pivot_column . "_" . $column_name;
                $cases[] = $qstr;
        };
        $str .= "\t" . implode(", \n\t", $cases) . "\n";      
        // From
        $str .= "FROM \"$this->source_table\"" . "\n";
        // FIlters
        if(count($this->filters)>0){
            $str .= "WHERE ";
            $str .= implode(" AND ", $this->filters);
        }
        $pivot_groupby = end($this->rows);
        $str .= "GROUP BY \"$pivot_groupby\"" . "\n";
        $pivot_orderby = reset($this->rows);
        $str .= "ORDER BY \"$pivot_orderby\" ASC" . "\n";
        $str .= ";" . "\n";        
        return $str;
    }
    
    public function __toString() {
        return "<pre>" . var_export($this, true) . "</pre>";
    }
}
