<?php
namespace Portal\Integrations\Slack\SlackCommands;

use Illuminate\Support\Facades\Queue;
use Portal\Integrations\Slack\Classes\SlackNotification;
use Portal\Integrations\Slack\Contracts\SlackCommand;

class SlackQc extends SlackCommand
{

    protected function getHelp()
    {
        return [
            ['survey [message]', 'Sends an anonymous message to the survey campaign.'],
            ['managers [message]', 'Sends an anonymous message to the managers campaign.'],
        ];
    }

    public function callSurvey()
    {
        if (strlen($this->text) < 1)
            return "You need to enter a message to be sent. Example: `/qc survey Hello from the QC team`";

        $this->sendMessage('survey', $this->text);

        return "Message will be sent!";
    }

    public function callManagers()
    {
        if (strlen($this->text) < 1)
            return "You need to enter a message to be sent. Example: `/qc managers Hello to the managers`";

        $this->sendMessage('call-centre-managers', $this->text);

        return "Message will be sent!";
    }

    protected function sendMessage($to, $message)
    {
        Queue::push(function($job) use($to, $message) {
            $slack = SlackNotification::create();
            $slack
                ->to($to)
                ->from('Quality Control')
                ->withIcon('http://a5.mzstatic.com/us/r30/Purple5/v4/77/7d/87/777d8753-c206-a335-7f33-04435931634f/icon175x175.jpeg')
                ->send(
                    preg_replace("/(?<=^|(?<=[^a-zA-Z0-9-_\.]))(@\w+)/", "<$1>", $message)
                );

            $job->delete();
        });
    }


}
