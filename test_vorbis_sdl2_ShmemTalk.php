
//////////////////////////////////////////////////////////////////////////////////

 Open a background PHP process to stream an `.ogg` file using SDL2 Audio API

//////////////////////////////////////////////////////////////////////////////////

<?php

if ( ! class_exists( "ShmemTalk" ) )
{
	require_once( "include/ShmemTalk.php" );
}


// If we call this script without arguments, it is the main Master process :

if ( $argc == 1 ) 
{
	$worker = new ShmemTalk( __FILE__ );

	echo "Setting vorbis file ...".PHP_EOL;
	$worker->Set( "file" , "../stb/test.ogg" );

	sleep( 1 );

	echo "Play ...".PHP_EOL;
	$worker->Set( 0 , "play" )->Synch( true );

	sleep( 3 );

	echo "Pause ...".PHP_EOL;
	$worker->Set( 0 , "pause" )->Synch( true );

	sleep( 1 );

	echo "Play ...".PHP_EOL;
	$worker->Set( 0 , "play" )->Synch( true );

	sleep( 3 );

	echo "Stop ...".PHP_EOL;
	$worker->Set( 0 , "stop" )->Synch( true );

	sleep( 1 );

	echo "Play ...".PHP_EOL;
	$worker->Set( 0 , "play" )->Synch( true );

	echo PHP_EOL;

	while(1)
	{
		if ( $worker->Synch() )
		{
			$pos  = (int)( $worker->Get("pos") * 100.0 );
			$loop = $worker->Get("loops") + 1;

			echo "\r- position $pos % ; loop #$loop   ";

			//print_r( $worker->data );

			if ( $loop > 2 ) break;
		}

		sleep( 1 );
	}

	echo PHP_EOL;

	echo "Quit ...".PHP_EOL;
	$worker->Set( 0 , "quit" )->Synch( true );

	sleep( 1 );

	echo "Closing ...".PHP_EOL;
	$worker->Close();

	exit();
}

// ---------------- Worker Process ----------------------------




require_once("lib/SDL.php");
require_once("lib/STB.php");

$master = new ShmemTalk();

// ----- Init Audio ------


SDL::Init( SDL::INIT_AUDIO );

$audio_want = SDL::$ffi->new('SDL_AudioSpec');
$audio_have = SDL::$ffi->new('SDL_AudioSpec');

$audio_want->freq     = 48_000 ;
$audio_want->format   = SDL::$AUDIO['S16SYS'] ;
$audio_want->channels = 2 ;
$audio_want->samples  = 4096 ;
$audio_want->callback = null; // since SDL 2.0.4, we can use SDL::QueueAudio() instead

$audio = SDL::OpenAudioDevice( null , 0 , FFI::addr( $audio_want ) , FFI::addr( $audio_have ) , 0 );

if ( $audio === null )
{
	$master->Set( "ERR" , "SDL::OpenAudioDevice() : ".SDL::GetError().PHP_EOL );
	$master->Set( 1 , "ERR" );
	
	SDL::Quit();

	exit(0);
}

SDL::PauseAudioDevice( $audio , 0 ); 

$_buffer_size = 8192 ;
$_buffer      = FFI::new("short[$_buffer_size]") ;

// ----------

function load_vorbis( string $file ) : object|null
{
	$v_err = FFI::new("int");


	$v = Vorbis::open_filename( "../stb/test.ogg" , FFI::addr( $v_err ) , null );


	return $v;
}

function open_stream_for( object $vorbis ) : array
{
	global $audio_have;


	$v_info = Vorbis::get_info( $vorbis );


	$stream = SDL::NewAudioStream( SDL::$AUDIO['S16SYS'] , $v_info->channels , $v_info->sample_rate , $audio_have->format , $audio_have->channels , $audio_have->freq );


	return [ $stream , $v_info ];
}

function close_stream( object|null $stream )
{
	if ( $stream !== null )
	{
		SDL::FreeAudioStream( $stream );
	}

	return null;
}

