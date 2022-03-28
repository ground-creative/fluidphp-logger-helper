<?php

	namespace helpers\Logger;
	
	class Instance extends \Model
	{		
		protected static $_sessionID = null;
		
		public static function boot( )
		{
			static::$_sessionID = ( '' !== session_id( ) ) ? session_id( ) : \Auth::random( 26 );		
		}
		
		public function msg( $type , $msg )
		{
			$this->logtype = $type;
			$this->message = $msg;
			return $this;
		}
		
		public function script( $method , $line = null )
		{
			$this->method = $method;
			$this->line = $line;
			return $this;
		}
		
		public function data( $data , $encode = true )
		{
			$this->data = ( $encode ) ? json_encode( $data ) : $data;
			return $this;
		}
		
		public function extra( $extra )
		{
			foreach ( $extra as $k => $v ){ $this->{ $k } = $v; }
			return $this;
		}
		
		public function mail( $to , $from = null , $subject = 'Logger Mail {type}' , $replyTo = null )
		{
			$this->_mail =
			[
				'to' 		=>	$to ,
				'from'	=>	$from ,
				'subject'	=>	$subject ,
				'replyTo'	=>	$replyTo ,
			];
			return $this;
		}
		
		public function save( )
		{
			if ( 'debug' == $this->logtype && !\App::option( 'app.test_env' ) ){ return false; }
			$this->_addClientData( );
			$this->session_id = static::$_sessionID;
			$this->request_data = json_encode( $_REQUEST );
			parent::save( );
			if ( !empty( $this->_mail ) )
			{
				$this->_sendMail( );
			}
		}
		
		protected $_mail = [ ];
		
		protected function _addClientData( )
		{
			$this->ip_address = $_SERVER[ 'REMOTE_ADDR' ];
			$this->request_uri = $_SERVER[ 'REQUEST_URI' ];
			$this->user_agent = $_SERVER[ 'HTTP_USER_AGENT' ];
			$this->referer = ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) ? $_SERVER[ 'HTTP_REFERER' ] : null;
			$this->domain = \Router::getProtocol( ) . '://' . $_SERVER[ 'HTTP_HOST' ];
		}
		
		protected static function _initialize( )
		{
			$options = \App::options ( 'logger.' . ( \App::option( 'test_env' ) ? 'develop' : 'prod' ) );
			static::$_connectionName = $options[ 'connection' ];
			static::$_table =  $options[ 'table' ];
			$class = parent::_initialize( );
			//static::$_connectionName = 'default';
			//static::$_table =  null;
			return $class;
		}
		
		protected function _sendMail( )
		{
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			if ( $this->_mail[ 'from' ] )
			{
				$headers .= 'From: ' . $this->_mail[ 'from' ] . "\r\n";
				$headers .= 'Reply-To: ' . ( ( $this->_mail[ 'replyTo' ] ) ?  
							$this->_mail[ 'replyTo' ] : $this->_mail[ 'from' ] ) . "\r\n";
			}
			$headers .= "X-Mailer: PHP/" . phpversion( );
			$subject = str_replace( '{type}' , ucfirst( $this->logtype ) , $this->_mail[ 'subject' ] );
			$message = $this->message . "<br>";
			if ( $this->method )
			{
				$message .= $this->method;
				if ( $this->line )
				{
					$message .= ':' . $this->line;
				}
				$message .= "<br>";
			}
			if ( $this->data )
			{
				$message .= '<pre>' . print_r( $this->toArray( ) , true ) . '</pre>';
			}
			if ( !mail( $this->_mail[ 'to' ] , $subject , $message , $headers ) )
			{
				Logger::error( 'logger mail alert error' )
					->data( error_get_last( ) )
					->script( __METHOD__ , __LINE__ )
					->save( );
			}
			return $this;
		}
	}