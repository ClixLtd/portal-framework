<?php namespace Portal\Integrations\Slack\Contracts;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Portal\Integrations\Slack\Classes\SlackNotification;
use Portal\Foundation\Commands\Command;

class SlackCommand extends Command implements SelfHandling, ShouldBeQueued
{

    use DispatchesCommands, SerializesModels;

    protected $slack;

    protected $cache;

    protected $token;
    protected $channelName;
    protected $channelId;
    protected $username;
    protected $userId;
    protected $command;
    protected $action;
    protected $text;
    protected $splitText;

    protected $baseHelp = ['help', 'Shows this list of commands!'];


    public function callHelp()
    {
        $helpDetails = $this->getHelp();
        $helpDetails[] = $this->baseHelp;

        $returnDetails = [];
        foreach ($helpDetails as $detail) {
            $returnDetails[] = '`/' . $this->command . ' ' . $detail[0] . '` : ' . $detail[1];
        }

        return implode("\n", $returnDetails);
    }

    protected function getHelp()
    {
        return [];
    }

    public function callDebug()
    {
        return implode(', ', [
            $this->token,
            $this->channelName,
            $this->channelId,
            $this->username,
            $this->userId,
            $this->command,
            $this->action,
            $this->text,
        ]);
    }

    public static function __callStatic($name, $args)
    {
        $name = "call" . ucwords(strtolower($name));
        $thisClass = new static($args[0]);

        if (!method_exists($thisClass, $name)) {
            return $thisClass->callHelp();
        }

        return $thisClass->$name();
    }

    public function __construct(array $settings)
    {
        $this->token = $settings['token'];
        $this->channelName = $settings['channelName'];
        $this->channelId = $settings['channelId'];
        $this->username = $settings['username'];
        $this->userId = $settings['userId'];
        $this->command = $settings['command'];
        $this->action = $settings['action'];
        $this->text = $settings['text'];

        $this->splitText = explode(' ', $settings['text']);
    }

}
