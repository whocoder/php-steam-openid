<?php
	/*
		Simple string to file caching system.
	*/
	class simplecacher {
		var $file;

		private $Directory = __DIR__ . '/../json-cache';
		private $ExpireTime = 3600;

		function __construct(){
			if(!is_dir($this->get_cache_dir())){
				mkdir($this->get_cache_dir());
			}
		}

		private function add_dir_to_file($filename){
			return $this->Directory . '/' . $filename;
		}

		private function urltofile($url){
			$remove  = array('http://', 'https://');
			$replace = array('\\', '/', '.');
			$url = str_replace($remove, "", $url);
			$url = str_replace($replace, "-", $url);
			return $this->add_dir_to_file($url) . '.cache';
		}

		/**
		* Set the expiry time (in seconds) for this instance.
		*
		* @param time 			New time (in seconds)
		*/
		public function setexpire($time){
			$this->ExpireTime = $time;
		}

		/**
		* Set the current file. Be sure to include the extension
		* yourself, as the script will not add it with this function.
		*
		* @param filename 			File name to set.
		*/
		public function setfile($filename){
			$this->file = $this->add_dir_to_file($filename);
		}

		/**
		* Set the current file from a given URL. Slashes and periods
		* are replaced with - and http:// or https:// is removed.
		* These files include the .cache extension.
		*
		* @param url 				URL to use.
		*/
		public function setfilefromurl($url){
			$this->file = $this->urltofile($url);
		}
		/**
		* Checks if the current cache file is valid.
		*
		* @return					True if valid and not yet expired,
		*							false otherwise.
		*/
		public function cache_isvalid(){
			if(file_exists($this->file) && (time() < (filemtime($this->file) + $this->ExpireTime)))
				return true;

			return false;
		}

		/**
		* Returns the content of the current file.
		*
		* @return					File's contents, or '(!)' if invalid.
		*/
		public function cache_retrieve(){
			if(!$this->cache_isvalid())
				return '(!)';

			return file_get_contents($this->file);
		}

		/**
		* Saves data to the current file.
		*
		* @param contents			The data to save.
		* @return					See file_put_contents at php.net
		*/
		public function cache_save($contents){
			file_put_contents($this->file, $contents);
		}

		public function get_cache_dir(){
			return $this->Directory;
		}
	}

?>
