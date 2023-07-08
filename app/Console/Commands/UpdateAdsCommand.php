<?php

namespace App\Console\Commands;

use App\actions\adsRiskMangement;
use App\actions\updateAdsAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateAdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ads';

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

     private $updateAdsAction=null;
     private $adsRiskMangement=null;

    public function __construct(updateAdsAction $updateAdsAction, adsRiskMangement $adsRiskMangement)
    {
        parent::__construct();

        $this->updateAdsAction = $updateAdsAction;
        $this->adsRiskMangement = $adsRiskMangement;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // $this->updateAdsAction->invoke();
            $this->adsRiskMangement->invoke();
            Log::debug("successfully updated");
        }catch(\Exception $e){
            Log::debug($e->getMessage());
        }
    }
}
