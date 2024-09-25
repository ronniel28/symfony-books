# Symfony Book Management Application

This is a Symfony-based book management application that allows authenticated users to manage a list of books. The app provides functionality for creating, editing, deleting, exporting books to CSV, and importing books via a CSV file.

## Features

- **Book Creation**: Add new books with a title, author, ISBN, published date, and description.
- **Book Editing**: Update book details.
- **Book Deletion**: Remove books from the list.
- **Export to CSV**: Export the list of books to a CSV file.
- **Import from CSV**: Upload a CSV file to import books.
- **Authentication**: All operations require a logged-in user.

## Prerequisites

Before setting up the application, make sure you have the following installed:

- PHP 8.1+
- Composer
- Symfony CLI (Optional but recommended)
- Docker (for setting up MariaDB using Docker)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/ronniel28/symfony-books.git
cd symfony-books
```
### 2. Install Dependencies
#### Install PHP dependencies:
```bash
composer install
```
### 3. Configure the Environment
#### Create a copy of the .env file for your environment variables:
```bash
cp .env .env.local
```
### Configure your database connection in the .env.local file:
```bash
DATABASE_URL="mysql://root:root@127.0.0.1:3306/symfony-book?serverVersion=mariadb-10.8.3&charset=utf8mb4"
```

### 4. Set Up the Database
#### Create the database and run the migrations to set up the schema:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```
#### (Optional) Load test data into the database:
```bash
php bin/console doctrine:fixtures:load
```

### 5. Run the Application
#### Start the Symfony development server:
```bash
symfony server:start
```

## Using Docker for MariaDB (Optional)

#### If you want to run MariaDB using Docker, use the following docker-compose.yml:

```yaml
services:
  mysql:
    image: mariadb:10.8.3
    # Uncomment below when on Mac M1
    platform: linux/arm64/v8
    command: --default-authentication-plugin=mysql_native_password
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
    ports:
      - 3306:3306

  mysql_test:
    image: mariadb:10.8.3
    # Uncomment below when on Mac M1
    platform: linux/arm64/v8
    command: --default-authentication-plugin=mysql_native_password
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
    ports:
      - 3307:3306

  adminer:
    image: adminer
    restart: always
    ports:
      - 8080:8080
```

```bash
docker-compose up -d
```

## Running Tests
#### This application uses PHPUnit for testing, including unit tests for the main functionalities (authentication of user,book creation, editing and deletion).

### 1. Set Up the Test Database
#### Create the test database:
```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:update --force --env=test
```

### 2. Run Tests
#### Run the tests using PHPUnit:
```bash
php bin/phpunit
```

## Available Routes
#### Here are some of the key routes available in the application:

- `/` - View the list of books
- `/book/add` - Create a new book
- `/book/{id}/edit` - Edit a specific book
- `/book/{id}/delete` - Delete a specific book 
- `/books/export?export_all=1` - Export the list of books to a CSV file
- `/books/export?search={searchTerm}&sortBy={sortBy}&direction={direction}` - Export the currently filtered results

## Authentication

- All operations require a logged-in user.
- You can add users via the Symfony UserFixtures, or manually insert them into the database.

## Troubleshooting
#### If you encounter issues, try the following:

- Ensure that the database is correctly set up by running migrations and loading fixtures.
- Check that the .env.local file is properly configured with your database connection.
- Ensure all required services (PHP, MySQL/MariaDB) are running.
- If using Docker, ensure the containers are up with docker-compose up.
















