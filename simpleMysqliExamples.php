<?php

/*
 * The following is a basic class extending 
 * the SimplyMysql class showing it's usage.
 * 
 * For the usage pattern, a new php class
 * is used for each table in the database 
 * we're working with. This allows for better
 * organization of our queries.
 * 
 */

class usersTableSimpleMysqliExample extends simpleMysqli
{
	public function __construct()
	{
		/*
		 * You don't need to pass a database configuration 
		 * in the following construct if you specify it
		 * for the database parameter in the simpleMysqli
		 * class we're extending.
		 * 
		 * Regardless, you will always need to call parent::__construct()
		 * in order to properly setup the database connection.
		 * 
		 */
		parent::__construct(array(
			'host' => '',
			'username' => '',
			'password' => '',
			'database' => ''
		));
		
		/*
		 * The table within our specified database we'll
		 * accessing and manipulating from this specific class.
		 */
		$this->table = 'users';
	}
	
	/*
	 * return a single row for our query.
	 */
	public function someCustomQuery($userId, $name)
	{
		$this->getSingle(
			"SELECT *
	 		 FROM users
	 		 WHERE id = :userId
	 		 AND name = :name",
			array(
				':userId' => $userId,
				':name' => $name
			)
		);
	}
	
	/*
	 * return multiple rows for our custom query
	 */
	public function getAllNewUsers()
	{
		$this->getMulti(
			"SELECT *
			 FROM users
			 WHERE created > UNIX_TIMESTAMP() - 86400"		
		);
	}
	
	/*
	 * returns multiple rows for out customer query
	 */
	public function getAllNewGenderUsers($gender)
	{
		$this->getMulti(
			"SELECT *
			 FROM users
			 WHERE gender = :gender",
			array(
				':gender' => $gender
			)		
		);
	}
}

/*
 * 
 * Example usage.
 * 
 */
class exampleUsage
{
	public static function someExample()
	{
		$usersTableSimpleMysqliExample = new usersTableSimpleMysqliExample();
		
		// insert example
		$usersTableSimpleMysqliExample->insert(array(
			'firstName' => 'Braydon',
			'lastName' => 'Batungbacal',
			'someData' => json_encode(array('hello', 'hello'))
		));
		
		// update example
		$usersTableSimpleMysqliExample->update('userId', 24, array(
			'firstName' => 'Some New Name',
			'lastName' => 'foo',
			'someOtherColumn' => 'bar'
		));
		
		// delete example
		$usersTableSimpleMysqliExample->preparedQuery(
			"DELETE
			 FROM users
			 WHERE id = :userId", 
			array(
				':userId' => 24	
			)
		);
	}
}