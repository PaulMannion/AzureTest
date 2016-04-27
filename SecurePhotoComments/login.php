<?php
/*
	session_start();
	include("connection.php"); //Establishing connection with our database
	
	$error = ""; //Variable for storing our errors.
	if(isset($_POST["submit"]))
	{
		if(empty($_POST["username"]) || empty($_POST["password"]))
		{
			$error = "Both fields are required.";
		}else
		{
			// Define $username and $password
			$username=$_POST['username'];
			$password=$_POST['password'];


			
			//Check username and password from database
			$sql="SELECT userID FROM users WHERE username='$username' and password='$password'";
			$result=mysqli_query($db,$sql);
			$row=mysqli_fetch_array($result,MYSQLI_ASSOC) ;
			
			//If username and password exist in our database then create a session.
			//Otherwise echo error.
			
			if(mysqli_num_rows($result) == 1)
			{
				$_SESSION['username'] = $username; // Initializing Session
				header("location: photos.php"); // Redirecting To Other Page
			}else
			{
				$error = "Incorrect username or password.";
			}

		}
	}
*/
?>


<?php
	//display error
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

	session_start();
	include("connection.php"); //Establishing connection with our database

	$error = ""; //Variable for storing our errors.


	if(isset($_POST["submit"])) {
		if (empty($_POST["username"]) || empty($_POST["password"])) {
			$error = "Both flanges are required.";
		} else {
			// Check Anti-CSRF token
			//checkToken($_REQUEST['user_token'], $_SESSION['session_token'], 'index.php');

			// Sanitise username input
			$user = $_POST['username'];
			$user = stripslashes($user);
			$user = mysqli_real_escape_string($db,$user);
			echo "<p><em>Warning</em>: WE got as far cleaning u/n.</p>";
			// Sanitise password input
			$pass = $_POST['password'];
			$pass = stripslashes($pass);
			$pass = mysqli_real_escape_string($db,$pass);
			$pass = md5($pass);
			echo "<p><em>Warning</em>: WE got as far cleanin pwd</p>";
			// Default values
			$total_failed_login = 3;
			$lockout_time = 15;
			$account_locked = false;
			echo "<p><em>Warning</em>: WE got as far start of db</p>";
			// Check the database (Check user information)
			$data = $db->prepare('SELECT failed_login, last_login FROM users WHERE user = :user LIMIT 1;');
			$data->bindParam(':user', $user, PDO::PARAM_STR);
			$data->execute();
			$row = $data->fetch();
			echo "<p><em>Warning</em>: WE got as far fetching data.</p>";
			// Check to see if the user has been locked out.
			if (($data->rowCount() == 1) && ($row['failed_login'] >= $total_failed_login)) {
				// User locked out.  Note, using this method would allow for user enumeration!
				//echo "<pre><br />This account has been locked due to too many incorrect logins.</pre>";

				// Calculate when the user would be allowed to login again
				$last_login = $row['last_login'];
				$last_login = strtotime($last_login);
				$timeout = strtotime("{$last_login} +{$lockout_time} minutes");
				$timenow = strtotime("now");

				// Check to see if enough time has passed, if it hasn't locked the account
				if ($timenow > $timeout)
					$account_locked = true;
			}

			// Check the database (if username matches the password)
			$data = $db->prepare('SELECT * FROM users WHERE user = (:user) AND password = (:password) LIMIT 1;');
			$data->bindParam(':user', $user, PDO::PARAM_STR);
			$data->bindParam(':password', $pass, PDO::PARAM_STR);
			$data->execute();
			$row = $data->fetch();

			// If its a valid login...
			if (($data->rowCount() == 1) && ($account_locked == false)) {
				// Get users details
				$avatar = $row['avatar'];
				$failed_login = $row['failed_login'];
				$last_login = $row['last_login'];

				// Login successful
				$_SESSION['username'] = $user; // Initializing Session
				header("location: photos.php"); // Redirecting To Other Page

				// Had the account been locked out since last login?
				if ($failed_login >= $total_failed_login) {
					echo "<p><em>Warning</em>: Someone might of been brute forcing your account.</p>";
					echo "<p>Number of login attempts: <em>{$failed_login}</em>.<br />Last login attempt was at: <em>${last_login}</em>.</p>";
				}

				// Reset bad login count
				$data = $db->prepare('UPDATE users SET failed_login = "0" WHERE user = (:user) LIMIT 1;');
				$data->bindParam(':user', $user, PDO::PARAM_STR);
				$data->execute();
			} else {
				// Login failed
				sleep(rand(2, 4));

				// Give the user some feedback
				$error = "<pre><br />Username and/or password incorrect.<br /><br/>Alternative, the account has been locked because of too many failed logins.<br />If this is the case, <em>please try again in {$lockout_time} minutes</em>.</pre>";

				// Update bad login count
				$data = $db->prepare('UPDATE users SET failed_login = (failed_login + 1) WHERE user = (:user) LIMIT 1;');
				$data->bindParam(':user', $user, PDO::PARAM_STR);
				$data->execute();
			}

			// Set the last login time
			$data = $db->prepare('UPDATE users SET last_login = now() WHERE user = (:user) LIMIT 1;');
			$data->bindParam(':user', $user, PDO::PARAM_STR);
			$data->execute();
		}

		// Generate Anti-CSRF token
		//generateSessionToken();
	}
?>
