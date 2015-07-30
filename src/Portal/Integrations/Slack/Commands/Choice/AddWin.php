<?php namespace Portal\Integrations\Slack\Commands\Choice;

use Carbon\Carbon;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Queue\SerializesModels;
use Portal\Foundation\Commands\Command;
use Portal\Integrations\Slack\Classes\SlackNotification;
use Portal\Integrations\Slack\Models\Choice\DailyResults;

class AddWin extends Command implements SelfHandling, ShouldBeQueued
{

    use SerializesModels;

    protected $todayInformation;
    protected $slack;
    protected $value;
    protected $slackUser;
    protected $winPercentage;
    protected $feeDue;

    protected $roomPosts = ['choice-claims', 'directors-chat'];

    public function handle()
    {
        $this->slack            = SlackNotification::create();
        $this->todayInformation->wins       = $this->todayInformation->wins + 1;
        $this->todayInformation->win_value  = $this->todayInformation->win_value + $this->value;
        $this->todayInformation->fees_due   = $this->todayInformation->fees_due + $this->feeDue;
        $this->todayInformation->save();

        $formattedValue      = number_format($this->value, 2);
        $formattedFeeValue   = number_format($this->feeDue, 2);
        $formattedTotalValue = number_format($this->todayInformation->win_value, 2);
        $formattedTotalFees  = number_format($this->todayInformation->fees_due, 2);

        $this->postMessage(
            ":tada: :confetti_ball: :balloon: <@{$this->slackUser}> just registered a win of £{$formattedValue} and fees due of £{$formattedFeeValue}! :balloon: :confetti_ball: :tada:",
            "Total wins today: *{$this->todayInformation->wins}* with a value of *£{$formattedTotalValue}* and fees due of *£{$formattedTotalFees}*!"
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

    public function __construct(Carbon $date, $value, $user, $winPercentage = 30)
    {
        $this->todayInformation = DailyResults::firstOrCreate(['today' => Carbon::now()->format('Y-m-d')]);
        $this->value            = $value;
        $this->winPercentage    = $winPercentage;
        $this->slackUser        = $user;
        $this->feeDue           = ($value * ($winPercentage / 100)) * 1.2;

    }

}
