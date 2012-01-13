<?php
class Error extends GW {
	protected $_threshold = 1;
	protected $_date_fmt = 'Y-m-d H:i:s';
	protected $_levels = array('ERROR' => '1', 'NOTICE' => '2', 'DEBUG' => '3',  'INFO' => '4', 'ALL' => '5');
	
	public function send($sMessage, $sCode = 500, $sLog = true, $sHeader = 'An Error Was Encountered') {
		
		if($sLog === true) {
			$this->log('ERROR', $sMessage);
		}
		
		if($this->config['env'] === 'Production') {
			$this->set_status_header($sCode);
			
			$aData = array(
				'sHeader' => $sHeader,
				'sMessage' => $sMessage
			);
			
			switch($sCode) {
				case 404:
					$this->load->view('error/404.php', $aData);
					break;
				case 500:
					$this->load->view('error/500.php', $aData);
					break;
				default:
					$this->load->view('error/generic.php', $aData);
			}
		} else {
			$trace = debug_backtrace();
			trigger_error($sMessage.' in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_ERROR);
		}
		
		return true;
	}
	
	public function trigger($sMessage, $sLevel = 'ERROR', $aBacktrace = null) {
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
			$aBacktrace = $aBacktrace[0];
		}
		
		$sLevel = strtoupper($sLevel);
		
		$this->log($sLevel, $sMessage.' in '.$aBacktrace['file'].' on line '.$aBacktrace['line']);
		
		if($this->config['env'] == 'Production') {
			$this->send($sMessage, 500, false);
		} else {
			$sLevel = strtoupper($sLevel);
			
			switch($sLevel) {
				case 'ERROR':
					$sType = E_USER_ERROR;
					break;
				case 'ALL':
					$sType = E_ALL;
					break;
				default:
					$sType = E_USER_NOTICE;
			}
			
			trigger_error($sMessage.' in '.$aBacktrace['file'].' on line '.$aBacktrace['line'], $sType);
		}
	}
	
	public function log($sMessage, $sLevel = 'ERROR') {
		$sLogPath = $this->root.'app/logs/';
		
		if(!$this->is_really_writable($sLogPath)) {
			return false;
		}

		$sLevel = strtoupper($sLevel);
		
		if (!isset($this->_levels[$sLevel]) OR ($this->_levels[$sLevel] > $this->_threshold)){
			return false;
		}

		$sFilePath = $sLogPath.'log-'.date('Y-m-d').'.txt';

		if ( ! $fp = fopen($sFilePath, 'ab')) {
			return false;
		}

		$sMessage = $sLevel.' '.(($sLevel == 'INFO') ? ' -' : '-').' '.date($this->config['options']['formatDate'].' '.$this->config['options']['formatTime']). ' --> '.$sMessage."\n";
		
		flock($fp, LOCK_EX);
		fwrite($fp, $sMessage);
		flock($fp, LOCK_UN);
		fclose($fp);

		chmod($sFilePath, 0666);
		
		return TRUE;
	}
	
	public function is_really_writable($file) {
		// If safe_mode off we call is_writable
		if (@ini_get('safe_mode') == FALSE) {
			return is_writable($file);
		}

		// For windows servers and safe_mode "on" installations we'll actually
		// write a file then read it.
		if (is_dir($file)) {
			$file = rtrim($file, '/').'/'.md5(mt_rand(1,100).mt_rand(1,100));

			if (($fp = @fopen($file, 'ab')) === FALSE){
				return false;
			}

			fclose($fp);
			@chmod($file, 0777);
			@unlink($file);
			
			return true;
		} elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE) {
			return false;
		}

		fclose($fp);
		return true;
	}
	
	private function set_status_header($sCode = 200, $sText = '') {
		$aStatuses = array(
			200	=> 'OK',
			201	=> 'Created',
			202	=> 'Accepted',
			203	=> 'Non-Authoritative Information',
			204	=> 'No Content',
			205	=> 'Reset Content',
			206	=> 'Partial Content',

			300	=> 'Multiple Choices',
			301	=> 'Moved Permanently',
			302	=> 'Found',
			304	=> 'Not Modified',
			305	=> 'Use Proxy',
			307	=> 'Temporary Redirect',

			400	=> 'Bad Request',
			401	=> 'Unauthorized',
			403	=> 'Forbidden',
			404	=> 'Not Found',
			405	=> 'Method Not Allowed',
			406	=> 'Not Acceptable',
			407	=> 'Proxy Authentication Required',
			408	=> 'Request Timeout',
			409	=> 'Conflict',
			410	=> 'Gone',
			411	=> 'Length Required',
			412	=> 'Precondition Failed',
			413	=> 'Request Entity Too Large',
			414	=> 'Request-URI Too Long',
			415	=> 'Unsupported Media Type',
			416	=> 'Requested Range Not Satisfiable',
			417	=> 'Expectation Failed',

			500	=> 'Internal Server Error',
			501	=> 'Not Implemented',
			502	=> 'Bad Gateway',
			503	=> 'Service Unavailable',
			504	=> 'Gateway Timeout',
			505	=> 'HTTP Version Not Supported'
		);

		if ($sCode == '' OR ! is_numeric($sCode)) {
			$aTrace = debug_backtrace();
			$this->trigger('Status codes must be numeric', 'ERROR', $aTrace[0]);
		}

		if (isset($aStatuses[$sCode]) AND $sText == '') {
			$sText = $aStatuses[$sCode];
		}

		if ($sText == '') {
			$aTrace = debug_backtrace();
			$this->trigger('No status text available. Please check your status code number or supply your own message text.', 'ERROR', $aTrace[0]);
		}
		
		header('HTTP/1.1 '.$sCode.' '.$sText, TRUE, $sCode);
	}
}