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
