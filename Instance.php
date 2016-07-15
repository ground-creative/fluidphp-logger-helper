<?php

	// SET CONNECTION NAME AND TABLE FROM CONFIG FILE

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
		
		public function save( )
		{
			$this->_addClientData( );
			$this->session_id = static::$_sessionID;
			$this->request_data = json_encode( $_REQUEST );
			parent::save( );
		}
		
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
			static::$_connectionName = 'default';
			static::$_table =  null;
			return $class;
			
		}
	}