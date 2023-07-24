<?php

namespace App\Console\Commands;

use App\actions\DailyRiskMangement;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PgSql\Lob;

class DailyCostPerSaleRiskMngCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mng:cpr';

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
    public $DailyRiskMangement;
    public function __construct(DailyRiskMangement $DailyRiskMangement)
    {
        parent::__construct();
        $this->DailyRiskMangement = $DailyRiskMangement;

    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        Log::debug(Carbon::now()->toDateTimeString());


        // try {
        //     Log::debug("---------------------------------------------------------------------------------------");
        //     Log::debug("---------------------------------------------------------------------------------------");
        //     Log::debug("--------------------------- Starting Daily Ads Risk Mng -------------------------------");
        //     Log::debug("Current Time ------------------> ".Carbon::now()->toDateTimeString());

        //     $this->DailyRiskMangement->invoke();

        //     Log::debug("--------------------------- Finished Daily Risk Mng -------------------------------");
        //     Log::debug("---------------------------------------------------------------------------------------");

        //     return 0;
        // } catch (\Exception $e) {
        //     Log::debug("--------------------------- Error Durring  Daily Ads Risk Mng -------------------------------");
        //     Log::debug($e->getMessage());

        // }
    }
}
