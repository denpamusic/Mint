<?php

namespace Mint\Console\Commands;

use Mint;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

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
		$request = Request::create('/api/callback', 'GET', [
				'key' => Mint\Settings::getVal('api_key'),
				'txid' => $this->argument('txid'),
		]);
		Request::replace( $request->input() );

		$response = Route::dispatch($request)->getContent();
		if( $this->option('debug') ) {
			$this->info($response);
		}
    }
}
