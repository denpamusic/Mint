<?php

namespace Mint\Console\Commands;

use Mint;

use Mint\Lock;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApiCallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:callback {--debug : Show command output} {txid : Transaction ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call callback method from CLI.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		$pid = (int)getmypid();

		$txid = $this->argument('txid');

		Log::info( 'ApiCallback(txid: ' . $txid . ', pid:' . $pid . '): Running...' );

		/* Acquire lock to prevent double execution */
		$fp = fopen( storage_path('locks/mint-' . $txid . '.lock'), 'w' );
		if ( !flock( $fp, LOCK_EX | LOCK_NB ) ) {
			Log::error( 'ApiCallback(txid: ' . $txid . ', pid:' . $pid . '): Task is already running! Halting...' );
			exit;
		}

		$request = Request::create('/api/callback', 'GET', [
				'key' => Mint\Settings::getVal('api_key'),
				'txid' => $this->argument('txid'),
		]);

		Request::replace( $request->input() );
		$response = Route::dispatch($request)->getContent();
		if( $this->option('debug') ) {
			$this->info($response);
		}

		/* Release lock after finishing execution */
		flock($fp, LOCK_UN);
		fclose($fp);
		unlink( storage_path('locks/mint-' . $txid . '.lock') );
    }
}
