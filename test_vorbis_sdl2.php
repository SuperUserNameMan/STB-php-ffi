
//////////////////////////////////////////////////////////////////////////////////

 Let's stream audio from a `.ogg` file using SDL2 Audio API

//////////////////////////////////////////////////////////////////////////////////

<?php

include("lib/SDL.php");
include("lib/STB.php");

// ---

$v_err = FFI::new("int");

$v = Vorbis::open_filename( "../stb/test.ogg" , FFI::addr( $v_err ) , null );

if ( $v === null )
{
	exit( "Vorbis::open_filename() : ".Vorbis::get_error_message( $v_err->cdata ).PHP_EOL );
}

// ---

$v_info = Vorbis::get_info( $v );
print_r( $v_info );

$v_samples = Vorbis::stream_length_in_samples( $v );

// ---

SDL::Init( SDL::INIT_AUDIO );

$audio_want = SDL::$ffi->new('SDL_AudioSpec');
$audio_have = SDL::$ffi->new('SDL_AudioSpec');

$audio_want->freq     = 48_000 ;
$audio_want->format   = SDL::$AUDIO['S16SYS'] ;
$audio_want->channels = 2 ;
$audio_want->samples  = 4096 ;
$audio_want->callback = null; // since SDL 2.0.4, we can use SDL::QueueAudio() instead

print_r( $audio_want );

$audio = SDL::OpenAudioDevice( null , 0 , FFI::addr( $audio_want ) , FFI::addr( $audio_have ) , 0 );

print_r( $audio_have );

// ----

if ( $audio === null )
{
	echo "SDL::OpenAudioDevice() : ".SDL::GetError().PHP_EOL;
}
else
{
	echo "Playing ...".PHP_EOL;

	SDL::PauseAudioDevice( $audio , 0 );

	$_buffer_size = 8192 ;

	$_buffer   = FFI::new("short[$_buffer_size]");

	$stream = SDL::NewAudioStream( SDL::$AUDIO['S16SYS'] , $v_info->channels , $v_info->sample_rate , $audio_have->format , $audio_have->channels , $audio_have->freq );

	while( $v_samples )
	{ 
		//for( $i = 0; $i<10; $i++ )
		{

			$_spc = Vorbis::get_frame_short_interleaved( $v , $v_info->channels , $_buffer , $_buffer_size ) ;

			$v_samples -= $_spc;

			$bytes = $_spc * $v_info->channels * 2 ;

			SDL::AudioStreamPut( $stream , $_buffer , $bytes );

			do
			{
				$bytes = SDL::AudioStreamGet( $stream , $_buffer , $_buffer_size );		
				SDL::QueueAudio( $audio , $_buffer , $bytes );
			}
			while( SDL::AudioStreamAvailable( $stream ) );
		}

		echo ( $v_samples / $v_info->sample_rate ).PHP_EOL; // seconds remaining

		while( SDL::GetQueuedAudioSize( $audio ) > ($_buffer_size*3) ); // let's wait a little
	}

	SDL::FreeAudioStream( $stream );
	
}


// ---

SDL::CloseAudioDevice( $audio );

Vorbis::close( $v );

SDL::Quit();

// EOF

