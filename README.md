# PDOQueryBuilder

A minimalistic SQL query builder for PHP that abstracts the binding process by allowing gradual addition and binding of SQL clauses with values. It promotes verbose, typed, safe, and readable SQL query construction through a step-by-step add & bind approach.

## Features

- **Abstraction of Binding Process:** Automatically detects and binds named placeholders in SQL queries.
- **Method Chaining:** Supports fluent interfaces for building queries in a readable manner.
- **Safety:** Utilizes prepared statements to protect against SQL injection.
- **Type Enforcement:** Binds parameters with appropriate PDO types based on PHP variable types.
- **Reusability:** Allows reuse of prepared statements with different bindings for efficiency.

## Requirements

- PHP 7.4 or higher
- PDO extension enabled
- Supported PDO drivers (e.g., MySQL, PostgreSQL)

## Installation

You can include the `PDOQueryBuilder` class in your project manually or via Composer.

### Manual Installation

1. **Download the Class:**
   - Save the `PDOQueryBuilder.php` file to your project's directory.

2. **Include the Class in Your Project:**
```php
   require_once 'path/to/PDOQueryBuilder.php';
   ```
3. **Have the PDO initiated**
```php   
    // Initialize PDO
    $pdo = new PDO('mysql:host=localhost;dbname=your_db;charset=utf8mb4', 'username', 'password', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
   ```
    
## Minimal usage
```php
    // Build and execute an UPDATE query
    PDOQueryBuilder::instance($pdo)
        ->add(
            'UPDATE users SET name = :name, email = :email WHERE id = :id',
            [
                ':name'  => 'John Doe',
                ':email' => 'john.doe@example.com',
                ':id'    => 42,
            ]
        )
        ->execute();
     echo "User updated successfully.";
```

## Step-by-Step Construction
   ```php
       $query = new PDOQueryBuilder($pdo);
       $query->add('UPDATE users SET');
       $query->add('name = :name', 'John Doe');
       $query->add(', email = :email', 'john.doe@example.com');
       $query->add('WHERE id = :id', 42);
       $stmt = $query->execute();
       echo "User updated successfully.";
```

## Reusing the Prepared Statement
```php
$queryBuilder = PDOQueryBuilder::instance($pdo)
        ->add('INSERT INTO products (name, price, quantity)')
        ->add('VALUES (:name, :price, :quantity)', [
            ':name'     => 'Laptop',
            ':price'    => 999.99,
            ':quantity' => 10,
        ])
        ->execute();

    // Reuse the prepared statement for another insert
    $queryBuilder->execute([
        ':name'     => 'Smartphone',
        ':price'    => 499.99,
        ':quantity' => 25,
    ]);

    echo "Products inserted successfully.";
```


### What It Aims to Resolve

Working with PDO for dynamic SQL construction can lead to redundant code and increased complexity, especially when constructing queries and binding values conditionally. This process often requires:
 1.	Separate Conditionals for SQL and Binding: SQL construction and binding are handled in separate steps, leading to duplicate logic.
 2.	Verbose and Hard-to-Read Code: Multiple conditional checks make the code unnecessarily long and harder to maintain.
 3.	Higher Error Risk: Repeating logic increases the chance of mismatches or missing bindings.

PDOQueryBuilder addresses these issues by consolidating the SQL construction and parameter binding into a single, streamlined process.

Example Scenario: Dynamic UPDATE Query

#### Without PDOQueryBuilder: Separate Steps for SQL Construction and Binding

In a traditional PDO setup, a query with dynamic conditions might involve building the SQL in one pass, then conditionally binding values in a second pass.

```php
      $id = 1;
      $data = [
          'name'  => 'John Doe',            // Always update
          'email' => 'john@example.com',    // Conditionally update
          // 'age' => 30,                    // Optional update
      ];
      
      // Step 1: Build the SQL string with conditional logic
      $sql = "UPDATE users SET name = :name";
      
      if (isset($data['email'])) {
          $sql .= ", email = :email";
      }
      
      if (isset($data['age'])) {
          $sql .= ", age = :age";
      }
      
      $sql .= " WHERE id = :id";
      
      // Step 2: Prepare the statement
      $stmt = $pdo->prepare($sql);
      
      // Step 3: Bind the values conditionally
      $stmt->bindValue(':name', $data['name']);
      
      if (isset($data['email'])) {
          $stmt->bindValue(':email', $data['email']);
      }
      
      if (isset($data['age'])) {
          $stmt->bindValue(':age', $data['age']);
      }
      
      $stmt->bindValue(':id', $id);
      
      $stmt->execute();
      
      echo "User updated successfully.";
```

#### Issues with the Above Approach:
 1.	Double Conditionals: Each optional field requires separate if statements for both SQL string construction and parameter binding, making the code verbose and repetitive.
 2.	Difficult to Maintain: Adding or removing fields means updating two separate sections, increasing the chance of errors.
 3.	Readability: This structure is harder to follow and maintain, especially as the number of conditions grows.

#### With PDOQueryBuilder: Streamlined Add & Bind Approach

With PDOQueryBuilder, we can consolidate SQL construction and parameter binding into a single step, reducing redundancy and making the code more readable.
```php
$id = 1;
$data = [
    'name'  => 'John Doe',            // Always update
    'email' => 'john@example.com',    // Conditionally update
    // 'age' => 30,                    // Optional update
];

    $queryBuilder = PDOQueryBuilder::instance($pdo)
        ->add('UPDATE users SET name = :name', $data['name'] );

    // Conditionally add SQL clauses and bind parameters
    if (isset($data['email'])) {
        $queryBuilder->add(', email = :email', $data['email']);
    }

    if (isset($data['age'])) {
        $queryBuilder->add(', age = :age', $data['age']);
    }

    $queryBuilder->add('WHERE id = :id', $id);
    $stmt = $queryBuilder->execute();

    echo "User updated successfully.";
```
### Advantages of Using PDOQueryBuilder:

 1. Single Conditional Check: Each condition is checked only once, eliminating the need for separate conditionals for SQL and binding.
 2. Simplified Code: The query builder’s method chaining approach makes the code cleaner and easier to read.
 3. Lower Maintenance: Adding or removing fields requires minimal changes, reducing the risk of errors.
 4. Reduced Error Potential: With SQL construction and parameter binding in a single step, the chances of missing or mismatching bindings are minimized.
