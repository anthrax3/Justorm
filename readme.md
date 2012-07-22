Justorm - Simple Codeigniter ORM
================================

This is short and incomplete documentation of Justorm library. More coherent and full description of Justorm is coming soon.

About
-----

Justorm is a library for Codeigniter that provides you with simple ORM functionality on top of the framework's native database driver.
The library consists of migration and model classes and is a sufficient solutions for mid-size projects, which require easy migrations and models generation, but do not need heavy multifunctional Doctrine/Propel libraries.
Unfortunately, for now it definitely works only with MySQL database because of Codeigniter drivers and some types of fields. The library is under development and hopefully will be able to work with other database types soon.

Migrations
----------

Justorm Migrations are based on YAML descriptions from `/migrations` directory. The way it works is quite similar with [Codeignier build-in migration library](http://codeigniter.com/user_guide/libraries/migration.html) and actually includes some of it's code.
The differences are easy of use YAML descriptions of the database and auto-generation of the models according to the current database structure.

Simple example of migration application

	// Loading the library (can be done in autoload.php)
	$this->load->library('justorm');
	// Applying migration version #2
	$this->justorm->migrate(2);

Migration class uses [YAML processing library](https://github.com/symfony/Yaml) from [Symfony Framework](https://github.com/symfony/symfony), but can be replaces by any other YAML library.
Each migration file name should start with 4-digit migrations version and include any description of the file, separated by underscore.

Example:

	0001_usertable.yml

Examples of YAML descriptions:

### Create table

	---
	version: 1						// Version of the mi
	model: User						// Model name, in most cases it is just singular form of table name.
	up:								// UP action
	  create_table:					
	    Users:						// Table name
	      columns:					// Column description
	        email:
	          type: string
	          primary: true
	        username:
	          type: string(255)
	        password:
	          type: string(255)
	        contact_id:
	          type: integer
	down:							// DOWN action
	  drop_table: User

### Rename table

	---
	version: 2
	model: User
	up:
	  rename_table:
	    Users: Members				// Rename from Users to Members
	down:
	  rename_table:
	    Members: Users				// Rename from Members to Users

### Add fields

	---
	version: 3
	model: User
	up:
	  add_field:
	    Members:
	      columns:
	        created_at:
	          type: timestamp
	        updated_at:
	          type: timestamp
	down:
	  delete_field:
	    Members:
	      columns: [created_at, updated_at]		// Can be set as array of fields [] or name of one field

### Modify fields

	---
	version: 4
	model: User
	up:
	  modify_field:
	    Members:
	      columns:
	        contact_id:
	          type: string
	down:
	    modify_field:
	      Members:
	        columns:
	          contact_id:
	            type: int


### Rename field

	---
	version: 5
	model: User
	up:
	  rename_field:
	    Members:
	      username: name
	down:
	  rename_field:
	    Members:
	      name: username

### Field types

Justorm supports all the native Codeigniter field types together with its own implementation.
The list below shows correspondence in Justorm and Codeigniter/MySQL implementations. You can use any of these styles of migration descriptions in your projects.

Type:

* `string` -> `varchar`
* `integer` -> `int`
* `id` -> `int`, `primary key`, `length 16`

Length:

* `length` -> `constraint`

Autoincrement

* `autoincrement` -> `auto_increment`

Null

* `null: false` == `notnull: true`

Values (for field types: enum and set)

* `values` -> `constraint`

Examples of YAML descriptions can be found in `/migrations` directory.

Models
------

Justorm models consists of 3 files:

* /application/libraries/Justorm/JO_model.php - unified (base) model that provides basic methods like `find()`, `save()` and `delete()` objects.
* /application/models/Justorm/JO_modelname.php - this file generates after each migration and contains table structure description to allow make methods work. The class in the file extends JO_model class.
* /application/models/Modelname_model.php - this files generates contains empty class by default. It designed to allow developers add custom methods of a model.

Base model includes following methods:

* find($id) - find an object in database by its primary key.
* where($arrayOfParameters) - find an object or group of objects by parameter/s.
* count() - the number of objects in the database
* count($arrayOfParameters) - the number of object in database with specified parameter/s
* save() - save new or update existing object.
* delete() - delete an object from the database.
* set($field, $value) - set a parameter of an object
* get($field) - get a parameter of an object
* getArray() - get all the parameters (except primary key) of an object as an array
* setArray($array) - set all the parameters (except primary key) of an object by array
* setObject($primary_key, $array) - set all the parameters of an object
* reset() - set all the parameters of an object to null 

Todo
----

- Support of all the database that supports Codeigniter
- Console interface for migration management