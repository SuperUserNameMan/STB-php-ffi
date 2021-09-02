<?php

include("lib/STB.php");

// ---

$v_err = FFI::new("int");

$v = Vorbis::open_filename( "../stb/test.ogg" , FFI::addr( $v_err ) , null );

if ( $v === null )
{
	exit( "Vorbis::open_filename() : ".Vorbis::get_error_message( $v_err->cdata ).PHP_EOL );
}

// ---

$info = Vorbis::get_info( $v );

print_r( $info );

// ---

$comm = Vorbis::get_comment( $v );
echo "- Vendor : ".FFI::string( $comm->vendor ).PHP_EOL ;

for( $c = 0 ; $c < $comm->comment_list_length ; $c++ )
{
	echo "- Comment #$c : ".FFI::string( $comm->comment_list[ $c ] ).PHP_EOL ;
}

// ---

Vorbis::close( $v );


/* Should display something like that :

FFI\CData:struct <anonymous> Object
(
    [sample_rate] => 32000
    [channels] => 2
    [setup_memory_required] => 190856
    [setup_temp_memory_required] => 7209
    [temp_memory_required] => 4096
    [max_frame_size] => 1024
)
- Vendor : Xiph.Org libVorbis I 20120203 (Omnipresent)
- Comment #0 : GENRE=Cinematic
- Comment #1 : ALBUM=YouTube Audio Library
- Comment #2 : TITLE=Impact Moderato
- Comment #3 : ARTIST=Kevin MacGoodMusic

*/

// EOF


