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

	public static function STB() : void
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

	//----------------------------------------------------------------------------------
	// Helpers
	//----------------------------------------------------------------------------------

	static $image_cache_data = []; //!\ dual function of the cache : 1) cache the data, 2) prevent the GC of PHP from erasing the FFI array when returning from STBI::Load()

	static function Load( string $image_path , int $desired_channels = STBI::DEFAULT ) : object
	{
		if ( isset( static::$image_cache_data[ $image_path ] ) )
		{
			$image = static::$image_cache_data[ $image_path ] ;
			$image->shares++;
			return $image ;
		}

		$_w = FFI::new("int");
		$_h = FFI::new("int");
		$_c = FFI::new("int");
		$_data = STB::stbi_load( $image_path , FFI::addr( $_w ) , FFI::addr( $_h ) , FFI::addr( $_c ) , $desired_channels );

		$image = (object)[
			'data'     => $_data ,
			'w'        => $_w->cdata ,
			'h'        => $_h->cdata ,
			'channels' => $_c->cdata ,
			'shares'   => 1 ,
			'path'     => $image_path ,
		];

		static::$image_cache_data[ $image_path ] = $image ;

		return $image ;
	}

	static function Free( object $image ) : void
	{
		if ( $image->shares > 0 )
		{
			$image->shares -- ;

			if ( $image->shares == 0 )
			{
				STB::stbi_image_free( $image->data );
				$image->data = null ;
				$image->w = 0 ;
				$image->h = 0 ;
				$image->channels = 0;

				unset( static::$image_cache_data[ $image->path ] );
			}
		}
	}
};


class TTF_Font_Data
{
	static array $cache = [];

	public object $data ;
	public int    $size ;

	public string $path ;

	static function Load( string $font_path ) : TTF_Font_Data
	{
		if ( isset( static::$cache[ $font_path ] ) )
		{
			return static::$cache[ $font_path ] ;
		}
	
		$font_data = new TTF_Font_Data( $font_path );

		static::$cache[ $font_path ] = $font_data ;

		return $font_data ;
	}

	function __construct( string $font_path )
	{
		$this->path = $font_path ;
		$this->size = filesize( $font_path ) ;
		$this->data = FFI::new( "uint8_t[".$this->size."]" , false ) ; // unmanaged by php's gc

		FFI::memcpy( $this->data , file_get_contents( $font_path ) , $this->size ) ;
	}

	function __destruct()
	{
		echo "Deleting font data : ".$this->path.PHP_EOL;
		FFI::free( $this->data );
	}
}


class TTF_Font
{
	public object $font ;
	public string $path ;
	public int   $index ;

	public object $metrics ;

	function __construct( string $font_path , int $font_index = 0 )
	{
		$this->font = STB::$ffi->new( "stbtt_fontinfo" , false ) ; // unmanaged by php's gc

		$this->path = $font_path ;
		$this->index = $font_index ;

		$font_data = TTF_Font_Data::Load( $font_path ); 

		TTF::InitFont( FFI::addr( $this->font ) , $font_data->data , TTF::GetFontOffsetForIndex( $font_data->data , $font_index ) ) ;

		$this->metrics = STB::$ffi->new('struct {'

			.'int text_w ;' 
			.'int text_h ;'

			.'int ascent ;'  //!\ raw data is unscaled !
			.'int descent ;' //!\ raw data is unscaled !
			.'int lineGap ;' //!\ raw data is unscaled !
			.'int lineHeight ;' //!\ raw data is unscaled !

			.'float scale ;'

			.'int advanceWidth ;' // scaled.
			.'int leftSideBearing ;' // scaled.

		.'}' , false ); // unmanaged by php's gc

		TTF::GetFontVMetrics( FFI::addr( $this->font ) , FFI::addr( $this->metrics->ascent ) , FFI::addr( $this->metrics->descent ) , FFI::addr( $this->metrics->lineGap ) );

		$this->metrics->lineHeight = $this->metrics->ascent - $this->metrics->descent + $this->metrics->lineGap ;
	}

