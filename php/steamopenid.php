<?php
	require 'openid.php';
	require 'simplecacher.php';

	class SteamOpenID extends simplecacher {
		private $Config = array(
			'cacher-expire' => 3600,
			'openid-domain' => '',
			'api-key' 		=> '',
			'session-salt' => '',
			'token-prefix' => 'whocodestest-',
		);
		private $Database = array(
			'host' 		=> '',
			'user'		=> '',
			'pass'		=> '',
			'db' 		=> '',
		);
		private $Database_Handle;
		private $Database_Connected = true;

		private $OpenID;
		private $LoggedIn = false;
		private $Auth;

		function __construct($login = false){
			session_start();

			$this->OpenID = new LightOpenID($this->Config['openid-domain']);
			$this->setexpire($this->Config['cacher-expire']);

			try{
				$this->Database_Handle = new PDO('mysql:host='.$this->Database['host'].';dbname='.$this->Database['db'].';charset=utf8',
					$this->Database['user'], $this->Database['pass']);
				$this->Database_Handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->Database_Handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			} catch (PDOException $exception){
				$this->Database_Connected = false;
			}
			unset($this->Database);

			if($this->Database_Connected == false)
				die('Error');

			if(isset($_SESSION[$this->Config['token-prefix'] . 'steam-token'])){
				$old_token = $_SESSION[$this->Config['token-prefix'] . 'steam-token'];
				try{
					$query = $this->Database_Handle->prepare("SELECT auth FROM sessions WHERE token = :oldtoken;");
					$query->bindParam(':oldtoken', $old_token, PDO::PARAM_STR);
					$query->execute();

					$result = $query->fetch(PDO::FETCH_ASSOC);

					if(isset($result['auth'])){
						$this->LoggedIn = true;
						$this->Auth = $result['auth'];
					}
				} catch (PDOException $exception){

				}
			}

			if(!$this->OpenID->mode){
				if($login == true){
					$this->OpenID->identity = 'http://steamcommunity.com/openid';
					header('Location: ' . $this->OpenID->authUrl());
				}
			}else{
				if($this->OpenID->mode != 'cancel'){
					if($this->OpenID->validate()){
						$temp_auth = str_replace('http://steamcommunity.com/openid/id/', '', $this->OpenID->identity);

						try{
							$query = $this->Database_Handle->prepare("DELETE FROM sessions WHERE auth = :sessionauth;");
							$query->bindParam(':sessionauth', $temp_auth, PDO::PARAM_INT);
							$query->execute();
						} catch (PDOException $exception){
							//die('Error 003');
						}

						$new_token = md5($this->Config['session-salt'] . '-' . $temp_auth . '-' . uniqid($this->Config['session-salt'], true));

						try{
							$query = $this->Database_Handle->prepare("INSERT INTO sessions (auth, token) VALUES (:sessionauth, :sessiontoken);");
							$query->bindParam(':sessionauth', $temp_auth, PDO::PARAM_INT);
							$query->bindParam(':sessiontoken', $new_token, PDO::PARAM_STR);

							$query->execute();
						} catch (PDOException $exception){
							die('Error 004');
						}

						$_SESSION[$this->Config['token-prefix'] . 'steam-token'] = $new_token;

						$this->LoggedIn = true;
						$this->Auth = $temp_auth;
					}
				}else{
					$this->LoggedIn = false;
				}
			}
		}

		public function getsummaries(){
			if((!$this->LoggedIn) || (!isset($this->Auth)) || $this->Auth == 0)
				return '(!) Not logged in';

			$this->setfile('summaries-'. $this->auth());

			$method_url = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/';
			$sub_url = '?format=json&key='.$this->Config['api-key'].'&steamids='.$this->Auth;

			if($this->cache_isvalid()){
				return json_decode($this->cache_retrieve(), true)['response']['players'][0];
			}
			else
			{
				$headers = array();
				$headers[] = 'User-Agent: PHP/whocodes/'. $this->Config['openid-domain'];

				$curl = curl_init($method_url . $sub_url);

				curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_HTTPHEADER => $headers
				));
				$contents = curl_exec($curl);

				if(!$contents)
					return ('(!) ' . curl_errno($curl) . ': '. curl_error($curl));

				curl_close($curl);

				$this->cache_save($contents);

				return json_decode($contents, true)['response']['players'][0];
			}
		}

		public function logout(){
			unset($_SESSION[$this->Config['token-prefix'] . 'steam-token']);
			$this->LoggedIn = false;
			$this->Auth = null;
		}

		public function loggedin(){
			return $this->LoggedIn;
		}

		public function auth(){
			if($this->loggedin()){
				return $this->Auth;
			}else return false;
		}
	}
?>
