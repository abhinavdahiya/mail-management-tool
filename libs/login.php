<?php
/**
 * this library will hold the function and classes for login functionality 
 * few of the functins can be from OWASP PHPsec project
 * dependency: db library, error library
 */
if(!isset($checkVar)) 
{
	header("location: ../../index.php");
	exit;
}

class InvalidUsername extends Exception {};
class InvalidPassword extends Exception {};

class login 
{
	/** 
	 * username and password as sent to login library
	 */
	private $username = null;
	private $password = null;
	
	/**
	 * primary key for table for username and passwords
	 */
	private $id;	
	
	/**
	 * Maimum wrong attempts after which captcha should be 
	 * thrown to users
	 */
	public static $maxInvalidAttempts = 5;
	
	/**
	 * time after which a session should expire if not used
	 */
	public static $sessionTimeout = 1200;	//20 minutes
	
	public static function getIP()
	{
	 		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
		    {
		        if (array_key_exists($key, $_SERVER) === true)
		        {
		            foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip)
		            {
		                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
		                {
		                    return $ip;
		                }
		            }
		        }
		    }
	}
	/**
	 * Function to increase the count of 
	 * login failure count by one in session
	 * add ip logging feature rather than session
	 */
	public static function increaseFailureCount()
	{
		if(!isset($_SESSION['failure']))
		{
			$_SESSION['failure'] = 1;
			return;	
		}
		$_SESSION['failure'] = $_SESSION['failure'] + 1;
		/*
		 $ip = getIP();
		 dbase::start_connection();
		 $query = mysql_query("SELECT id FROM `brute_forcelog` WHERE `ip-address`='$ip'") or throw new dbError("");
		 if(mysql_num_rows($query)>0)
		 {
		 	$row = mysql_fetch_array($query);
		 	$query = mysql_query("UPDATE brute_forcelog SET `count`=`count`+1 WHERE `id`=".$row['id']."");
		 } 
		 else
		 {
		 	$query = mysql_query("INSERT brute_forcelog (`ip-address`,`count`) VALUES ('$ip',1)");
		 }
		 */
	}
	
	/**
	 * function to return filtered data 
	 * after passing it through a regex filter
	 */
	private static function filter($a,$b)
	{
		return preg_replace($a,"",$b);
	}
	
	/**
	 * function to check if username and password matches
	 * for user login 
	 * assuming database to be connected
	 * @param: username and password
	 * @return int
	 * -1 for error state, 0 for username doesnot match, 1 for password does not match, 2 for sucess
	 */
	public static function authenticate($user,$pass)
	{
		/**
		 * code to filter incoming username and password 
		 */
		$uname = self::filter("/[^A-Za-z0-9]+/",$user);			//for username
		$pwd = self::filter("/[^A-Za-z0-9-_@.!&()]+/",$pass);	//for password
		
		//compare filtered and unfiltered data for detecting possible attacks	
		if( strcmp($uname,$user) )
		{
			throw new InvalidUsername('Username is of invalid format');
			return -1;
		}	
		if( strcmp($pass,$pwd) )
		{
			throw new InvalidPassword('Password is of invalid format');
			return -1;
		}
		//executing query to retrieve password for authentication
		$query  = mysql_query("SELECT password FROM `admin` WHERE username = '" .$uname ."' LIMIT 1;");
		if( !$query )
		{
			throw new dbError(mysql_error());
			return -1;
		}
		$status = 0;
		if(mysql_num_rows($query))
		{
			$status++;
			$row = mysql_fetch_array($query);
			if( $pwd == $row['password'] )
			{
				$status++;
			}
		}
		unset($row);
		unset($query);
		return $status;
	}
	 
};

/**
 * function to check if session data already exists 
 * or to return flase after unsetting those session data
 */
function doesSessionAlreadyExists()
{
	if( isset($_SESSION[username_key]) && isset($_SESSION[password_key]) ) return true;
	if(isset($_SESSION[username_key])) unset($_SESSION[username_key]);
	if(isset($_SESSION[password_key])) unset($_SESSION[password_key]);
	return false;
}

/**
 * function to unset session data related to login information
 */
function clearSession()
{
	if(isset($_SESSION[username_key])) unset($_SESSION[username_key]);
	if(isset($_SESSION[password_key])) unset($_SESSION[password_key]);
}
