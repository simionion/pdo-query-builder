<?php

/**
 * A minimalistic SQL query builder that abstracts the binding process by gradually adding
 * and binding SQL clauses with their corresponding values using an add & bind approach.
 *
 * Features:
 *  - Enforces verbose, typed, safe, and readable SQL query construction.
 *  - Supports step-by-step building of SQL queries for better maintainability.
 *  - Utilizes named placeholders for binding values; positional placeholders ("?") are not supported.
 *
 * ### Examples
 *
 * **Minimal Usage:**
 * ```
 * PDOQueryBuilder::instance($db)
 *     ->add(
 *         'UPDATE table SET column1 = :column1, column2 = :column2 WHERE id = :id',
 *         [
 *             ':column1' => 'value1',
 *             ':column2' => 'value2',
 *             ':id' => 1
 *         ]
 *     )
 *     ->execute();
 * ```
 *
 * **Step by Step Construction:**
 * ```
 * $query = new PDOQueryBuilder($pdo);
 * $query->add('UPDATE table SET');
 * $query->add('column1 = :column1', 'value1');
 * $query->add(', column2 = :column2', 'value2');
 * $query->add(', column3 = :column3', 'value3'); //!comma
 * $query->add('WHERE')
 * $query->add(id = :id', 1);
 * $stmt = $query->execute();
 * 
 * ```
 * 
 * **Reusing the Prepared Statement:**
 * ```
 * $stmt = $query->execute([
 *     ':column1' => 'new_value1',
 *     ':column2' => 'new_value2'
 * ]);
 * ```
 */
class PDOQueryBuilder
{
    /**
     * @var PDO The PDO instance for database connection.
     */
    private PDO $db;

    /**
     * @var array The array of SQL clauses.
     */
    private array $clauses = [];

    /**
     * @var array The array of bind parameters (placeholders).
     */
    private array $parameters = [];

    /**
     * @var PDOStatement|null The PDO statement from initial execute.
     */
    private PDOStatement|null $statement;

    /**
     * QueryBuilder constructor.
     *
     * @param PDO $db The PDO instance for database connection.
     *
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Alternative to the constructor for direct class to method chaining.
     * 
     * ### Example
     * ```
     * $stmt = PDOQueryBuilder::instance($pdo)->add('SELECT * FROM table WHERE id = :id', 1)->execute();
     * ```
     * @param PDO $db The PDO instance for database connection.
     * @return PDOQueryBuilder The new instance of the QueryBuilder.
     */
    public static function instance(PDO $db): self
    {
        return new self($db);
    }

    /**
     * Appends the clause to the SQL query.
     * If placeholders are provided, they will be bound to their values.
     * 
     * 
     *
     * @param string $clause The query with placeholders to bind.
     * @param mixed $value The value to bind.
     * @param int|null $type The PDO parameter type.
     * @return PDOQueryBuilder The QueryBuilder instance for method chaining.
     * 
     * ### Examples
     * ```
     * // No bind - appends raw SQL fragment 
     * $queryBuilder->add('UPDATE table SET')
     * // Simple single bind - binds :column1 => 'value1'
     * $queryBuilder->addBind('column1 = :column1 ,', 'value1')
     * // Multiple binds :a => 1, :b => 2, :c => 3
     * $queryBuilder->addBind('column2 IN (:a, :b, :c) ,', [1, 2, 3])
     *```
     */
    final public function add(string $clause, mixed $value = null, int|null $type = null): self
    {
        $this->clauses[] = $clause;
        preg_match_all('/(:\w+)/', $clause, $matches);

        foreach ($matches[0] as $i => $placeholder) {
            if (isset($this->parameters[$placeholder])) {
                throw new PDOException("Duplicate placeholder: {$placeholder}");
            }
            $bindValue = is_array($value) ? ($value[$placeholder] ?? $value[$i] ?? null) : $value;
            $this->parameters[$placeholder] = ['value' => $bindValue, 'type' => $type ?? $this->getType($bindValue)];
        }

        return $this;
    }

    /**
     * @param mixed $value
     * @return array
     */
    private function getType(mixed $value): int
    {
        $types = [
            'string' => PDO::PARAM_STR,
            'integer' => PDO::PARAM_INT,
            'boolean' => PDO::PARAM_BOOL,
            'NULL' => PDO::PARAM_NULL,
        ];

        return $types[gettype($value)] ?? PDO::PARAM_STR;
    }

    /**
     * Execute the built SQL query.
     * The prepared statement can be executed multiple times with different values.
     * The $newBindings is an array like [':placeholder1' => 'value1', ':placeholder2' => 'value2'].
     *
     * @param array $newBindings Optional new bindings to re-use the prepared query.
     * @return PDOStatement The executed PDO statement.
     * 
     * ### Examples
     * 
     * ```
     * PDOQueryBuilder::instance($db)->add('INSERT INTO test (name, age, is_active) VALUES')
     * ->add('(:name, :age, :is_active)', [':name' => 'Alice', ':age' => 30, ':is_active' => true])
     * ->execute();
     * 
     * // Reusing the prepared statement
     * $queryBuilder->execute([':name' => 'George', ':age' => 40]);
     * $queryBuilder->execute([':name' => 'Hannah', ':age' => 32]);
     * ```
     */
    final public function execute(array $newBindings = []): PDOStatement
    {
        // Construct the SQL query
        $this->statement ??= $this->db->prepare($this->getQueryString());

        foreach ($this->parameters as $placeholder => $param) {
            // Get the value and type from the new bindings or use the initially provided value.
            $value = $newBindings[$placeholder]['value'] ?? $newBindings[$placeholder] ?? $param['value'];
            // Get the type from the new bindings or use the initially detected type.
            $type = $newBindings[$placeholder]['type'] ?? (isset($newBindings[$placeholder]) ? $this->getType($value) : $param['type']);

            $this->statement->bindValue($placeholder, $value, $type);
        }

        if (!$this->statement->execute()) {
            [$state, $code, $message] = $this->statement->errorInfo();
            $errorMessage = "SQLSTATE: {$state}, Error Code: {$code}, Message: {$message}";
            throw new PDOException($errorMessage);
        }

        return $this->statement;
    }

    /**
     * Get the built SQL query string.
     *
     * @return string The SQL query string.
     */
    final public function getQueryString(): string
    {
        return str_replace(['  ', ' ,'], [' ', ','], implode(' ', $this->clauses));
    }
}
