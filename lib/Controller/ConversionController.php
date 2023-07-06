<?php
namespace OCA\dbmp3\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use \OCP\IConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OC\Files\Filesystem;


class ConversionController extends Controller {

	private $userId;

	/**
	* @NoAdminRequired
	*/
	public function __construct($AppName, IRequest $request, $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;

	}

	public function getFile($directory, $fileName){
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($this->userId);
		return Filesystem::getLocalFile($directory . '/' . $fileName);
	}
	/**
	* @NoAdminRequired
	*/
	public function convertHere($nameOfFile, $directory, $external, $type, $url = null, $shareOwner = null, $mtime = 0) {
		$response = array();
		$dir = $this->getFile($directory, $nameOfFile);
		if (!is_dir($dir)) {
			$dir = dirname($this->getFile($directory, $nameOfFile));
		}
		$cmd = $this->createCmd($dir, $url);
		$output = "";
		exec($cmd, $output,$return);
		if (filesize($dir."/out.mp4") <1000) {
			$response = array_merge($response, array('error' => 'url : '.$url.' not downloadable or not found', 'cmd' => $cmd));
		}else {
			exec("rm ".$dir."/out.mp4", $output, $return);
			$response = array_merge($response, array("code" => 1, "cmd" => $cmd));
			exec("php /var/www/nextcloud/occ files:scan ".$this->userId, $output, $return);
		}
		return json_encode($response);
	}
	/**
	* @NoAdminRequired
	*/
	public function createCmd($dir, $url){
		$output = date("d-m-Y_H:i:s").".mp3";
		return "wget -O ".$dir."/out.mp4 ".$url." ; ffmpeg -i ".$dir."/out.mp4 -af silenceremove=window=0:detection=peak:stop_mode=all:start_mode=all:stop_periods=-1:stop_threshold=0 -codec:a libmp3lame -qscale:a 2 ".$dir."/".$output;
	}
}
