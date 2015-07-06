<?php namespace Portal\Integrations\Slack\Commands\Choice;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Queue\SerializesModels;
use Portal\Foundation\Commands\Command;
use Portal\Integrations\Slack\Classes\SlackNotification;
use Portal\Integrations\Slack\Models\Choice\DailyResults;

class AddPackout extends Command implements SelfHandling, ShouldBeQueued
{
    use SerializesModels;

    protected $todayInformation;
    protected $slack;
    protected $agent;
    protected $slackUser;

    protected $roomPosts = ['directors-chat','choice-claims','pba'];

    public function handle()
    {
        $this->slack            = SlackNotification::create();
        $this->todayInformation->pack_outs       = $this->todayInformation->pack_outs + 1;
        $this->todayInformation->save();

        $this->postMessage(
            ":tada: :confetti_ball: :balloon: <@{$this->slackUser}> just packed out a client hotkeyed over by <@{$this->agent}> :balloon: :confetti_ball: :tada:",
            "There have been *{$this->todayInformation->pack_outs}* clients packed out so far today!"
        );
    }

    protected function postMessage($message, $totalMessage)
    {
        array_walk(
            $this->roomPosts,
            function ($r) use ($message, $totalMessage) {
                $this->slack->to($r)
                            ->from('Choice Claims')
                            ->withIcon('https://s3-eu-west-1.amazonaws.com/choice-claims/images/choice_icon.png')
                            ->setAttachments(
                                [
                                    SlackNotification::attachment(
                                        [
                                            'title'     => $message,
                                            'text'      => $totalMessage,
                                            'color'     => 'good',
                                            'mrkdwn_in' => ['title', 'text'],
                                        ]
                                    )
                                ]
                            )
                            ->send();
            }
        );
    }

    public function __construct(Carbon $date, $agent, $user)
    {
        $this->todayInformation = DailyResults::firstOrCreate(['today' => Carbon::now()->format('Y-m-d')]);
        $this->agent            = $agent;
        $this->slackUser        = $user;
    }
}