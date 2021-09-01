<?php

include("lib/SDL.php");
include("lib/STB.php");


$image_w = FFI::new("int");
$image_h = FFI::new("int");
$image_f = FFI::new("int");
$image = STBI::load( "../stb/test_320.png" , FFI::addr( $image_w ) , FFI::addr( $image_h ) , FFI::addr( $image_f ) , STBI::RGBA );

if ( $image === null )
{
	exit("image not found".PHP_EOL);
}

print_r( $image );
print_r( $image_w );
print_r( $image_h );
print_r( $image_f );



if ( SDL::BYTEORDER() == SDL::BIG_ENDIAN )
{
	$pformat = SDL::$PIXELFORMAT['RGBA8888'   ];
}
else
{
	$pformat = SDL::$PIXELFORMAT['ARGB8888'   ];
}

$depth = 32 ;
$pitch = $image_w->cdata * 4 ;


SDL::Init( SDL::INIT_VIDEO );

$win = SDL::CreateWindow( "STBI and SDL", 100, 100, 640, 480, SDL::WINDOW_SHOWN );

$win_surface = SDL::GetWindowSurface( $win );
$image_surface = SDL::CreateRGBSurfaceWithFormatFrom( $image , $image_w->cdata , $image_h->cdata , $depth , $pitch , $pformat );

$tend = microtime( true ) + 5.0 ;

while( $tend > microtime( true ) )
{

	SDL::FillRect( $win_surface , null , SDL::MapRGB( $win_surface->format, rand( 0 , 0xFF ),  rand( 0 , 0xFF ),  rand( 0 , 0xFF ) ) );

	SDL::BlitSurface( $image_surface , null , $win_surface , null ); //FFI::addr( SDL::GetWindowSurface( $win )->clip_rect ) );

	SDL::UpdateWindowSurface( $win );

	usleep( 100_000 );

}

//sleep(3);

SDL::FreeSurface( $image_surface );

SDL::DestroyWindow( $win );
SDL::Quit();



STBI::image_free( $image );

// EOF
