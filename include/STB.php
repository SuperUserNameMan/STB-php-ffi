<?php

STB::STB(); // autoinit


/***
	`STB::` can be used to call any included stb API :

	- `STB::stbi_*`       : API from `stb_image.h` and `stb_image_write.h`
	- `STB::stbir_*`      : API from `stb_image_resize.h`
	- `STB::stbrp_*`      : API from `stb_rect_pack.h`
	- `STB::stbtt_*`      : API from `stb_truetype.h`
	- `STB::stb_vorbis_*` : API from `stb_vorbis.c`

	`STBI::*` is a shortcut to both `STB::stbi_*` and to `STB::stbir_*` ; it also contains their constants and helpers.

	`TTF::*` is a shortcut to `STB::stbtt_*`, and also contains dedicated constants and helpers.

	`Vorbis::*` is a shortcut to `STB::stb_vorbis_*` and contains dedicated constants and helpers.

*/

class STB
{
	//----------------------------------------------------------------------------------
	// FFI initialisation
	//----------------------------------------------------------------------------------

	public static $ffi;

	static $ffi_typeof_void_p; 

	public static function STB()
	{
		if ( static::$ffi ) 
		{ 
			debug_print_backtrace();
			exit("STB::STB() already init".PHP_EOL); 
		}
		
		$cdef = __DIR__ . '/STB.ffi.php.h';
		
		$lib_dir = defined('FFI_LIB_DIR') ? FFI_LIB_DIR : 'lib' ;
		
		$slib = "./$lib_dir/libSTB.".PHP_SHLIB_SUFFIX;
		
		static::$ffi = FFI::cdef( file_get_contents( $cdef ) , $slib );
	}


	public static function __callStatic( string $method , array $args ) : mixed
	{
		$callable = [static::$ffi, $method];
		return $callable(...$args);
	}

};


class STBI
{
	// stbi_ const ----------------------------------------------------------

	const DEFAULT    = 0 ; // only used for desired_channels
	const GREY       = 1 ;
	const GREY_ALPHA = 2 ; 
   	const RGB        = 3 ;
	const RGBA       = 4 ;


	// stbir_resize_ const ---------------------------------------------------------

	const ALPHA_CHANNEL_NONE = -1 ;

	const FLAG_ALPHA_PREMULTIPLIED   = 1 ;
	const FLAG_ALPHA_USES_COLORSPACE = 2 ;

	// typedef enum stbir_edge :
	const EDGE_CLAMP   = 1 ;
	const EDGE_REFLECT = 2 ;
	const EDGE_WRAP    = 3 ;
	const EDGE_ZERO    = 4 ;

	// typedef enum stbir_filter :
	const FILTER_DEFAULT      = 0 ;  // use same filter type that easy-to-use API chooses
	const FILTER_BOX          = 1 ;  // A trapezoid w/1-pixel wide ramps, same result as box for integer scale ratios
	const FILTER_TRIANGLE     = 2 ;  // On upsampling, produces same results as bilinear texture filtering
	const FILTER_CUBICBSPLINE = 3 ;  // The cubic b-spline (aka Mitchell-Netrevalli with B=1,C=0), gaussian-esque
	const FILTER_CATMULLROM   = 4 ;  // An interpolating cubic spline
	const FILTER_MITCHELL     = 5 ;  // Mitchell-Netrevalli filter with B=1/3, C=1/3

	// typedef enum stbir_colorspace :
	const COLORSPACE_LINEAR = 0 ;
	const COLORSPACE_SRGB   = 1 ;
	const MAX_COLORSPACES   = 2 ;

	// typedef enum stbir_datatype :
	const TYPE_UINT8  = 0 ;
	const TYPE_UINT16 = 1 ;
	const TYPE_UINT32 = 2 ;
	const TYPE_FLOAT  = 3 ;
	const MAX_TYPES   = 4 ;

	//----------------------------------------------------------------------------------
	// FFI callback
	//----------------------------------------------------------------------------------

	public static function __callStatic( string $method , array $args ) : mixed
	{
		if ( str_starts_with( $method , 'resize_' ) )
		{
			$callable = [STB::$ffi, 'stbir_'.$method];		
		}
		else
		{
			$callable = [STB::$ffi, 'stbi_'.$method];
		}
		return $callable(...$args);
	}
};

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
	// FFI callback
	//----------------------------------------------------------------------------------


	public static function __callStatic( string $method , array $args ) : mixed
	{
		$callable = [STB::$ffi, 'stbtt_'.$method];
		return $callable(...$args);
	}
	
	//----------------------------------------------------------------------------------
	// Helpers
	//----------------------------------------------------------------------------------

	static $font_cache_data = []; //!\ dual function of the cache : 1) cache the data, 2) prevent the GC of PHP from erasing the FFI array when returning from TTF::Load()


	/***
			Load a `.ttf` font or a `.ttc` font pack

			Parameter $font_index let choose which font from the `.ttc` pack.
	*/
	public static function Load( $font_path , $font_index = 0 )
	{
		$font = STB::$ffi->new("stbtt_fontinfo");

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

			//!\ without this cache, the GC of PHP would erase the FFI array as soon as returning from TTF::Load()
		}

		static::InitFont( FFI::addr( $font ) , $font_data , static::GetFontOffsetForIndex( $font_data , $font_index ) );

		return $font;
	}

};


class Vorbis
{

	// enum STBVorbisError :
	const ERR__no_error              = 0 ;

	const ERR_need_more_data         = 1 ;  // not a real error

	const ERR_invalid_api_mixing     = 2 ;  // can't mix API modes
	const ERR_outofmem               = 3 ;  // not enough memory
	const ERR_feature_not_supported  = 4 ;  // uses floor 0
	const ERR_too_many_channels      = 5 ;  // STB_const MAX_CHANNELS is too small
	const ERR_file_open_failure      = 6 ;  // fopen() failed
	const ERR_seek_without_length    = 7 ;  // can't seek in unknown-length file

	const ERR_unexpected_eof         = 10 ; // file is truncated?
	const ERR_seek_invalid           = 11 ; // seek past EOF

	// decoding errors (corrupt/invalid stream) -- you probably
	// don't care about the exact details of these

	// vorbis errors:
	const ERR_invalid_setup          = 20 ;
	const ERR_invalid_stream         = 21 ;

	// ogg errors:
	const ERR_missing_capture_pattern          = 30 ;
	const ERR_invalid_stream_structure_version = 31 ;
	const ERR_continued_packet_flag_invalid    = 32 ;
	const ERR_incorrect_stream_serial_number   = 33 ;
	const ERR_invalid_first_page               = 34 ;
	const ERR_bad_packet_type                  = 35 ;
	const ERR_cant_find_last_page              = 36 ;
	const ERR_seek_failed                      = 37 ;
	const ERR_ogg_skeleton_not_supported       = 38 ;

	//----------------------------------------------------------------------------------
	// FFI callback
	//----------------------------------------------------------------------------------

	public static function __callStatic( string $method , array $args ) : mixed
	{
		$callable = [STB::$ffi, 'stb_vorbis_'.$method];		
		return $callable(...$args);
	}
}
