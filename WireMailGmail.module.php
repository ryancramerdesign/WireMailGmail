<?php namespace ProcessWire;

/**
 * WireMail: Gmail
 * 
 * Requires the GoogleClientAPI module installed with this scope:
 * https://www.googleapis.com/auth/gmail.send
 * 
 * Developed by Ryan Cramer for ProcessWire 3.x / © 2019 (MPL 2.0)
 * 
 * @property string $fromGoogleEmail
 * @property string $fromGoogleName
 * 
 */
class WireMailGmail extends WireMail implements Module, ConfigurableModule {
	
	/**
	 * Module info
	 *
	 * @return array
	 *
	 */
	public static function getModuleInfo() {
		return array(
			'title' => 'WireMail Gmail',
			'summary' => 'WireMail module that sends email through Google’s Gmail.',
			'version' => 1,
			'requires' => 'GoogleClientAPI',
		);
	}

	/**
	 * @var \Google_Service_Gmail|null
	 * 
	 */
	protected $service = null;

	/**
	 * Construct
	 * 
	 */
	public function __construct() {
		$this->set('fromGoogleEmail', '');
		$this->set('fromGoogleName', '');
		parent::__construct();
	}

	/**
	 * Get the Google Gmail Service
	 * 
	 * @return \Google_Service_Gmail|null
	 * 
	 */
	protected function getGmailService() {
		
		if($this->service) return $this->service; 

		try {
			
			$module = $this->wire('modules')->get('GoogleClientAPI');
			if(!$module) throw new WireException("The GoogleClientAPI module is required");
			
			$client = $module->getClient();
			if(!$client) throw new WireException("Unable to get Google client from GoogleClientAPI module");
			
			$service = new \Google_Service_Gmail($client);
			if(!$service) throw new WireException("Unable to get Google_Service_Gmail");
			
			$this->service = $service;
			
		} catch(\Exception $e) {
			$service = null;
			$error = $this->className() . ': ' . $e->getMessage();
			$this->error($error);
			$this->log($error);
		}

		return $service;
	}

	/**
	 * Send the email
	 *
	 * @return int Returns a positive number (indicating number of addresses emailed) or 0 on failure.
	 *
	 */
	public function ___send() {

		if(!$this->getGmailService()) return 0;
		
		if($this->fromGoogleEmail) $this->from($this->fromGoogleEmail);
		if($this->fromGoogleName && !$this->fromName) $this->fromName($this->fromGoogleName);

		// prep header and body
		$this->multipartBoundary(true);
		$header = $this->renderMailHeader();
		$body = $this->renderMailBody();
		$subject = $this->encodeSubject($this->subject);
		$numSent = 0;

		foreach($this->to as $to) {
			if($this->sendGmailTo($to, $subject, $header, $body)) $numSent++;
		}

		return $numSent;
	}

	/**
	 * Send Gmail message 
	 * 
	 * @param string $to Email to send to
	 * @param string $subject Subject of message (encoded)
	 * @param string $header Message header, except for To and Subject
	 * @param string $body Message body
	 * @return bool 
	 * 
	 */
	protected function sendGmailTo($to, $subject, $header, $body) {
		
		$result = false;
		$service = $this->getGmailService();
		
		$toName = isset($this->mail['toName'][$to]) ? $this->mail['toName'][$to] : '';
		$to = ($toName ? $this->bundleEmailAndName($to, $toName) : $to);

		$data = "To: $to\r\nSubject: $subject\r\n$header\r\n\r\n$body";
		$data = rtrim(strtr(base64_encode($data), '+/', '-_'), '='); // URL-safe base64 encode
		$me = $this->fromGoogleEmail ? $this->fromGoogleEmail : 'me'; // "me" implies auth google email

		try {
			$message = new \Google_Service_Gmail_Message();
			$message->setRaw($data);
			$message = $service->users_messages->send($me, $message);
			if($message) $result = true;
			
		} catch(\Exception $e) {
			$error = $e->getMessage();
			$this->log($error);
			if($this->wire('user')->isSuperuser()) {
				if(strpos($error, '{') === 0) $error = json_decode($error, true);
				$this->error($error);
			}
		}
		
		return $result;
	}

	/**
	 * Module config
	 * 
	 * @param InputfieldWrapper $inputfields
	 * 
	 */
	public function getModuleConfigInputfields(InputfieldWrapper $inputfields) {
		/** @var Modules $modules */
		$modules = $this->wire('modules');
		
		/** @var InputfieldEmail $f */
		$f = $modules->get('InputfieldEmail');
		$f->attr('name', 'fromGoogleEmail'); 
		$f->label = $this->_('Google “from” email (Gmail) address that is authenticated to send (optional)'); 
		$f->description = 
			$this->_('When sending from Gmail, the “from” email address is required to be the one the system was authenticated with (in the GoogleClientAPI module).') . ' ' . 
			$this->_('If it isn’t, then Gmail will place it there for you and also overwrite your from “name” (if used), and potentially other properties.') . ' ' . 
			$this->_('As a result, we recommend entering the authenticated Gmail address here.') . ' ' . 
			$this->_('This will force it to be used for any messages sent, ensuring Gmail does not overwrite your from “name”.');
		$f->attr('value', $this->fromGoogleEmail);
		$f->attr('placeholder', 'you@gmail.com');
		$inputfields->add($f);
	
		/** @var InputfieldText $f */
		$f = $modules->get('InputfieldText');
		$f->attr('name', 'fromGoogleName'); 
		$f->label = $this->_('Default from “name” to use (optional)'); 
		$f->description = $this->_('This is typically a first and last name, or company name. It is used only if the message does not specify its own from name.');
		$f->attr('value', $this->fromGoogleName);
		$inputfields->add($f);
		
		$f = $modules->get('InputfieldEmail');
		$f->attr('name', '_test_email');
		$f->label = $this->_('Send a test message to any email address'); 
		$f->description = $this->_('Enter an email address here to send a test message to. Use this to confirm that everything is working.'); 
		$f->notes = $this->_('If you have adjusted any settings above, save/submit those settings first before using this test.');
		$f->collapsed = Inputfield::collapsedYes;
		$f->attr('placeholder', 'you@domain.com');
		$f->icon = 'send';
		$inputfields->add($f);
	
		$testEmail = $this->wire('input')->post->email('_test_email');
		if($testEmail) {
			$className = $this->className();
			$head = "This is a test";
			$body = "This is a test of the $className module. This is only a test. If you have received this message, it means the module is working!";
			$this->to($testEmail);
			$this->subject("$className Test Message"); 
			$this->body("$head\n\n$body");
			$this->bodyHTML("<html><body><h1>$head</h1><p>$body</p></body></html>"); 
			if($this->send()) {
				$this->message("Message successfully sent to $testEmail");
			} else {
				$this->warning("Message failed to send to $testEmail");
			}
		}
	}
}
