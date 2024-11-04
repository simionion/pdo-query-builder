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


