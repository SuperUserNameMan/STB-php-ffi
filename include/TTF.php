<?php

TTF::TTF(); // autoinit

class TTF 
{

	//----------------------------------------------------------------------------------
	// Const Definition
	//----------------------------------------------------------------------------------

	const vmove  = 1 ;
	const vline  = 2 ;
	const vcurve = 3 ;
	const vcubic = 4 ;


	const MACSTYLE_DONTCARE    = 0 ;
	const MACSTYLE_BOLD        = 1 ;
	const MACSTYLE_ITALIC      = 2 ;
	const MACSTYLE_UNDERSCORE  = 4 ;
	const MACSTYLE_NONE        = 8 ; // <= not same as 0 ; this makes us check the bitfield is 0


	const PLATFORM_ID_UNICODE   = 0 ;
	const PLATFORM_ID_MAC       = 1 ;
	const PLATFORM_ID_ISO       = 2 ;
	const PLATFORM_ID_MICROSOFT = 3 ;


	const UNICODE_EID_UNICODE_1_0      = 0 ;
	const UNICODE_EID_UNICODE_1_1      = 1 ;
	const UNICODE_EID_ISO_10646        = 2 ;
	const UNICODE_EID_UNICODE_2_0_BMP  = 3 ;
	const UNICODE_EID_UNICODE_2_0_FULL = 4 ;


	const MS_EID_SYMBOL        =  0 ;
	const MS_EID_UNICODE_BMP   =  1 ;
	const MS_EID_SHIFTJIS      =  2 ;
	const MS_EID_UNICODE_FULL  = 10 ;



	const MAC_EID_ARABIC       = 4 ;
	const MAC_EID_CHINESE_TRAD = 2 ;   
	const MAC_EID_GREEK        = 6 ;
	const MAC_EID_HEBREW       = 5 ;
	const MAC_EID_JAPANESE     = 1 ;   
	const MAC_EID_KOREAN       = 3 ;   
	const MAC_EID_ROMAN        = 0 ;   
	const MAC_EID_RUSSIAN      = 7 ;


	const MS_LANG_CHINESE     = 0x0804 ;
	const MS_LANG_DUTCH       = 0x0413 ;
	const MS_LANG_ENGLISH     = 0x0409 ;
	const MS_LANG_FRENCH      = 0x040c ;
	const MS_LANG_GERMAN      = 0x0407 ;
	const MS_LANG_HEBREW      = 0x040d ;
	const MS_LANG_ITALIAN     = 0x0410 ;
	const MS_LANG_JAPANESE    = 0x0411 ;
	const MS_LANG_KOREAN      = 0x0412 ;
	const MS_LANG_RUSSIAN     = 0x0419 ;
	const MS_LANG_SPANISH     = 0x0409 ;
	const MS_LANG_SWEDISH     = 0x041D ;

	const MAC_LANG_ARABIC       = 12 ;
	const MAC_LANG_CHINESE_SIMP = 33 ;
	const MAC_LANG_CHINESE_TRAD = 19 ;
	const MAC_LANG_DUTCH        =  4 ;
	const MAC_LANG_ENGLISH      =  0 ;
	const MAC_LANG_FRENCH       =  1 ;   
	const MAC_LANG_GERMAN       =  2 ;
	const MAC_LANG_HEBREW       = 10 ;
	const MAC_LANG_ITALIAN      =  3 ;
	const MAC_LANG_JAPANESE     = 11 ;
	const MAC_LANG_KOREAN       = 23 ;
	const MAC_LANG_RUSSIAN      = 32 ;
	const MAC_LANG_SPANISH      =  6 ;
	const MAC_LANG_SWEDISH      =  5 ;   



	//----------------------------------------------------------------------------------
	// FFI initialisation
	//----------------------------------------------------------------------------------

	public static $ffi;

	static $ffi_typeof_void_p; 

	public static function TTF()
	{
		if ( static::$ffi ) 
		{ 
			debug_print_backtrace();
			exit("TTF::TTF() already init".PHP_EOL); 
		}
		
		$cdef = __DIR__ . '/TTF.ffi.php.h';
		
		$lib_dir = defined('FFI_LIB_DIR') ? FFI_LIB_DIR : 'lib' ;
		
		$slib = "./$lib_dir/libTTF.".PHP_SHLIB_SUFFIX;
		
		static::$ffi = FFI::cdef( file_get_contents( $cdef ) , $slib );
	}


	public static function __callStatic( string $method , array $args ) : mixed
	{
		$callable = [static::$ffi, 'stbtt_'.$method];
		return $callable(...$args);
	}
	
	//----------------------------------------------------------------------------------
	// Helpers
	//----------------------------------------------------------------------------------

	static $font_cache_data = []; //!\ double fonction du cache : mettre en cache, et éviter que le GC de PHP n'efface le tableau FFI au retour de TTF::Load()


	/***
			Charge une police .ttf ou un pack de polices .ttc

			le paramètre $font_index permet de spécifier le numéro de la police du pack .ttc
	*/
	public static function Load( $font_path , $font_index = 0 )
	{
		$font = static::$ffi->new("stbtt_fontinfo");

		if ( isset( static::$font_cache_data[ $font_path ] ) )
		{
			$font_data = static::$font_cache_data[ $font_path ];
		}
		else
		{
			$file_size = filesize( $font_path );
			$font_data = FFI::new( "uint8_t[$file_size]" );
			FFI::memcpy( $font_data , file_get_contents( $font_path ) , $file_size );

			static::$font_cache_data[ $font_path ] = $font_data ;

			//!\ sans ce cache, le GC de PHP effacerait le tableau FFI au retour de TTF::Load()
		}

		static::InitFont( FFI::addr( $font ) , $font_data , static::GetFontOffsetForIndex( $font_data , $font_index ) );

		return $font;
	}

};