// -----

define( 'IS_STOPPED' , -1 );
define( 'IS_PAUSED'  ,  0 );
define( 'IS_PLAYING' ,  1 );

// -----

function cmd_play()
{
	global $master;
	global $stream;
	global $vorbis;
	global $vorbis_info;
	global $status;

	if ( $stream == null and $vorbis == null and $master->Get( 'file' ) )
	{
		$vorbis = load_vorbis( $master->Get( 'file' ) );

		if ( $vorbis === null )
		{
			$master->Set( 'ERR' , "Could not open vorbis file : ".$master->Get('file') );
			$master->Set( 1 , 'ERR' );
		}
		else
		{
			$master->Set( 'pos'   , 0 );
			$master->Set( 'loops' , 0 );

			list( $stream , $vorbis_info ) = open_stream_for( $vorbis );
		}
	}

	$status = IS_PLAYING ;
}

function cmd_pause()
{
	global $status ;

	$status = IS_PAUSED;
}

function cmd_stop()
{
	global $master ;
	global $stream ;
	global $vorbis ;
	global $status ;


	$master->Set( 'pos'   , 0 );
	$master->Set( 'loops' , 0 );

	if ( $stream ) 
	{
		SDL::FreeAudioStream( $stream );
		$stream = null; 
	}

	if ( $vorbis )
	{
		Vorbis::close( $vorbis );
		$vorbis = null;
	}

	$status = IS_STOPPED ;
}

// -----

function do_play_vorbis()
{
	global $audio;
	global $master;
	global $stream;
	global $vorbis;
	global $vorbis_info;
	global $status;
	global $_buffer;
	global $_buffer_size;


	$_spc = Vorbis::get_frame_short_interleaved( $vorbis , $vorbis_info->channels , $_buffer , $_buffer_size ) ;


	if ( $_spc <= 0 )
	{
		Vorbis::seek_start( $vorbis );
		$_spc = Vorbis::get_frame_short_interleaved( $vorbis , $vorbis_info->channels , $_buffer , $_buffer_size ) ;

		$master->Inc( 'loops' );

		//file_put_contents( "out.txt", time().' : '.print_r( $master->changes, true ).PHP_EOL , FILE_APPEND );
	}


	$bytes = $_spc * $vorbis_info->channels * 2 ;

	SDL::AudioStreamPut( $stream , $_buffer , $bytes );

	$master->Set( "pos" , Vorbis::get_sample_offset( $vorbis ) / Vorbis::stream_length_in_samples( $vorbis ) );

	do
	{
		$bytes = SDL::AudioStreamGet( $stream , $_buffer , $_buffer_size );		

		SDL::QueueAudio( $audio , $_buffer , $bytes );
	}
	while( SDL::AudioStreamAvailable( $stream ) );

}

// -----

$vorbis = null ;
$vorbis_info = null;

$stream = null ;
$status = IS_STOPPED;

$check_master_timeout = microtime( true ) + 1.0 ;

while(1)
{	
	if ( $check_master_timeout < microtime( true ) )
	{
		if ( ! $master->master_is_alive() )
		{
			break;
		}
		
		$check_master_timeout += 1.0 ;
	}

	if ( $master->Synch() )
	{
		file_put_contents( "out.txt", time()." : ".$master->Get(0).PHP_EOL , FILE_APPEND );

		switch( $master->Get(0) )
		{
			case 'play': 
				cmd_play(); 
			break;

			case 'pause':
				cmd_pause();
			break;

			case 'stop':
				cmd_stop();
			break;

			case 'quit':
				cmd_stop();
			break 2;
		}
	}

	if ( SDL::GetQueuedAudioSize( $audio ) <= $_buffer_size*2 ) 
	{
		switch( $status )
		{
			case IS_PLAYING :
				do_play_vorbis();
			break;
		}
	}

	usleep( 1_000 ); 
}

// ---

SDL::CloseAudioDevice( $audio );
SDL::Quit();

// EOF

