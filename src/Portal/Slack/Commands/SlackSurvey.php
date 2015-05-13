<?php namespace Portal\Slack\Commands;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use MySecurePortal\OldPortal\Classes\Dashboard\Reports\Surveys;
use Portal\Foundation\Commands\Command;
use Portal\Slack\Classes\SlackNotification;

class SlackSurvey extends Command implements SelfHandling, ShouldBeQueued
{

    use SerializesModels, DispatchesCommands;

    protected $slack;

    protected $token;
    protected $channelName;
    protected $channelId;
    protected $username;
    protected $userId;
    protected $command;
    protected $action;
    protected $text;


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


    public function callDisplay()
    {
        $this->sendSurveyResults($this->userId);
        return 'Survey results coming up!';
    }

    public function callAnnounce()
    {
        $this->sendSurveyResults($this->channelId);
        return 'Survey results coming up!';
    }


    private function sendSurveyResults($to)
    {
        $fullResults = SlackNotification::attachment([
            'fallback' => str_replace(['<b>', '</b>'], ['', ''], Surveys::surveys(Carbon::today(), Carbon::now())['currentText']),
            'text' => str_replace(['<b>', '</b>'], ['*', '*'], Surveys::surveys(Carbon::today(), Carbon::now())['currentText']),
            'color' => 'good',
            'mrkdwn_in' => ['text'],
        ]);

        $partialResults = SlackNotification::attachment([
            'fallback' => str_replace(['<b>', '</b>'], ['', ''], Surveys::surveysTele(Carbon::today(), Carbon::now())['currentText']),
            'text' => str_replace(['<b>', '</b>'], ['*', '*'], Surveys::surveysTele(Carbon::today(), Carbon::now())['currentText']),
            'color' => 'warning',
            'mrkdwn_in' => ['text'],
        ]);

        Queue::push(function($job) use($to, $fullResults, $partialResults) {
            $this->slack
                ->to($to)
                ->from('Survey Team')
                ->withIcon('http://www.yarramsc.vic.edu.au/wp-content/uploads/2012/07/Survey-Icon.png')
                ->setAttachments([$fullResults, $partialResults])
                ->send('');

            $job->delete();
        });



    }


    public static function __callStatic($name, $args)
    {
        $name = "call".ucwords(strtolower($name));
        $thisClass = new Static($args[0]);

        if (!method_exists($thisClass, $name)) {
            return "Sorry, I can't do that! :( ". $name;
        }

        return $thisClass->$name();
    }

    public function __construct(array $settings)
    {
        $this->slack = SlackNotification::create();

        $this->token       = $settings['token'];
        $this->channelName = $settings['channelName'];
        $this->channelId = $settings['channelId'];
        $this->username    = $settings['username'];
        $this->userId    = $settings['userId'];
        $this->command     = $settings['command'];
        $this->action      = $settings['action'];
        $this->text        = $settings['text'];
    }

}