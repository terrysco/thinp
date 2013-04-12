<?php
class ThinpMysqli {
    /**
     * Static instance of self
     *
     * @var MysqliDb
     */
    protected static $_instance;
    /**
     * MySQLi instance
     *
     * @var mysqli
     */
    protected $_mysqli;
    /**
     * The SQL query to be prepared and executed
     *
     * @var string
     */
    protected $_query;
    /**
     * An array that holds where conditions 'fieldname' => 'value'
     *
     * @var array
     */
    protected $_where = array();
    /**
     * Dynamic type list for where condition values
     *
     * @var array
     */
    protected $_whereTypeList;
    /**
     * Dynamic type list for table data values
     *
     * @var array
     */
    protected $_paramTypeList;
    /**
     * Dynamic array that holds a combination of where condition/table data value types and parameter referances
     *
     * @var array
     */
    protected $_bindParams = array(''); // Create the empty 0 index

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $db
     */
    public function __construct($host, $username, $password, $db) {
        $this->_mysqli = new mysqli($host, $username, $password, $db)
            or die('There was a problem connecting to the database');

        $this->_mysqli->set_charset('utf8');

        self::$_instance = $this;
    }

    public static function getInstance() {
        return self::$_instance;
    }

    protected function reset() {
        $this->_where = array();
        $this->_bindParams = array(''); // Create the empty 0 index
        unset($this->_query);
        unset($this->_whereTypeList);
        unset($this->_paramTypeList);
    }

