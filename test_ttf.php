<?php

include("lib/TTF.php");


$chr = mb_ord("A"); 
$size = 40 ;

if ( PHP_OS_FAMILY == 'Linux' )
{
	$font_path = "/usr/share/fonts/truetype/freefont/FreeSerif.ttf" ;
}
else
{
	$font_path = "c:/windows/fonts/times.ttf" ;
}

$font = TTF::Load( $font_path );


$w = FFI::new("int32_t");
$h = FFI::new("int32_t");

$bitmap = TTF::GetCodepointBitmap( FFI::addr( $font ) , 0 , TTF::ScaleForPixelHeight( FFI::addr( $font ) , $size ), $chr , FFI::addr( $w ) , FFI::addr( $h ) , null , null );

$w = $w->cdata;
$h = $h->cdata;

//exit(0);
$grad = [ ' ' , '.' , ':' , 'i' , 'o' , 'V' , 'M' , '@' ];

for( $j = 0 ; $j < $h ; $j++ ) 
{
	for( $i = 0 ; $i < $w ; $i++ )
	{
		echo $grad[ $bitmap[ $j * $w + $i ] >> 5 ];
	}
	echo PHP_EOL;
}


TTF::FreeBitmap( $bitmap , $font->userdata );