	function __destruct()
	{
		echo "Deleting font struct : ".$this->path."[".$this->index."]".PHP_EOL;
		FFI::free( $this->metrics );
		FFI::free( $this->font );
	}
}

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

	/***
			Load a `.ttf` font or a font from a `.ttc` font pack

			Parameter $font_index let choose which font from the `.ttc` pack.
	*/
	public static function Load( string $font_path , int $font_index = 0 ) : TTF_Font
	{
		$font = new TTF_Font( $font_path , $font_index );

		return $font;
	}


	/***
			Returns the metrics of a text string and each lines of the text ...

			Params :
			- `$text` is a string that may contain several lines ;
			- `$font` is the TTF_Font object returned by `TTF::Load()` ;
			- `$text_h` is the maximum height of a character in pixels ;
			- `$rect` contain the maximum width and height in pixels of the canvas on which the text will be printed.
			- `$auto_wrap` indicates if this function wrap the text when it overflow on the right side of the rect
			- `$grid` see code 

			Return : an array as `[ array $lines , int $w , int $h ]` where :
				- `$lines` is an array of each lines of the text which may be cut to fit the canvas ;
				- `$w` and `$h` dimensions of the canvas ;
	*/
	public static function text_metrics( string $text , TTF_Font $font , int $text_h , array $rect = [] , bool $auto_wrap = false , ?object $grid = null ) : array // [ $lines , $w , $h ]
	{
		$rect[ 0 ] ??= 0 ;
		$rect[ 1 ] ??= 0 ;
		$rect[ 2 ] ??= PHP_INT_MAX ;
		$rect[ 3 ] ??= PHP_INT_MAX ;

		//echo "text_metrics:";print_r( $rect );

		list( $offset_x , $offset_y , $max_w , $max_h ) = $rect ;

		$grid ??= (object)[ 
			'cpos_to_line' => [] , // for each chr pos store the line number
			'line_to_xpos' => [] , // for each line store a list of char pos in pixel
			'line_to_cpos' => [] , // for each line store the chr pos of its first chr
			'last_line'    => 0 ,
			'line_count'   => 0 ,
			'last_cpos'    => 0 ,
			'char_count'   => 0 ,
		]; 

		$font->metrics->scale = TTF::ScaleForPixelHeight( FFI::addr( $font->font ) , $text_h );

		$lines = [] ; 

		$line_w  = 0;
		$_line_w = 0;

		$line_num = 0 ;

		$line_beg  = 0 ;

		$line_grid = [ 0 ];

		$last_char = mb_strlen( $text ) - 1 ;

		$_last_space_pos = -1;
		$_last_space_w   = 0;

		for( $pos = 0 ; $pos <= $last_char ; $pos++ )
		{
			$chr = mb_substr( $text , $pos , 1 );
			$ord = mb_ord( $chr );

			if ( $chr == ' ' )
			{
				$_last_space_pos = $pos ;
				$_last_space_w   = $_line_w ;
			}

			static::GetCodepointHMetrics( FFI::addr( $font->font ) , $ord , FFI::addr($font->metrics->advanceWidth) , FFI::addr($font->metrics->leftSideBearing)  );

			$_line_w += $font->metrics->advanceWidth * $font->metrics->scale ;

			$line_grid[] = (int)$_line_w ;

			$grid->cpos_to_line[ $pos ] = $line_num ;

			$wrap_now = ( $auto_wrap and ( $_line_w >= $max_w ) ) ;

			if ( $chr === "\n" or $wrap_now )
			{
				if ( $wrap_now and $_last_space_pos >= 0 )
				{
					$line_len = $_last_space_pos - $line_beg + 1 ;
					$lines[ $line_num ] = mb_substr( $text , $line_beg , $line_len ) ; //. '|';

					$grid->line_to_cpos[ $line_num ] = $line_beg ;

					$line_beg = $_last_space_pos + 1 ;
					$pos = $_last_space_pos ;
				}
				else
				{
					$line_len = $pos - $line_beg + 1 ;
					$lines[ $line_num ] = mb_substr( $text , $line_beg , $line_len );

					$grid->line_to_cpos[ $line_num ] = $line_beg ;

					$line_beg = $pos + 1 ;
				}

				$grid->line_to_xpos[ $line_num ] = array_slice( $line_grid , 0 , $line_len );
				
				$_last_space_pos = -1 ;
				$_last_space_w   = 0 ;

				$line_num ++ ;

				$line_w = max( $line_w , $_line_w );
				$_line_w = 0 ;

				$line_grid = [ 0 ];
			}
		}

		$lines[ $line_num ] = mb_substr( $text , $line_beg );

		$grid->line_to_cpos[ $line_num ] = $line_beg ;
		$grid->line_to_xpos[ $line_num ] = array_slice( $line_grid , 0 , mb_strlen( $lines[ $line_num ] ) + 1 );

		$grid->last_line  = $line_num ;
		$grid->line_count = $line_num + 1 ;

		$grid->last_cpos  = max( 0 , $last_char );
		$grid->char_count = $last_char + 1 ;

		$grid->cpos_to_line[ $grid->char_count ] = $line_num ;

		$w = max( $line_w , $_line_w );
		$h = count( $lines ) * $font->metrics->lineHeight * $font->metrics->scale ;

		$font->metrics->text_w = min( $w , $max_w ) ;
		$font->metrics->text_h = min( $h , $max_h ) ;

//		if ( $text == '' )
//		{
//			print_r( $lines );
//			print_r( $grid );
//		}

		return [ $lines , $w , $h ];
	}



	/***
			Print a text using a callback, and the lines and metrics provided by ::text_metrics()

			$cb_print_glyph = function( object $glyph , int $x , int $y , int $w , int $h , array &$cb_data )
	*/
	public static function print_lines( array $lines , TTF_Font $font , callable $cb_print_glyph , array &$cb_user_data ) : void
	{

		static $glyph_rect = null ; $glyph_rect ??= FFI::new("struct { int x, y, w, h ; }");

		$line_h = $font->metrics->lineHeight * $font->metrics->scale ;
		$line_n = 0 ;

		foreach( $lines as $line_n => $line )
		{
			$len = mb_strlen( $line );

			$pos_x = 0 ;
			$pos_y = ( ( $font->metrics->ascent + $font->metrics->lineGap/2 ) * $font->metrics->scale ) + ( $line_h * $line_n ) ;		

			for( $c = 0 ; $c < $len ; $c++ )
			{
				$glyph_ord = mb_ord( mb_substr( $line , $c , 1 ) );

				if ( $glyph_ord < 0 or $glyph_ord >= 32 )
				{
					//echo(__FILE__.','.__LINE__.':');print_r( $glyph_rect );
					$glyph = static::GetCodepointBitmap( 
							FFI::addr( $font->font )  , 0 , $font->metrics->scale , $glyph_ord , 
							FFI::addr( $glyph_rect->w ) , FFI::addr($glyph_rect->h) , FFI::addr($glyph_rect->x) , FFI::addr($glyph_rect->y) 
					);

					static::GetCodepointHMetrics( FFI::addr($font->font) , $glyph_ord , FFI::addr($font->metrics->advanceWidth) , FFI::addr($font->metrics->leftSideBearing)  );

					//echo "'".mb_chr( $glyph_ord )."' : "; print_r( $glyph );

					$pos_x += $glyph_rect->x;

					$line_y = $pos_y ;

					$pos_y += $glyph_rect->y ;

					if ( $glyph !== null )
					{
						$cb_print_glyph( $glyph , (int)$pos_x , (int)$pos_y , (int)$glyph_rect->w , (int)$glyph_rect->h , $cb_user_data );
					}

					$pos_x += $font->metrics->advanceWidth * $font->metrics->scale - $glyph_rect->x ;
					$pos_y = $line_y ;
				
					if ( $glyph !== null )
					{
						static::FreeBitmap( $glyph , $font->font->userdata );	
					}
				
				} // endif glyph >= 32
			} // end for $c
		} //  end foreach $line

		return;
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


	//----------------------------------------------------------------------------------
	// Helpers
	//----------------------------------------------------------------------------------

	public static function get_error_message( int $err ) : string
	{
		return [
			0 => "Ok" ,
				
			static::ERR_need_more_data         => "ERR_need_more_data" , // not a real error

			static::ERR_invalid_api_mixing     => "ERR_invalid_api_mixing" ,  // can't mix API modes
			static::ERR_outofmem               => "ERR_outofmem" ,  // not enough memory
			static::ERR_feature_not_supported  => "ERR_feature_not_supported" ,  // uses floor 0
			static::ERR_too_many_channels      => "ERR_too_many_channels" ,  // STB_const MAX_CHANNELS is too small
			static::ERR_file_open_failure      => "ERR_file_open_failure" ,  // fopen() failed
			static::ERR_seek_without_length    => "ERR_seek_without_length" ,  // can't seek in unknown-length file

			static::ERR_unexpected_eof         => "ERR_unexpected_eof" , // file is truncated?
			static::ERR_seek_invalid           => "ERR_seek_invalid" , // seek past EOF

			// vorbis errors:
			static::ERR_invalid_setup          => "ERR_invalid_setup" ,
			static::ERR_invalid_stream         => "ERR_invalid_stream" ,

			// ogg errors:
			static::ERR_missing_capture_pattern          => "ERR_missing_capture_pattern" ,
			static::ERR_invalid_stream_structure_version => "ERR_invalid_stream_structure_version" ,
			static::ERR_continued_packet_flag_invalid    => "ERR_continued_packet_flag_invalid" ,
			static::ERR_incorrect_stream_serial_number   => "ERR_incorrect_stream_serial_number" ,
			static::ERR_invalid_first_page               => "ERR_invalid_first_page" ,
			static::ERR_bad_packet_type                  => "ERR_bad_packet_type" ,
			static::ERR_cant_find_last_page              => "ERR_cant_find_last_page" ,
			static::ERR_seek_failed                      => "ERR_seek_failed" ,
			static::ERR_ogg_skeleton_not_supported       => "ERR_ogg_skeleton_not_supported" ,
		][ $err ];
	}
}

//EOF
