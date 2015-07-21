<?php namespace Portal\Integrations\Slack\SlackCommands;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\Queue;
use MySecurePortal\Models\Voip\VoipBalanceLog;
use MySecurePortal\Models\Voip\VoipProvider;
use MySecurePortal\Models\Voip\VoipTopupLog;
use Portal\Integrations\Slack\Contracts\SlackCommand;

class SlackVoip extends SlackCommand
{

    public function getHelp()
    {
        return [
            ['funds [provider]', 'show\'s the remanding funds for a provider'],
            ['topup [amount] [provider]', 'top-up adds funds to a provider'],
        ];
    }

    public function callFunds()
    {
        $providerNameOrId = empty($this->splitText[0])?1:$this->splitText[0];

        $provider = VoipProvider::where('id', $providerNameOrId)->orWhere('slug', $providerNameOrId)->first(['id', 'name']);

        if(is_null($provider)) {
            return "Unable to find provider '{$providerNameOrId}'";
        }

        $funds =  VoipBalanceLog::where('provider_id', $provider->id)
            ->orderBy('created_at','desc')
            ->first(['balance', 'created_at']);

        return 'The last funds for "' . $provider->name . '" was £' . $funds->balance/100 . ' this was taken at ' . $funds->created_at->format('H:i');
    }

    public function callTopup()
    {

        if(!is_numeric($this->splitText[0]) || strpos($this->splitText[0], '.') === false) {
            return 'top-up amount must be a number or isn\'t a decimal';
        }

        $providerNameOrId = empty($this->splitText[1])?1:$this->splitText[1];

        $provider = VoipProvider::where('id', $providerNameOrId)
            ->orWhere('slug', $providerNameOrId)
            ->first(['id', 'name']);

        if(is_null($provider)) {
            return 'Unable to find provider "' . $providerNameOrId . '"';
        }

        VoipTopupLog::create([
            'amount' => $this->splitText[0]*100,
            'provider_id' => $provider->id,
            'created_at' => Carbon::now(),
        ]);

        return "top-up has been registered with '{$provider->name}' of £" . $this->splitText[0];

    }

}