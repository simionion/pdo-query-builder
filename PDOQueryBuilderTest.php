<?php

include 'PDOQueryBuilder.php';

use PHPUnit\Framework\TestCase;

class PDOQueryBuilderTest extends TestCase
{
    /**
     * @var PDO|null
     */
    private PDO|null $pdo;

    /**
     * Test: Basic Insert Functionality
     */
    public function testBasicInsert()
    {
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO test (name, age, is_active) VALUES')
            ->add('(:name, :age, :is_active)', [
                ':name' => 'Alice',
                ':age' => 30,
                ':is_active' => true
            ]);
        $stmt = $queryBuilder->execute();

        // Verify that the data was inserted
        $stmt = $this->pdo->query("SELECT * FROM test WHERE name = 'Alice'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
        $this->assertEquals(1, $result['is_active']);
    }

    /**
     * Test: Add Method with Single Placeholder
     */
    public function testAddWithSinglePlaceholder()
    {
        $this->pdo->exec("INSERT INTO test (name, age) VALUES ('Bob', 25)");

        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('SELECT * FROM test WHERE name = :name', 'Bob');
        $stmt = $queryBuilder->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($result);
        $this->assertEquals('Bob', $result['name']);
        $this->assertEquals(25, $result['age']);
    }

    /**
     * Test: Add Method with Multiple Placeholders
     */
    public function testAddWithMultiplePlaceholders()
    {
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO test (name, age) VALUES (:name, :age)', [
                ':name' => 'Charlie',
                ':age' => 35
            ]);
        $stmt = $queryBuilder->execute();

        $stmt = $this->pdo->query("SELECT * FROM test WHERE name = 'Charlie'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($result);
        $this->assertEquals('Charlie', $result['name']);
        $this->assertEquals(35, $result['age']);
    }

    /**
     * Test: Exception on Duplicate Placeholders
     */
    public function testDuplicatePlaceholdersException()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Duplicate placeholder: :name');

        PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO test (name) VALUES (:name)', 'Dave')
            ->add('AND name = :name', 'David');
    }

    /**
     * Test: Reusing Prepared Statements
     */
    public function testReusingPreparedStatements()
    {
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO test (name, age) VALUES (:name, :age)', [
                ':name' => ':name',
                ':age' => ':age'
            ]);

        // First execution
        $queryBuilder->execute([':name' => 'George', ':age' => 40]);

        // Second execution with different values
        $queryBuilder->execute([':name' => 'Hannah', ':age' => 32]);

        // Verify both records inserted
        $stmt = $this->pdo->query("SELECT name, age FROM test WHERE name IN ('George', 'Hannah')");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $results);
    }

    /**
     * Test: Handling of Various Data Types
     */
    public function testHandlingVariousDataTypes()
    {
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO test (name, age, is_active) VALUES (:name, :age, :is_active)', [
                ':name' => 'Isabel',
                ':age' => 28,
                ':is_active' => false
            ]);
        $stmt = $queryBuilder->execute();

        $stmt = $this->pdo->prepare("SELECT name, age, is_active FROM test WHERE name = :name");
        $stmt->execute([':name' => 'Isabel']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Isabel', $result['name']);
        $this->assertEquals(28, $result['age']);
        $this->assertEquals(0, $result['is_active']); // false stored as 0
    }

    /**
     * Test: Edge Cases with Empty Strings and Null Values
     */
    public function testEmptyStringAndNullValues()
    {
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO test (name, age) VALUES (:name, :age)', [
                ':name' => '',
                ':age' => null
            ]);
        $stmt = $queryBuilder->execute();

        $stmt = $this->pdo->prepare("SELECT name, age FROM test WHERE name = :name");
        $stmt->execute([':name' => '']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('', $result['name']);
        $this->assertNull($result['age']);
    }

    /**
     * Test: Exception Handling When SQL Execution Fails
     */
    public function testExecuteExceptionHandling()
    {
        $this->expectException(PDOException::class);

        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO nonexistent_table (name) VALUES (:name)', 'Jack');
        $queryBuilder->execute();
    }

    /**
     * Test: getQueryString Method
     */
    public function testGetQueryString()
    {
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('SELECT * FROM test WHERE name = :name', 'Karen');

        $expectedQuery = 'SELECT * FROM test WHERE name = :name';
        $this->assertEquals($expectedQuery, $queryBuilder->getQueryString());
    }

    /**
     * Test: Handling of Special Characters in Bindings
     */
    public function testSpecialCharactersInBindings()
    {
        $specialString = "O'Reilly \\ Company";
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('INSERT INTO test (name) VALUES (:name)', $specialString)
            ->execute();

        $stmt = $this->pdo->prepare("SELECT name FROM test WHERE name = :name");
        $stmt->execute([':name' => $specialString]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals($specialString, $result['name']);
    }

    /**
     * Test: Edge Case with No Placeholders
     */
    public function testAddWithoutPlaceholders()
    {
        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('SELECT * FROM test');
        $queryBuilder->execute();

        $this->assertEquals('SELECT * FROM test', $queryBuilder->getQueryString());
    }

    /**
     * Test: Complex Query Construction
     */
    public function testComplexQueryConstruction()
    {
        // Insert data
        $this->pdo->exec("INSERT INTO test (id, name, age) VALUES (1, 'Paul', 29)");

        $queryBuilder = PDOQueryBuilder::instance($this->pdo)
            ->add('SELECT * FROM test WHERE')
            ->add('name = :name', 'Paul')
            ->add('AND age = :age', 29)
            ->add('ORDER BY id DESC');

        $stmt = $queryBuilder->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($result);
        $this->assertEquals('Paul', $result['name']);
        $this->assertEquals(29, $result['age']);
    }

    /**
     * Test: Transactions with PDOQueryBuilder
     */
    public function testTransactions()
    {
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec("INSERT INTO test (id, name) VALUES (1, 'OldName')");

            $queryBuilder = PDOQueryBuilder::instance($this->pdo)
                ->add('UPDATE test SET name = :name', 'Quincy')
                ->add('WHERE id = :id', 1)
                ->execute();

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->fail('Transaction failed: ' . $e->getMessage());
        }

        // Verify the update
        $stmt = $this->pdo->query("SELECT name FROM test WHERE id = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Quincy', $result['name']);
    }

    /**
     * Set up an in-memory SQLite database and create the necessary table(s).
     */
    protected function setUp(): void
    {
        // Create an in-memory SQLite database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create a test table
        $this->pdo->exec(
            "
            CREATE TABLE test (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                age INTEGER,
                is_active BOOLEAN
            )
        "
        );
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Close the PDO connection
        $this->pdo = null;
    }
}
