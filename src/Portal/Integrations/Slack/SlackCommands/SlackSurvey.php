<?php namespace Portal\Integrations\Slack\SlackCommands;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use IlluminateExtensions\Support\Collection;
use MySecurePortal\OldPortal\Classes\Dashboard\Reports\Surveys;
use MySecurePortal\OldPortal\Models\Vicidial\AgentGroups;
use Portal\Integrations\Slack\Classes\SlackNotification;
use Portal\Integrations\Slack\Contracts\SlackCommand;

class SlackSurvey extends SlackCommand
{

    protected function getHelp()
    {
        return [
            ['display', 'Gets @slackbot to show you the current survey count.'],
            ['announce', 'Announces the current survey count in the current chat window.'],
            ['agents [limit] [user-group]', 'Shows the top _[limit]_ agents from the dialler _[user-group]_.'],
        ];
    }


    public function callAgents()
    {
        $details = explode(' ', $this->text);

        $count = isset($details[0]) ? $details[0] : 5;
        $group = isset($details[1]) ? $details[1] : 'LEADGEN';

        $name = ucwords(strtolower($group));

        Queue::push(function($job) use($group, $count, $name) {
            $this->sendSingleCampaign($this->agentList($group, $count), $name);

            $job->delete();
        });

        return "Top {$count} {$name} agents coming up!";
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




    // *****************************************************************
    // Helper Methods
    public function sendSingleCampaign($results, $usergroup, $room = null)
    {
        if (count($results) > 0)
        {
            $fieldHolder = [
                SlackNotification::attachmentField([
                    'title' => 'Agent Name',
                    'short' => true,
                ]),
                SlackNotification::attachmentField([
                    'title' => 'Results',
                    'short' => true,
                ]),
            ];

            foreach ($results as $single)
            {
                $fieldHolder[] = SlackNotification::attachmentField([
                    'value' => $single['name'],
                    'short' => true,
                ]);

                $fieldHolder[] = SlackNotification::attachmentField([
                    'value' => $single['full'] . ' full, ' . $single['part'] . ' partials.',
                    'short' => true,
                ]);
            }

            $sendResults = SlackNotification::attachment([
                'fields' => $fieldHolder,
                'color' => 'good',
                'mrkdwn_in' => ['text'],
            ]);

            Queue::push(function($job) use($sendResults, $results, $usergroup, $room) {
                $slack = SlackNotification::create();
                $slack
                    ->to(!is_null($room) ? $room : $this->channelId)
                    ->from('Survey Team')
                    ->withIcon('http://www.yarramsc.vic.edu.au/wp-content/uploads/2012/07/Survey-Icon.png')
                    ->setAttachments([$sendResults])
                    ->send("*Top " . count($results) ." {$usergroup} agents!*");

                $job->delete();
            });
        }
    }

    public function agentList($userGroup, $limit = null, Carbon $startDate = null, Carbon $endDate = null)
    {
        $startDate = is_null($startDate) ? Carbon::now()->hour(0)->minute(0)->second(0) : $startDate;
        $endDate = is_null($endDate) ? Carbon::now()->hour(23)->minute(59)->second(59) : $endDate;

        $cache = App::make('cache.store');

        return $cache->remember(
            "agentLeaderboard-{$userGroup}-{$limit}-{$startDate}-{$endDate}",
            5,
            function() use($userGroup, $startDate, $endDate, $limit) {
                $group = AgentGroups::with('agents', 'agents.scriptlog')->find($userGroup);

                if (!isset($group->agents))
                    return [];

                $results = [];
                foreach ($group->agents as $agent)
                {
                    $fullSurveyCount = $agent->scriptlog()->whereBetween('completed_at', [$startDate, $endDate])->where('status', 'COMPLETE')->count();
                    $partialSurveyCount = $agent->scriptlog()->whereBetween('completed_at', [$startDate, $endDate])->where('status', 'PARTIAL')->count();

                    $results[] = [
                        'name' => $agent->full_name,
                        'full' => $fullSurveyCount,
                        'part' => $partialSurveyCount,
                    ];
                }

                return (new Collection($results))->sortByDesc(function($r) {
                    return $r['full'];
                })->limit($limit)->toArray();
            }
        );
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
            $slack = SlackNotification::create();
            $slack
                ->to($to)
                ->from('Survey Team')
                ->withIcon('http://www.yarramsc.vic.edu.au/wp-content/uploads/2012/07/Survey-Icon.png')
                ->setAttachments([$fullResults, $partialResults])
                ->send('');

            $job->delete();
        });



    }

}
