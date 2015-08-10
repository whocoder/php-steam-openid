<?php
	require 'openid.php';
	require 'simplecacher.php';

	class SteamOpenID extends simplecacher {
		private $Config = array(
			'cacher-expire' => 3600,
			'cookie-expire' => 3600,
			'openid-domain' => '',
			'api-key' 		=> '',
			'session-salt' => '',
			'token-prefix' => 'whocodestest-',
			'max-sessions' 	=> 5,
			'logging'		=> true,
			'session-iplock' => false,
		);
		private $Database = array(
			'host' 		=> '',
			'user'		=> '',
			'pass'		=> '',
			'db' 		=> '',
		);

		/* http://php.net/manual/en/function.error-log.php */
		private $LogType = 3;
		private $LogLoc = __DIR__ . '/logs/steamopenid.log';

		private function log($message){
			if($this->Config['logging']){
				if($LogType > 0){
					error_log($message, $this->LogType, $this->LogLoc);
				}else{
					error_log($message);
				}
			}
		}

		private $Database_Handle;
		private $Database_Connected = true;

		private $OpenID;
		private $LoggedIn = false;
		private $Auth;


		function __construct($login = false){
			$this->OpenID = new LightOpenID($this->Config['openid-domain']);
			$this->setexpire($this->Config['cacher-expire']);

			try{
				$this->Database_Handle = new PDO('mysql:host='.$this->Database['host'].';dbname='.$this->Database['db'].';charset=utf8',
					$this->Database['user'], $this->Database['pass']);
				$this->Database_Handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->Database_Handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			} catch (PDOException $exception){
				$this->log('Error in construct @ initial connection: ' . $exception);
				$this->Database_Connected = false;
			}
			unset($this->Database);

			if($this->Database_Connected == false){
				$this->log('Unable to connect to database');
				return;
			}

			if(isset($_COOKIE[$this->Config['token-prefix'] . 'steam-token'])){
				$old_token = $_COOKIE[$this->Config['token-prefix'] . 'steam-token'];
				try{
					$statement = "SELECT auth FROM sessions WHERE token = :oldtoken";

					if($this->Config['session-iplock']){
						$statement .= "AND address = :userip";
					}

					$statement .= ";";

					$query = $this->Database_Handle->prepare();
					$query->bindParam(':oldtoken', $old_token, PDO::PARAM_STR);

					if($this->Config['session-iplock']){
						$query->bindParam(':userip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
					}

					$query->execute();

					$result = $query->fetch(PDO::FETCH_ASSOC);

					if(isset($result['auth'])){
						$this->LoggedIn = true;
						$this->Auth = $result['auth'];
					}
				} catch (PDOException $exception){
					$this->log('Error at token verification: ' . $exception);
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

						if($this->Config['max-sessions'] <= 1){
							try{
								$query = $this->Database_Handle->prepare("DELETE FROM sessions WHERE auth = :sessionauth;");
								$query->bindParam(':sessionauth', $temp_auth, PDO::PARAM_INT);
								$query->execute();
							} catch (PDOException $exception){
								$this->log('Error deleting sessions (1): ' . $exception);
							}
						}else{
							try{
								$query = $this->Database_Handle->prepare("SELECT COUNT(*) FROM sessions WHERE auth = :sessionauth;");
								$query->bindParam(':sessionauth', $temp_auth, PDO::PARAM_INT);
								$query->execute();

								$session_amt = $query->fetchColumn();

								$del_amt = 0;
								if($session_amt > $this->Config['max-sessions']){
									$del_amt = ($session_amt - ($this->Config['max-sessions'] + 1));
								}else if($session_amt == $this->Config['max-sessions']){
									$del_amt = 1;
								}

								if($del_amt != 0){
									try{
										$query = $this->Database_Handle->prepare("DELETE FROM sessions WHERE auth = :sessionauth LIMIT :sessionlimit;");
										$query->bindParam(':sessionauth', $temp_auth, PDO::PARAM_INT);
										$query->bindParam(':sessionlimit', ($del_amt), PDO::PARAM_INT);
										$query->execute();
									} catch(PDOException $exception){
										$this->log('Error deleting sessions (2): ' . $exception);
									}
								}
							} catch (PDOException $exception){
								$this->log('Error deleting sessions (3): ' . $exception);
							}
						}

						$new_token = md5($this->Config['session-salt'] . '-' . $temp_auth . '-' . uniqid($this->Config['session-salt'], true));

						try{
							$query = $this->Database_Handle->prepare("INSERT INTO sessions (auth, token, address) VALUES (:sessionauth, :sessiontoken, :userip);");
							$query->bindParam(':sessionauth', $temp_auth, PDO::PARAM_INT);
							$query->bindParam(':sessiontoken', $new_token, PDO::PARAM_STR);
							$query->bindParam(':userip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);

							$query->execute();
						} catch (PDOException $exception){
							$this->log('Error creating token: ' . $exception);
						}

						setcookie($this->Config['token-prefix'] . 'steam-token', $new_token, time() + $this->Config['cookie-expire']);

						$this->LoggedIn = true;
						$this->Auth = $temp_auth;
					}
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
			unset($_COOKIE[$this->Config['token-prefix'] . 'steam-token']);
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
