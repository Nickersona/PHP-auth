<?php
	require(dirname(__FILE__).'/passwordhash.php');
		
	class Auth{
		  public $conn; // Require a connection on the MySQLi Object format.
		  public $excluded_user_data; //
		  public $errors = array(); // Contains An array of all errors 
		  
		  private $ses_timeout = 3600; //Session timeout in seconds
		  private $ses_id = ''; 
		  private $login_file = 'login.php'; //Reference to your login File relatve to this file
		  private $logged_in = false; 
		  private $encodable = false; //To Hash Your passwords set this to True. Provides you a form the do the translation
		  
		  private $hasher;
		  private $hasher_level = 8; // Complexity of the Hash
		  
		  private $redirect = '/eadmin/index.php';
		  
		  
		  public function __construct($conn) { 
			  $this->conn = $conn; 
			  $this->excluded_user_data = Array('password');
			  $this->hasher = new PasswordHash($this->hasher_level, FALSE);

			  //Determine if the current session matches a user in the databsae
			  $this->logged_in = $this->session_check();
			  
			  //Check if login or logout forms have been submitted
			  // overides the initial $this->logged_in if a successful login is completed
			  $this->check_POST($_POST);

							  
			  if($this->logged_in == false){
				//if no one is logged in, display a login form for them
				$this->build_login();  
			  }
		  } 
		
		
		//Establishes the Session returns true or false if the user is logged in or not
		function session_check(){
			session_start();
			if(isset($_SESSION['timeout']) ) {
				$session_life = time() - $_SESSION['timeout'];
				if($session_life > $this->ses_timeout){ 
					$this->logout();
					return false;
				}
			}
			
			if($this->encodable){
				$this->logout();
				return false;
			}
			
			$this->ses_id = session_id(); 
			$this->get_errors();

			$dbid_exists = false; 
			$dbid_exists = $this->check_session_id_from_db($this->ses_id); 


			 if ($dbid_exists){ 	
				return true;
			 } else{
				 session_regenerate_id();         
				$this->ses_id = session_id(); 
				return false;
			 }

			 
		}
		
		
		//  The actual database check for the hashed password
		function check_session_id_from_db($ses_id = null){
			if($ses_id != null && $ses_id != ''){
				$sql = 'SELECT * FROM admin WHERE token = "'.$ses_id.'"';	
				
				$result = $this->conn->query($sql);
				
				return ($result->num_rows > 0)? true: false;
			}
			return false;
		}
		
	
		// Logs in, duh. Also establishes the timeout 
		function login($email, $password){
			$sql = 'SELECT * FROM admin WHERE email = "'.$email.'"';	
			$result = $this->conn->query($sql);
			$user_record = $result->fetch_object();
			
			$hash_match = $this->hasher->CheckPassword($password, $user_record->password);
			
			if($hash_match){
				$token = $this->ses_id;

				$stmt = $this->conn->prepare("UPDATE admin SET token = ? WHERE email = ? ");
				$stmt->bind_param('ss', $token, $email);
				$stmt->execute(); 
				$stmt->close(); 
				
				$this->load_user($user_record);
				$_SESSION['timeout'] = time();
				$this->logged_in = true;
			}else{
				$this->logged_in = false;
				$this->set_error('No User Found', 'Looks like you logged in with the wrong credentials, try again');
			};
			
			$_SESSION['token'] = $token;
			
		}
		

		
		//sets up all logged in user to the session. Called every time the user is authenticated
		function load_user($user_record){
			foreach ($user_record as $field_name => $data){
				if(in_array($field_name, $this->excluded_user_data)){
					continue;
				}
				$_SESSION['current_user'][$field_name] = $data;
			}			
		}
		
		//Exlodes the session and redirects
		function logout(){
			session_unset();
			session_regenerate_id();         
			$this->ses_id = '';
			
			header("Location: ".$this->redirect);
			exit;
		}
		
		
		//Grabs the login file and displays it.
		function build_login(){
			if($this->encodable){
				echo '
			<form action="" method="post">
				<p>Submit your password for encoding to the Database</p>
				<input name="action" value="encode" type="hidden" />
				<input name="password" type="text" />
				<input name="submit" type="submit" />
			</form>';	
			exit;
			}
			
			$login_form = file_get_contents(dirname(__FILE__).'/'.$this->login_file);
			if(isset($this->errors) && $this->errors != array()){
				foreach($this->errors as  $error){
					echo '<div class="error_message">'.$error['name'].': '.$error['description'].' </div>';	
				}
				
			}
			echo $login_form;				
			exit;
		}
		
		//Takes the $_POST['action'] variable and directs the data to the proper method
		function check_POST($_POST){
		
			//Implement Injection attack Security Here
			if (isset($_POST) && isset($_POST['action'])){
				if($_POST['action'] == 'login'){
					if(isset($_POST['email']) && isset($_POST['password'])){
						if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))	{
							$this->login($_POST['email'], $_POST['password']);
						}else{
							$this->set_error('Failed Login','Invalid email formatting');
						}
					}else{
						$this->set_error('Failed Login','email or password were missing');
					}
				}else if ($_POST['action'] == 'logout'){
					 $this->logout();
				}else if ($_POST['action'] == 'encode'){
					 $this->encode_password($_POST['password']);
				}
								
			}
		} 	
		
		//Grabs the errors from the session sets them to the $this->error_array for displaying on the login form.
		function get_errors(){
			if(isset($_SESSION['errors'])){
				$this->errors = $_SESSION['errors'];
				unset($_SESSION['errors']);  
			}
		}
		
		function set_error($error_name, $error_description){
			$_SESSION['errors'][] = array('name' => $error_name , 'description' => $error_description);
		}
		
		function encode_password($password){ 
			if ($this->encodable){
				$hash = $this->hasher->HashPassword($password);
				echo 'Insert this into your Database as the Hashed Password<br />';
				echo '<strong>'.$password.'</strong> : '.$hash;
				exit;
			}
		    		
		}

	}