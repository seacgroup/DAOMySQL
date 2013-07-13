DAOMySQL
========

A PHP DAO Class wrapper generator using MySQLi

This includes a base abstract class and an interface with static methods for Fetch, Store and Remove by Primary Key.

Hooks for Multirow Fetch, Store and Remove by Primary keys or by Index are also included and will be implemented within the next week.

Documentation and Generators for Caching, NoSQL and other PHP SQL libraries also planed.

Currently you can see a very simple implementation of this library in the test folder.

Basically this library creates php class code that extends the base class.  You can mix and match your database connections and tables as you see fit and then use the generated classes to access / manipulate your database.

The criteria use has been kept to very simple equates, this is because in most cases data is cached using hash keys which is made persistant with an underlying database.  Complex criteria just slows everything down and potentially makes data access slow and uncompatible for hash caching.

But since the access classes are generated they can be adapted or modified for your needs or more complex data manipulation.

The reason I am sharing this on GitHub is that it is a very basic tool that I have re-programmed way to many times for projects.  It is a great time saver and fullfills database half the database requirements for just about any app or webpage that using a database.
