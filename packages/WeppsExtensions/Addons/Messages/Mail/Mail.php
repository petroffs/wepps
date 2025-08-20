<?php
namespace WeppsExtensions\Addons\Messages\Mail;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\SmartyWepps;
use Curl\Curl;
use WeppsCore\Utils\UtilsWepps;

class MailWepps {
	private $attachment=[];
	private $attachmentInput=[];
	private $from;
	private $type;
	private $outer;
	private $content;
	private $contentAll;
	private $debug;
	private $mime_boundary;
	public function __construct($type='plain') {
		$this->type = $type;
		$this->from = "=?utf-8?B?" .base64_encode(ConnectWepps::$projectInfo['name']). "?=" . " <".ConnectWepps::$projectInfo['email'].">";
		$this->mime_boundary=md5(time());
		if (ConnectWepps::$projectDev['debug']==1) {
			$this->debug = 1;
		}
	}
	public function mail(string $to,string $subject,string $text) {
		$from = $this->from;
		$subj = "=?utf-8?B?" . base64_encode($subject) . "?=";
		$headers =  "";
		$headers .= "From: $from\n";
		$headers .= "Reply-to: $from\n";
		$headers .= "X-Mailer: PHP v".phpversion()."\n";
		$headers .= "MIME-Version: 1.0"."\n";
		$headers .= "Content-Type: multipart/related; boundary=\"{$this->mime_boundary}\""."\n";
		$this->contentAll = "--".$this->mime_boundary."\n";
		
		$smarty = SmartyWepps::getSmarty();
		$settings = ConnectWepps::$projectInfo;
		$settings['host'] = [
				'title'=>ConnectWepps::$projectDev['host'],
				'url'=>ConnectWepps::$projectDev['protocol'].ConnectWepps::$projectDev['host']
		];
		$smarty->assign('settings',$settings);
		switch ($this->type) {
			case "html":
				$smarty->assign('subject',$subject);
				$smarty->assign('text',$text);
				$this->content = $smarty->fetch(ConnectWepps::$projectDev['root'].'/packages/WeppsExtensions/Addons/Messages/Mail/MailHtml.tpl');
				self::getQuotedPrintable();
				$this->contentAll .= "Content-Type: text/html; charset=\"utf-8\"\n";
				#$this->contentAll .= "Content-Transfer-Encoding: 8bit"."\n\n";
				$this->contentAll .= "Content-Transfer-Encoding: quoted-printable\n\n";
				$this->contentAll .= (string) $this->content."\r\n";
				#$this->contentAll = self::getImagesHtml($this->contentAll);
				$this->contentAll .= "\r\n";
				break;
			default:
				$smarty->assign('text',$text);
				$this->content = $smarty->fetch(ConnectWepps::$projectDev['root'].'/packages/WeppsExtensions/Addons/Messages/Mail/MailPlain.tpl');
				$this->contentAll .= "Content-Type: text/plain; charset=\"utf-8\"\n";
				$this->contentAll .= "Content-Transfer-Encoding: quoted-printable\n\n";
				$this->contentAll .= (string) $this->content."\n\n";
				break;
		}
		$this->contentAll .= self::getAttach();
		$this->contentAll .= self::getAttachInput();
		$this->contentAll .= "--{$this->mime_boundary}\n\n";
		if ($this->debug==1) {
			$to = ConnectWepps::$projectDev['email'];
		}
		return mail($to,$subj,$this->contentAll,$headers,"-f".ConnectWepps::$projectInfo['email']);
	}
	public function setSender($name,$email) {
		$this->from = "=?utf-8?B?" .base64_encode($name). "?=" . " <".$email.">";
	}
	public function setAttach($attachment = array()) {
		$this->attachment = $attachment;
	}
	public function setAttachInput($attachment = array()) {
		$this->attachmentInput = $attachment;
	}
	public function setDebug() {
		if (ConnectWepps::$projectDev['debug']==1) {
			return $this->debug = 1;
		}
	}
	public function unsetDebug() {
		if (ConnectWepps::$projectDev['debug']==1) {
			return $this->debug = 0;
		}
	}
	public function getContent(bool $contentAll = false) {
		if ($contentAll==true) {
			return $this->contentAll;
		}
		return $this->content;
	}
	public function save($filename='') {
		$filename = ($filename!='') ? $filename : __DIR__ . '/files/mail.html';
		$output = file_get_contents($filename);
		$output .= $this->getContent(false)."\n";
		file_put_contents($filename, $output);
	}
	private function getAttach() {
		$msg = "";
		if (count ( $this->attachment ) != 0) {
			foreach ( $this->attachment as $value ) {
				if (! is_file ( $value )) {
					return 0;
				} else {
					$f_name = $value;
					$handle = fopen ( $f_name, 'rb' );
					$f_contents = fread ( $handle, filesize ( $f_name ) );
					$f_contents = chunk_split ( base64_encode ( $f_contents ) );
					fclose ( $handle );
					$f_info = pathinfo ( $f_name );
					$msg .= "--{$this->mime_boundary}\n";
					$msg .= "Content-Type: 	application/octet-stream; name=\"" . $f_info ['basename'] . "\"\n";
					$msg .= "Content-Transfer-Encoding: base64" . "\n";
					$msg .= "Content-Disposition: attachment; filename=\"" . $f_info ['basename'] . "\"\n\n";
					$msg .= $f_contents . "\n\n";
				}
			}
		}
		return $msg;
	}
	private function getAttachInput() {
		$msg = "";
		if (!empty($this->attachmentInput)) {
			foreach ($this->attachmentInput as $value) {
				$f_contents = chunk_split(base64_encode($value['content']));
				$msg .= "--{$this->mime_boundary}\n";
				$msg .= "Content-Type: 	application/octet-stream; name=\"{$value['title']}\"\n";
				$msg .= "Content-Transfer-Encoding: base64\n";
				$msg .= "Content-Disposition: attachment; filename=\"{$value['title']}\"\n\n";
				$msg .= (string) $f_contents . "\n\n";
			}
		}
		return $msg;
	}
	private function getImagesHtml($msg) {
		$matches = [];
		preg_match_all("/img src=\"([0-9a-zA-Z\.\-\_\/\:]+)/",$msg,$matches);
		$messfiles = "";
		if (is_array($matches[1])) {
			$tmp = [];
			$arrContextOptions = [ 
					"ssl" => [ 
							"verify_peer" => false,
							"verify_peer_name" => false
					]
			];
			
			foreach ($matches[1] as $key=>$filename) {
				if (!isset($tmp[$filename])) {
					$f_info = pathinfo($filename);
					$messfiles .= "\n\n--{$this->mime_boundary}\n";
					$f_info['extension'] = str_replace("jpg","jpeg",$f_info['extension']);
					$messfiles.="Content-Type: image/".$f_info['extension']."; name=\"".basename($filename)."\"\n";
					$messfiles.="Content-Transfer-Encoding:base64\n";
					$messfiles.="Content-ID: <img_$key>\n\n";
					$file = file_get_contents($filename,false,stream_context_create($arrContextOptions));
					$messfiles.= self::encode64(base64_encode($file))."\n";
					$msg = str_replace($filename,"cid:img_$key",$msg);
					$tmp[$filename] = 1;
				}
			}
		}
		return $msg.$messfiles;
	}
	private function encode64 ($data) {
		$datalb = "";
		while (strlen($data) > 64) {
			$datalb .= substr($data, 0, 64) . "\r\n";
			$data = substr($data,64);
		}
		$datalb .= $data;
		return $datalb;
	}
	private function getQuotedPrintable() {
		$this->content = mb_convert_encoding($this->content, 'UTF-8');
		$this->content = quoted_printable_encode($this->content);
	}
}