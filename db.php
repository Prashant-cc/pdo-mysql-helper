<?php

class db
{

    /** @var PDO null */
    private $connection = null;

    function __construct($dsn, $username, $password)
    {
        $this->connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    /**
     * Update the given table with an array of data.
     * id is assumed to be the primary key of the table.
     * If the table doesn't have a primary key then this
     * method won't work.
     *
     * @param $table
     * @param array $data
     * @param $id
     * @throws Exception
     */
    public function update($table, array $data, $id)
    {
        $columns = $this->getColumns($table);
        $primaryKey = $this->getPrimaryKey($table);
        if ($primaryKey === null) {
            throw new Exception("Attempt to update table ({$table}) with no primary key.");
        }

        list($matchedData, $fields) = $this->buildFields($data, $columns);
        $updates = $fields['updates'];

        $updateSql = implode(', ', $updates);
        $sql = "update `{$table}` set {$updateSql} where `{$primaryKey}` = :{$primaryKey}";
        $statement = $this->connection->prepare($sql);
        $statement->bindParam(":{$primaryKey}", $id, $this->getPdoType($id));
        $this->bindParameters($statement, $matchedData);

        $statement->execute();
    }

    /**
     * Inserts an array of data into the provided table.
     *
     * @param $table
     * @param $data
     * @return string
     */
    public function insert($table, array $data)
    {
        $columns = $this->getColumns($table);

        list($matchedData, $fields) = $this->buildFields($data, $columns);
        $inserts = $fields['inserts'];
        $fields = $inserts['fields'];
        $values = $inserts['values'];

        $fieldsSql = implode(', ', $fields);
        $valuesSql = implode(', ', $values);
        $sql = "insert into `{$table}` ({$fieldsSql}) values ({$valuesSql})";
        $statement = $this->connection->prepare($sql);
        $this->bindParameters($statement, $matchedData);

        $statement->execute();
        return $this->connection->lastInsertId();
    }

    /**
     * Returns an array of rows for the given query.
     *
     * @param $sql
     * @return array
     */
    public function all($sql)
    {
        $statement = $this->connection->query($sql);
        $records = $statement->fetchAll();
        return $records;
    }

    /**
     * Returns a single row.
     *
     * @param $sql
     * @return mixed|null
     */
    public function one($sql)
    {
        $record = null;
        $statement = $this->connection->query($sql);
        if ($statement === false) {
            return $record;
        }
        $record = $statement->fetch();
        return $record;
    }

    /**
     * Returns a single column / value.
     *
     * @param $sql
     * @return mixed|null
     */
    public function single($sql)
    {
        $record = null;
        $statement = $this->connection->query($sql);
        $record = $statement->fetch(PDO::FETCH_NUM);
        if ($statement === false) {
            return $record;
        }
        $record = $record[0];
        return $record;
    }

    /**
     * Returns the pdo type for use in binding.
     *
     * @param $value
     * @param null $type
     * @return int|null
     */
    private function getPdoType($value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        return $type;
    }

    /**
     * Binds an array of data to a pdo prepared statement.
     *
     * @param $statement
     * @param $matchedData
     */
    private function bindParameters($statement, $matchedData)
    {
        foreach ($matchedData as $field => $value) {
            $statement->bindParam(":{$field}", $value, $this->getPdoType($field));
            unset($value); // if not unset then bindings will fail
        }
    }

    /**
     * Storage for columns already fetched.
     *
     * @var array
     */
    private static $columns = [];

    /**
     * Retrieves column data for the provided table.
     *
     * @param $table
     * @return mixed
     * @throws Exception
     */
    private function getColumns($table)
    {
        if (isset(static::$columns[$table])) {
            return static::$columns[$table];
        }
        $sql = "show columns from {$table}";
        $columns = $this->all($sql);
        if ($columns === false) {
            throw new Exception("Unable to `show columns` for table ({$table}).  Does the table exist?");
        }
        static::$columns[$table] = $columns;
        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                $primaryKey = $column['Field'];
                static::$primaryKeys[$table] = $primaryKey;
                break;
            }
        }
        return static::$columns[$table];
    }

    /**
     * Storage for primary keys already fetched.
     *
     * @var array
     */
    private static $primaryKeys = [];

    /**
     * Retrieves the primary key field name for the provided table.
     *
     * @param $table
     * @return null
     */
    private function getPrimaryKey($table)
    {
        if (isset(static::$primaryKeys[$table])) {
            return static::$primaryKeys[$table];
        }
        return null;
    }

    /**
     * Returns update statements and field - value pairs [ for inserts ].
     *
     * @param $data
     * @param $columns
     * @return array
     */
    private function buildFields($data, $columns)
    {
        $matchedData = [];
        $updates = [];
        $fields = [];
        $values = [];
        foreach ($columns as $column) {
            $field = $column['Field'];
            $canBeNull = ($column['Null'] === 'YES');
            if (array_key_exists($field, $data)) {
                if (isset($data[$field])) {
                    $matchedData[$field] = $data[$field];
                } else {
                    if ($canBeNull) {
                        $matchedData[$field] = null;
                    } else {
                        $matchedData[$field] = '';
                    }
                }
                $updates[] = "`{$field}` = :{$field}";
                $fields[] = "`{$field}`";
                $values[] = ":{$field}";
            }
        }
        return array($matchedData, [
            'updates' => $updates,
            'inserts' => [
                'fields' => $fields,
                'values' => $values
            ]
        ]);
    }

}
