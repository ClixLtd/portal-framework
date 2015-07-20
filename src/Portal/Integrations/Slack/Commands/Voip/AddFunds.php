<?php namespace Portal\Integrations\Slack\Commands\Voip;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MySecurePortal\Models\Voip\VoipBalanceLog;
use Portal\Foundation\Commands\Command;
use Portal\Integrations\Slack\Classes\SlackNotification;

class AddFunds extends Command implements SelfHandling, ShouldQueue
{

    use SerializesModels, InteractsWithQueue;

    protected $rooms = ['test-channel'];
    protected $slack = null;
    protected $amountAvalilbe;

    public function __construct($amountAvailable)
    {
        $this->amountAvalilbe = $amountAvailable;
        dd($amountAvailable);
    }


    public function handle()
    {
        $this->slack = SlackNotification::create();
        $this->sendMessage(":troll: We only have £{$this->amountAvalilbe} left, top up is recommended soon.");
    }

    protected function sendMessage($message)
    {
        array_walk($this->rooms, function($channel) use ($message) {
            $this->slack->from('MySecurePortal')
                ->to($channel)
                ->send($message);
        });
    }



}