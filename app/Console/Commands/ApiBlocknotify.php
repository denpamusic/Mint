<?php

namespace Mint\Console\Commands;

use Mint;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

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
