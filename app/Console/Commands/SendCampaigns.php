<?php

namespace App\Console\Commands;

use App\Services\CampaignSender;
use Illuminate\Console\Command;

class SendCampaigns extends Command
{
    protected $signature = 'campaigns:send';

    protected $description = 'Στέλνει την επόμενη δόση από τις μαζικές αποστολές προσφορών';

    public function handle(CampaignSender $sender): int
    {
        $this->info('Στάλθηκαν: '.$sender->tick());

        return self::SUCCESS;
    }
}
