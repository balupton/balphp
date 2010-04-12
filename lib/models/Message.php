<?php

/**
 * Message
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
class Bal_Message extends Base_Bal_Message
{
	
	/**
	 * Generate the Email's Content
	 * @return
	 */
	protected function _generateEmailContent ( ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$View = Bal_App::getView(true);
		$Message = $this;

		# Prepare Message
		$params				= array();
		$params['Message']	= is_array($Message) ? $Message : $Message->toArray(true);
		
		# Update Params
		$this->_updateParams($params);
		
		# Render
		$xhtml = 
			$View->doctype().
			'<html>'.
			'<head>'.
				$View->headTitle().
				$View->headLink().
				$View->headStyle().
			'</head>'.
			'<body class="email">'.
				'<div class="email-wrapper">'.
					'<div class="email-header">'.
						$Locale->translate('message-header',$params).
					'</div>'.
					'<div class="email-body">'.
						'<div class="email-from">'.
							$Locale->translate('message-from',$params).
						'</div>'.
						'<div class="email-title">'.
							$Locale->translate('message-title',$params).
						'</div>'.
						'<div class="email-description">'.
							$Locale->translate('message-description',$params).
						'</div>'.
					'</div>'.
					'<div class="email-footer">'.
						$Locale->translate('message-footer',$params).
					'</div>'.
				'</div>'.
			'</body>'.
			'</html>';
		
		# Return xhtml
		return $xhtml;
	}
	
	/**
	 * Update the translation params
	 * @return
	 */
	protected function _updateParams ( array &$params ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$View = Bal_App::getView(false);
		$View->url()->renege('route','app');
		$Message = $this;
		
		# Prepare Relations
		$UserFrom	= delve($Message,'UserFrom');
		$UserFor	= delve($Message,'UserFor');
		
		# Apply Relations
		$params['from']	= delve($UserFrom,'fullname','System');
		$params['for'] 	= delve($UserFor, 'fullname','System');
		
		# Prepare Urls
		$root_url		= $View->app()->getRootUrl();
		$base_url		= $View->app()->getBaseUrl(true);
		$Message_url	= $root_url.$View->url()->message($Message)->toString();
		$UserFrom_url	= delve($UserFrom,'id')	? $root_url.$View->url()->user($UserFrom)->toString()	: $root_url;
		$UserFor_url	= delve($UserFor,'id')	? $root_url.$View->url()->user($UserFor)->toString()	: $root_url;
		
		# Apply URLs
		$params['root_url'] 	= $root_url;
		$params['base_url'] 	= $base_url;
		$params['Message_url'] 	= $Message_url;
		$params['UserFrom_url']	= $UserFrom_url;
		$params['UserFor_url'] 	= $UserFor_url;
		
		# Chain
		return $this;
	}
	
	/**
	 * Shortcut Message Creation via Codes
	 * @return
	 */
	public function useTemplate ( $template, array $data = array() ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$Message = $this;
		
		# --------------------------
		
		# Prepare Message
		$params	= is_array($data) ? $data : array();
		$params['Message'] 	= $Message->toArray();
		
		# Handle Message
		$function = '_template'.magic_function($template);
		if ( method_exists($this, $function) ) {
			$this->$function($params,$data);
		}
		
		# Update Params
		$this->_updateParams($params);
		
		# --------------------------
		
		# Render
		$title = empty($this->title) ? $Locale->translate('message-'.$template.'-title', $params) : $Locale->translate_default('message-'.$template.'-title', $params, $this->title);
		$description = empty($this->description) ? $Locale->translate('message-'.$template.'-description', $params) : $Locale->translate_default('message-'.$template.'-description', $params, $this->description);
		
		# --------------------------
		
		# Apply
		$this->title = $title;
		$this->description = $description;
		$this->_set('template', $template, false);
		
		# Chain
		return $this;
	}
	
	/**
	 * Set Message Code: user-insert
	 * @return
	 */
	protected function _templateUserInsert ( &$params, &$data ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$View = Bal_App::getView(false);
		$View->url()->renege('route','app');
		$Message = $this;
		$UserFor = delve($Message,'UserFor');
		
		# Prepare Urls
		$rootUrl = $View->app()->getRootUrl();
		$baseUrl = $View->app()->getBaseUrl(true);
		
		# --------------------------
		
		# Prepare URL
		$activateUrl = $rootUrl.$View->url()->userActivate($UserFor)->toString();
		$params['User_url_activate'] = $activateUrl;

		# --------------------------
		
		return true;
	}
	
	/**
	 * Send the Message
	 * @return
	 */
	public function send ( ) {
		# Prepare
		$Message = $this;
		$UserFor = delve($Message,'UserFor');
		$mail = Bal_App::getConfig('mail');
		
		# Prepare Mail
		$mail['subject'] = $Message->title;
		$mail['html'] = $this->_generateEmailContent();
		$mail['text'] = strip_tags($mail['html']);
		
		# Create Mail
		$Mail = new Zend_Mail();
		$Mail->setFrom($mail['from']['address'], $mail['from']['name']);
		$Mail->setSubject($mail['subject']);
		$Mail->setBodyText($mail['text']);
		$Mail->setBodyHtml($mail['html']);
		
		# Add Receipient
		if ( delve($UserFor,'id') ) {
			$email = $UserFor->email;
			$fullname = $UserFor->fullname;
		} else {
			$email = $mail['from']['address'];
			$fullname = $mail['from']['name'];
		}
		$Mail->addTo($email, $fullname);
		
		# Send Mail
		$Mail->send();
		
		# Done
		$Message->sent_on = doctrine_timestamp();
		$Message->status = 'published';
		
		# Chain
		return $this;
	}
	
	/**
	 * Ensure Message
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensureMessage (  $Event, $Event_type ) {
		# Check
		if ( !in_array($Event_type,array('postSave','preInsert')) ) {
			# Not designed for these events
			return null;
		}
		
		# Prepare
		$save = false;
		
		# Fetch
		$Message = $Event->getInvoker();
		
		# preInsert
		if ( $Event_type === 'preInsert' ) {
			# Ensure Only One
			Doctrine_Query::create()
				->delete('Message m')
				->where('m.hash = ?', $Message->hash)
				->execute();
				;
			
			# Send On
			if ( !$Message->send_on ) {
				$Message->set('send_on', doctrine_timestamp(), false);
				$save = true;
			}
			
			# Prepare
			$UserFor = delve($Message,'UserFor');
			$UserFor_id = delve($UserFor,'id');
			
			# Hash
			$hash = md5($Message->send_on.$Message->title.$Message->description.$UserFor_id);
			if ( $Message->hash != $hash ) {
				$Message->set('hash', $hash, false);
				$save = true;
			}
		}
		elseif ( $Event_type === 'postSave' ) {
			# Send
			if ( $Message->id && empty($Message->sent_on) && strtotime($Message->send_on) <= time() ) {
				# We want to send now or earlier
				$Message->send();
				$save = true;
			} else {
				# We want to send later
				// do nothing
			}
		}
		
		# Done
		return $save;
	}
	
	/**
	 * Ensure Consistency
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public function ensure ( $Event, $Event_type ){
		return Bal_Doctrine_Core::ensure($Event,$Event_type,array(
			'ensureMessage'
		));
	}
	
	/**
	 * preSave Event
	 * @param Doctrine_Event $Event
	 * @return
	 */
	public function preSave ( $Event ) {
		# Prepare
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			// no need
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * postSave Event
	 * @param Doctrine_Event $Event
	 * @return
	 */
	public function postSave ( $Event ) {
		# Prepare
		$Invoker = $Event->getInvoker();
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			$Invoker->save();
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * Pre Insert Event
	 * @param Doctrine_Event $Event
	 */
	public function preInsert ( $Event ) {
		# Prepare
		$Message = $Event->getInvoker();
		$result = true;
		
		# Ensure
		if ( self::ensure($Event, __FUNCTION__) ) {
			// no need
		}
		
		# Done
		return method_exists(get_parent_class($this),$parent_method = __FUNCTION__) ? parent::$parent_method($Event) : $result;
	}
	
	/**
	 * Fetch the Messages
	 * @return array
	 */
	public static function fetchMessages ( array $params = array() ) {
		# Prepare
		Bal_Dontrine_Core::prepareFetchParams($params,array('Message','Booking','User','UserFrom','UserFor'));
		extract($params);
		
		# Query
		$Query = Doctrine_Query::create()
			->select('Message.*, UserFrom.fullname, UserFor.fullname')
			->from('Message, Message.UserFrom UserFrom, Message.UserFor UserFor')
			->orderBy('Message.send_on DESC')
			->andWhere('Message.sent_on <= ?', doctrine_timestamp());
		
		# Criteria
		if ( $User ) {
			$User = Bal_Dontrine_Core::resolveId($User);
			$Query->andWhere('UserFor.id = ? OR UserFrom.id = ?', array($User,$User));
		}
		if ( $UserFor ) {
			$UserFor = Bal_Dontrine_Core::resolveId($UserFor);
			$Query->andWhere('UserFor.id = ?', $UserFor);
		}
		if ( $UserFrom ) {
			$UserFrom = Bal_Dontrine_Core::resolveId($UserFrom);
			$Query->andWhere('UserFrom.id = ?', $UserFrom);
		}
		if ( $Message ) {
			$Message = Bal_Dontrine_Core::resolveId($Message);
			$Query->andWhere('Message.id = ?', $Message);
		}
		if ( $Booking ) {
			$Booking = Bal_Dontrine_Core::resolveId($Booking);
			$Query->andWhere('Booking.id = ?', $Booking);
		}
		
		# Fetch
		$result = Bal_Dontrine_Core::prepareFetchResult($params,$Query);
		
		# Done
		return $result;
	}
	
}
