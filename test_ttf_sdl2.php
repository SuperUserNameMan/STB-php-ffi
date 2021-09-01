<?php

include("lib/SDL.php");
include("lib/STB.php");



$font = TTF::Load([ 
	'Linux'   => "/usr/share/fonts/truetype/freefont/FreeSerif.ttf" ,
	'Windows' => "c:/windows/fonts/times.ttf" ,
][ PHP_OS_FAMILY ]);

$font_size = TTF::ScaleForPixelHeight( FFI::addr( $font ) , 128 ) ;


// ----

$glyph_w = FFI::new("int32_t[1]"); // using array avoid the need of `FFI::addr()` and `->cdata`
$glyph_h = FFI::new("int32_t[1]");

$glyph_ord = mb_ord("A"); 

$text = "Test!";

// ---------

SDL::Init( SDL::INIT_VIDEO );

$win = SDL::CreateWindow( "TTF and SDL test", 100, 100, 256, 256, SDL::WINDOW_SHOWN );

$win_surface = SDL::GetWindowSurface( $win );

SDL::FillRect( $win_surface , null , SDL::MapRGB( $win_surface->format, 0xFF, 0xFF, 0xFF ) );
SDL::UpdateWindowSurface( $win );

// ---------

$palette = SDL::$ffi->new("SDL_Color[256]");

for( $i = 0 ; $i < 256 ; $i++ )
{
    $palette[ $i ]->r = $palette[ $i ]->g = $palette[ $i ]->b = 255-$i ;
}

// ---------

$pos = SDL::$ffi->new("SDL_Rect[1]");

$pos[0]->x = 10;
$pos[0]->y = 50;


for( $c = 0 ; $c < mb_strlen( $text ) ; $c++ )
{
	$glyph_ord = mb_ord( mb_substr( $text , $c , 1 ) );

	$glyph = TTF::GetCodepointBitmap( FFI::addr( $font ) , 0 , $font_size , $glyph_ord , $glyph_w , $glyph_h , null , null );


	$glyph_surface = SDL::CreateRGBSurfaceWithFormatFrom( $glyph , $glyph_w[0] , $glyph_h[0] , 8 , $glyph_w[0] , SDL::$PIXELFORMAT['INDEX8'] );
	SDL::SetPaletteColors( $glyph_surface->format->palette , $palette , 0 , 256 );

	SDL::BlitSurface( $glyph_surface , null , $win_surface , $pos );

	SDL::FreeSurface( $glyph_surface );
	TTF::FreeBitmap( $glyph , $font->userdata );

	SDL::UpdateWindowSurface( $win );

	$pos[0]->x += $glyph_w[0];

	sleep( 1 );
}

// ---------

sleep(3);

// ---------

SDL::DestroyWindow( $win );
SDL::Quit();