    /**
     * Pass in a raw query and an array containing the parameters to bind to the prepaird statement.
     *
     * @param string $query      Contains a user-provided query.
     * @param array  $bindParams All variables to bind to the SQL statment.
     *
     * @return array Contains the returned rows from the query.
     */
    public function rawQuery($query, $bindParams = null)
    {
        $this->_query = filter_var($query, FILTER_SANITIZE_STRING);
        $stmt = $this->_prepareQuery();

        if (is_array($bindParams) === true) {
            $params = array(''); // Create the empty 0 index
            foreach ($bindParams as $prop => $val) {
                $params[0] .= $this->_determineType($val);
                array_push($params, $bindParams[$prop]);
            }
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params));
        }
        $stmt->execute();
        $this->reset();

        return $this->_dynamicBindResults($stmt);
    }

    /**
     *
     * @param string $query   Contains a user-provided select query.
     * @param int    $numRows The number of rows total to return.
     *
     * @return array Contains the returned rows from the query.
     */
    public function query($query, $numRows = null)
    {
        $this->_query = filter_var($query, FILTER_SANITIZE_STRING);
        $stmt = $this->_buildQuery($numRows);
        $stmt->execute();
        $this->reset();

        return $this->_dynamicBindResults($stmt);
    }

    /**
     * A convenient SELECT * function.
     *
     * @param string  $tableName The name of the database table to work with.
     * @param integer $numRows   The number of rows total to return.
     *
     * @return array Contains the returned rows from the select query.
     */
    public function get($tableName, $numRows = null)
    {
        $this->_query = "SELECT * FROM $tableName";
        $stmt = $this->_buildQuery($numRows);
        $stmt->execute();
        $this->reset();

        return $this->_dynamicBindResults($stmt);
    }

    /**
     *
     * @param <string $tableName The name of the table.
     * @param array $insertData Data containing information for inserting into the DB.
     *
     * @return boolean Boolean indicating whether the insert query was completed succesfully.
     */
    public function insert($tableName, $insertData)
    {
        $this->_query = "INSERT into $tableName";
        $stmt = $this->_buildQuery(null, $insertData);
        $stmt->execute();
        $this->reset();

        return ($stmt->affected_rows > 0 ? $stmt->insert_id : false);
    }

    public function update($tableName, $tableData) {
        $this->_query = "UPDATE $tableName SET ";

        $stmt = $this->_buildQuery(null, $tableData);
        $stmt->execute();
        $this->reset();

        return ($stmt->affected_rows > 0);
    }

    public function delete($tableName, $numRows = null) {
        $this->_query = "DELETE FROM $tableName";

        $stmt = $this->_buildQuery($numRows);
        $stmt->execute();
        $this->reset();

        return ($stmt->affected_rows > 0);
    }

    public function where($whereProp, $whereValue) {
        $this->_where[$whereProp] = $whereValue;
        return $this;
    }


    public function getInsertId() {
        return $this->_mysqli->insert_id;
    }

    public function escape($str) {
        return $this->_mysqli->real_escape_string($str);
    }

    protected function _determineType($item) {
        switch (gettype($item)) {
            case 'NULL':
            case 'string':
                return 's';
                break;

            case 'integer':
                return 'i';
                break;

            case 'blob':
                return 'b';
                break;

            case 'double':
                return 'd';
                break;
        }
        return '';
    }

    protected function _buildQuery($numRows = null, $tableData = null) {
        $hasTableData = is_array($tableData);
        $hasConditional = !empty($this->_where);

        // Did the user call the "where" method?
        if (!empty($this->_where)) {

            // if update data was passed, filter through and create the SQL query, accordingly.
            if ($hasTableData) {
                $pos = strpos($this->_query, 'UPDATE');
                if ($pos !== false) {
                    foreach ($tableData as $prop => $value) {
                        // determines what data type the item is, for binding purposes.
                        $this->_paramTypeList .= $this->_determineType($value);

                        // prepares the reset of the SQL query.
                        $this->_query .= ($prop . ' = ?, ');
                    }
                    $this->_query = rtrim($this->_query, ', ');
                }
            }

            //Prepair the where portion of the query
            $this->_query .= ' WHERE ';
            foreach ($this->_where as $column => $value) {
                // Determines what data type the where column is, for binding purposes.
                $this->_whereTypeList .= $this->_determineType($value);

                // Prepares the reset of the SQL query.
                $this->_query .= ($column . ' = ? AND ');
            }
            $this->_query = rtrim($this->_query, ' AND ');
        }

        // Determine if is INSERT query
        if ($hasTableData) {
            $pos = strpos($this->_query, 'INSERT');

            if ($pos !== false) {
                //is insert statement
                $keys = array_keys($tableData);
                $values = array_values($tableData);
                $num = count($keys);

                // wrap values in quotes
                foreach ($values as $key => $val) {
                    $values[$key] = "'{$val}'";
                    $this->_paramTypeList .= $this->_determineType($val);
                }

                $this->_query .= '(' . implode($keys, ', ') . ')';
                $this->_query .= ' VALUES(';
                while ($num !== 0) {
                    $this->_query .= '?, ';
                    $num--;
                }
                $this->_query = rtrim($this->_query, ', ');
                $this->_query .= ')';
            }
        }

        // Did the user set a limit
        if (isset($numRows)) {
            $this->_query .= ' LIMIT ' . (int)$numRows;
        }

        // Prepare query
        $stmt = $this->_prepareQuery();

        // Prepare table data bind parameters
        if ($hasTableData) {
            $this->_bindParams[0] = $this->_paramTypeList;
            foreach ($tableData as $prop => $val) {
                array_push($this->_bindParams, $tableData[$prop]);
            }
        }
        // Prepare where condition bind parameters
        if ($hasConditional) {
            if ($this->_where) {
                $this->_bindParams[0] .= $this->_whereTypeList;
                foreach ($this->_where as $prop => $val) {
                    array_push($this->_bindParams, $this->_where[$prop]);
                }
            }
        }
        // Bind parameters to statment
        if ($hasTableData || $hasConditional) {
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($this->_bindParams));
        }

        return $stmt;
    }

    protected function _dynamicBindResults(mysqli_stmt $stmt) {
        $parameters = array();
        $results = array();

        $meta = $stmt->result_metadata();

        $row = array();
        while ($field = $meta->fetch_field()) {
            $row[$field->name] = null;
            $parameters[] = & $row[$field->name];
        }

        call_user_func_array(array($stmt, 'bind_result'), $parameters);

        while ($stmt->fetch()) {
            $x = array();
            foreach ($row as $key => $val) {
                $x[$key] = $val;
            }
            array_push($results, $x);
        }
        return $results;
    }

    protected function _prepareQuery() {
        if (!$stmt = $this->_mysqli->prepare($this->_query)) {
            trigger_error("Problem preparing query ($this->_query) " . $this->_mysqli->error, E_USER_ERROR);
        }
        return $stmt;
    }

    public function __destruct() {
        $this->_mysqli->close();
    }

    protected function refValues($arr) {
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = & $arr[$key];
            }
            return $refs;
        }
        return $arr;
    }
}
