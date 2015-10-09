<?php namespace Portal\Integrations\Slack\Commands\Voip;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MySecurePortal\Models\Voip\VoipBalanceLog;
use Portal\Foundation\Commands\Command;
use Portal\Integrations\Slack\Classes\SlackNotification;

class CreditAlert extends Command implements SelfHandling, ShouldQueue
{

    use SerializesModels, InteractsWithQueue;

    protected $rooms = ['directors'];
    protected $slack = null;
    protected $amountAvailable;

    public function __construct($amountAvailable)
    {
        $this->amountAvailable = $amountAvailable;
    }


    public function handle()
    {
        $this->slack = SlackNotification::create();
        $this->sendMessage("We only have £{$this->amountAvailable} of credit left.");
    }

    protected function sendMessage($message)
    {
        array_walk($this->rooms, function($channel) use ($message) {
            $this->slack->from('MySecurePortal')
                ->to($channel)
                ->withIcon('http://www.helpyourselfgetlucky.com/wp-content/uploads/2008/04/no-money.gif')
                ->send(
                    preg_replace("/(?<=^|(?<=[^a-zA-Z0-9-_\.]))(@\w+)/", "<$1>",
                        "@andrew @simonskinner \n {$message}"
                    ));
        });
    }



}
