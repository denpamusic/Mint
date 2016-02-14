<?php

namespace Mint\Console\Commands;

use Mint;
use Request;
use Route;
use Log;

use Illuminate\Console\Command;

class ApiBlocknotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:blocknotify  {--debug : Show command output} {blockhash : Hash of new best block.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call blocknotify method from CLI.';

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
		Log::info( 'Blocknotify(blockhash: ' . $this->argument('blockhash') . ', pid:' . (int)getmypid() . '): Running...' );

		$request = Request::create('/api/blocknotify', 'GET', [
				'key' => Mint\Settings::getVal('api_key'),
				'blockhash' => $this->argument('blockhash'),
		]);
		Request::replace( $request->input() );

		$response = Route::dispatch($request)->getContent();
		if( $this->option('debug') ) {
			$this->info($response);
		}
    }
}
