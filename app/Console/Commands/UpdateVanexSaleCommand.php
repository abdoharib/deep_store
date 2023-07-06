<?php

namespace App\Console\Commands;

use App\actions\updateVanexSalesAction;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;

class UpdateVanexSaleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:vanex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */

     public $updateVanexSalesAction;
    public function __construct( updateVanexSalesAction $updateVanexSalesAction)
    {
        parent::__construct();

        $this->updateVanexSalesAction = $updateVanexSalesAction;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->updateVanexSalesAction->invoke();
            Log::debug("successfully updated");
        }catch(\Exception $e){
            Log::debug($e->getMessage());
        }
        return 0;
    }
}
