<?php
/**
 * @author 马秉尧
 * @copyright (C) 2005 CoolCode.CN
 * @package xmlrpc-epi-php
 * @version 0.7
 */

class xmlrpc_error {
	var $faultCode;
	var $faultString;
	function xmlrpc_error($code, $string) {
		$this->faultCode = $code;
		$this->faultString = $string;
	}
}

class xmlrpc_server {
	var $server;
	function xmlrpc_server() {
		$this->server = xmlrpc_server_create();
		register_shutdown_function(array(&$this, "__xmlrpc_server"));
	}

	function register_method($method_name, $function) {
		xmlrpc_server_register_method($this->server, $method_name, $function);
	}

	function xmlrpc_server_add_introspection_data($desc) {
		xmlrpc_server_add_introspection_data($this->server, $desc);
	}

	function register_introspection_callback($function) {
		xmlrpc_server_register_introspection_callback($this->server, $function);
	}

	function call_method($user_data = null) {
		if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
			$request = $GLOBALS['HTTP_RAW_POST_DATA'];
		}
		else {
			$request = '';
		}
		$output_options = array(
			"output_type" => "xml",
			"verbosity" => "pretty",
			"escaping" => array("markup"),
			"version" => "xmlrpc",
			"encoding" => "utf-8"
		);
		$response = xmlrpc_server_call_method($this->server, $request, $user_data, $output_options);
		header("HTTP/1.1 200 OK");
		header("Connection: close");
		header("Content-Length: " . strlen($response));
		header("Content-Type: text/xml; charset=utf-8");
		header("Date: " . gmdate("D, d M Y H:i:s") . " GMT");
		print $response;
	}

	function __xmlrpc_server() {
		xmlrpc_server_destroy($this->server);
	}
}

class xmlrpc_client {
	var $scheme;
	var $host;
	var $port;
	var $path;
	var $user;
	var $pass;
	var $namespace;
	var $timeout;

	function xmlrpc_client($url, $namespace = '', $user = '', $pass = '', $timeout = 10) {
		$this->use_service($url);
		$this->namespace = $namespace;
		$this->user = $user;
		$this->pass = $pass;
		$this->timeout = $timeout;
	}

	function use_service($url) {
		$urlparts = parse_url($url);

		if (!isset($urlparts['host'])) {
			if (isset($_SERVER["HTTP_HOST"])) {
				$urlparts['host'] = $_SERVER["HTTP_HOST"];
			}
			else if (isset($_SERVER["SERVER_NAME"])) {
				$urlparts['host'] = $_SERVER["SERVER_NAME"];
			}
			else {
				$urlparts['host'] = "localhost";
			}
			if (!isset($urlparts['scheme'])) {
				if (!isset($_SERVER["HTTPS"]) ||
					$_SERVER["HTTPS"] == "off"  ||
					$_SERVER["HTTPS"] == "") {
					$urlparts['scheme'] = "";
				}
				else {
					$urlparts['scheme'] = "https";
				}
			}
			if (!isset($urlparts['port'])) {
				$urlparts['port'] = $_SERVER["SERVER_PORT"];
			}
		}

		if (isset($urlparts['scheme']) && ($urlparts['scheme'] == "https")) {
			$urlparts['scheme'] = "ssl";
		}
		else {
			$urlparts['scheme'] = "";
		}

		if (!isset($urlparts['port'])) {
			if ($urlparts['scheme'] == "ssl") {
				$urlparts['port'] = 443;
			}
			else {
				$urlparts['port'] = 80;
			}
		}

		if (!isset($urlparts['path'])) {
			$urlparts['path'] = "/";
		}
		else if (($urlparts['path']{0} != '/') && ($_SERVER["PHP_SELF"]{0} == '/')) {
			$urlparts['path'] = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], '/') + 1) . $urlparts['path'];
		}

		$this->scheme = $urlparts['scheme'];
		$this->host = $urlparts['host'];
		$this->port = $urlparts['port'];
		$this->path = $urlparts['path'];
	}

	function __invoke($function, $arguments) {
		$output = array(
			"output_type" => "xml",
			"verbosity" => "pretty",
			"escaping" => array("markup"),
			"version" => "xmlrpc",
			"encoding" => "utf-8");
		$request = xmlrpc_encode_request($function, $arguments, $output);
		$content_len = strlen($request);
		$errno = 0;
		$errstr = '';
		$host = ($this->scheme) ? $this->scheme . "://" . $this->host : $this->host;
		$handle = @fsockopen($host, $this->port, $errno, $errstr, $this->timeout);
		$buf = '';
		if ($handle) {
			$auth = '';
			if ($this->user) {
				$auth = "Authorization: Basic " . base64_encode($this->user . ":" . $this->pass) . "\r\n";
			}
			$http_request =
				"POST $this->path HTTP/1.0\r\n" .
				"User-Agent: xmlrpc-epi-php/0.6 (PHP)\r\n" .
				"Host: $this->host:$this->port\r\n" .
				$auth .
				"Content-Type: text/xml; charset=utf-8\r\n" .
				"Content-Length: $content_len\r\n" .
				"\r\n" .
				$request;
			fputs($handle, $http_request, strlen($http_request));
			while (!feof($handle)) {
				$buf .= fgets($handle, 128);
			}
			fclose($handle);
			if (strlen($buf)) {
				$xml = substr($buf, strpos($buf, "<?xml"));
				if (strlen($xml)) {
					$result = xmlrpc_decode($xml);
				}
				else {
					$result = new xmlrpc_error(7, "invalid data");
				}
			}
			else {
				$result = new xmlrpc_error(6, "No data received from server");
			}
		}
		else {
			$result = new xmlrpc_error(5, "Didn't receive 200 OK from remote server");
		}
		if (is_object($result)) {
			$fp = fopen('/tmp/xmlrpc_error.log', 'a');
			if ($fp) {
				$log = "\n---------------------------------------------------\n";
				$log .= "-- Time: ".date("Y-m-d H:i:s")."\n";
				$log .= "-- Local Url: http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."\n";
				$log .= "-- Local File: ".$_SERVER['SCRIPT_FILENAME']."\n";
				$log .= "-- Rpc Server: http://".$this->host.":".$this->port.$this->path."\n";
				$log .= "-- Rpc Function: ".$function."\n";
				$log .= "-- Rpc Arguments: ".print_r($arguments, true);
				$log .= "-- Rpc Response: ".$buf."\n";
				$log .= "-- Rpc Error: ".print_r($result, true);
				$log .= "-- Fsockopen Error: ".$errno."\t".$errstr."\n";
				fwrite($fp, $log);
				fclose($fp);
			}
			$result = '';
		}
		return $result;
	}

	function invoke($function, $args) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->__invoke($function, $arguments);
	}

	function call($function, $args) {
		$function = ($this->namespace == '') ? $function : $this->namespace . '.' . $function;
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->__invoke($function, $arguments);
	}
}
?>