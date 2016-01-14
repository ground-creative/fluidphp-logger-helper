<?php


	// CATCH EXCEPTIONS
	// MAKE THE REGISTER DEBUGGER AUTOMATIC FROM CONFIG FILES
	// CREATE METHOD DEBUG SQL QUERIES
	// MAKE DEBUG METHOD USABLE ONLY IF DEBUG IS ON IN APPLICATION

	namespace helpers\Logger;
	
	class Manager
	{
		public static function msg( $msg )
		{
			$instance = new Instance( );
			return $instance->msg( 'message' , $msg );
		}
		
		public static function error( $msg )
		{
			$instance = new Instance( );
			return $instance->msg( 'error' , $msg );
		}
		
		public static function debug( $msg )
		{
			$instance = new Instance( );
			return $instance->msg( 'debug' , $msg );
		}
		
		public static function warning( $msg )
		{
			$instance = new Instance( );
			return $instance->msg( 'warning' , $msg );
		}
		
		public static function observe( $class = null )
		{
			return Instance::observe( $class );
		}
		
		public static function lastError( )
		{
			$error = error_get_last( );
			if ( 'Php Error' === \Debug::msgType( $error[ 'type' ] ) )
			{
				static::errorHandler( $error[ 'type' ] , $error[ 'message' ] , $error[ 'file' ] , $error[ 'line' ] );
			}
		} 
		
		public static function errorHandler( $errno , $errstr , $errfile , $errline )
		{
			if ( !( error_reporting( ) & $errno ) ) { return; }
			$err = \Debug::msgType( $errno );
			static::_php( $err , $errstr )->data( $errfile , false )->script( '' , $errline )->save( );
			return true;	// don't execute php error handler
		}
		
		public static function registerDebug( $level = null )
		{ 
			$level = ( $level ) ? $level : E_ALL ^ E_NOTICE;
			$called_class = get_called_class( );
			if ( !defined( '_PTCDEBUG_NAMESPACE_' ) )
			{
				error_reporting( $level );
				set_error_handler( array( $called_class , 'errorHandler' ) );
				register_shutdown_function( array( $called_class , 'lastError' ) ); 
			}
			else{ register_shutdown_function( array( $called_class , 'processBuffer' ) ); }
		}
		
		public static function processBuffer( )
		{
			$buffer = \Debug::getBuffer( );
			$errors = array( 'Php Notice' , 'Php Warning' , 'Php Error' );
			foreach ( $buffer[ 'log' ] as $k => $v )
			{
				if ( in_array( $v[ 'errno' ] , $errors ) )
				{
					$log = static::_php( $v[ 'errno' ] , $v[ 'errstr' ] )->data( $v[ 'errfile' ][ 0 ] , false );
					if ( $v[ 'class' ][ 0 ] && $v[ 'function' ][ 0 ] )
					{
						$log->script( $v[ 'class' ][ 0 ] . '::' . $v[ 'function' ][ 0 ] , $v[ 'errline' ][ 0 ] );
					}
					else if ( $v[ 'function' ][ 0 ] ){ $log->script( $v[ 'function' ][ 0 ] , $v[ 'errline' ][ 0 ] ); }
					else{ $log->script( '' , $v[ 'errline' ][ 0 ] ); }
					$log->save( );
				}
			}
		}
		
		protected static function _php( $errType , $errMsg )
		{
			$instance = new Instance( );
			return $instance->msg( $errType , $errMsg );
		}
	}