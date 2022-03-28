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
		
		protected static function _getQB( )
		{
			$options = \App::options( 'logger.' . ( \App::option( 'test_env' ) ? 'develop' : 'prod' ) );
			$manager = static::_namespace( static::$_connectionManager , 'Db' );
			return call_user_func( $manager . '::getQB' , $options[ 'connection' ] );	
		}
		
		/*protected static function _initialize( )
		{
			$qb = static::_getQB( );
			if ( !array_key_exists( $class = get_called_class( ) , static::$_storage ) )
			{
				$options = \App::options( 'logger.' . ( \App::option( 'test_env' ) ? 'develop' : 'prod' ) );
				static::$_storage[ $class ][ 'table' ] = $options[ 'table' ];
				$qb->run( 'SHOW TABLES LIKE ?' , [ static::$_storage[ $class ][ 'table' ] ] );
				if ( !$qb->countRows( ) )
				{ 
					trigger_error( 'Table ' . static::$_storage[ $class ][ 'table' ] . 
								' does not exist, quitting now!' , E_USER_ERROR );
					return false;
				}
				static::$_storage[ $class ][ 'columns' ] = static::getColumns( );
				if ( method_exists( $class , 'boot' ) ){ static::boot( ); }
			}
			return $class;
		}*/
		
		public static function getColumns( )
		{
			$class= get_called_class( );
			if ( array_key_exists( 'columns' , static::$_storage[ $class ] ) )
			{ 
				return static::$_storage[ $class ][ 'columns' ]; 
			}
			$qb = static::_getQB( );
			$qb->setFetchMode( \PDO::FETCH_ASSOC );
			$columns = $qb->run( 'SHOW COLUMNS FROM ' . 
						$qb->addBackTicks( static::$_storage[ $class ][ 'table' ] ) );
			$cols = [ ];
			foreach ( $columns as $name ){ $cols[ $name[ 'Field' ] ] = $name[ 'Field' ]; }
			return static::$_storage[ $class ][ 'columns' ] = $cols;
		}
		
		protected static function _initialize( )
		{
			$options = \App::options( 'logger.' . ( \App::option( 'test_env' ) ? 'develop' : 'prod' ) );
			$class_name = get_called_class( );
			static::$_storage[ $class_name ][ 'table' ] = $options[ 'table' ];
			static::$_storage[ $class_name ][ 'columns' ] = static::getColumns( );
			$class = parent::_initialize( );
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