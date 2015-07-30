<?php namespace Portal\Integrations\Slack\Commands\Choice;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Queue\SerializesModels;
use Portal\Foundation\Commands\Command;
use Portal\Integrations\Slack\Classes\SlackNotification;
use Portal\Integrations\Slack\Models\Choice\DailyResults;

class AddCollection extends Command implements SelfHandling, ShouldBeQueued
{

    use SerializesModels;

    protected $todayInformation;
    protected $slack;
    protected $value;
    protected $slackUser;

    protected $roomPosts = ['choice-claims', 'directors-chat'];

    public function handle()
    {
        $this->slack            = SlackNotification::create();
        $this->todayInformation->collected       = $this->todayInformation->collected + 1;
        $this->todayInformation->collected_value = $this->todayInformation->collected_value + $this->value;
        $this->todayInformation->save();

        $formattedValue      = number_format($this->value, 2);
        $formattedTotalValue = number_format($this->todayInformation->collected_value, 2);

        $this->postMessage(
            ":tada: :confetti_ball: :balloon: <@{$this->slackUser}> just collected a payment of £{$formattedValue}! :balloon: :confetti_ball: :tada:",
            "A total of *£{$formattedTotalValue}* has been collected so far today!"
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

    public function __construct(Carbon $date, $value, $user)
    {
        $this->todayInformation = DailyResults::firstOrCreate(['today' => Carbon::now()->format('Y-m-d')]);
        $this->value            = $value;
        $this->slackUser        = $user;
    }

}
