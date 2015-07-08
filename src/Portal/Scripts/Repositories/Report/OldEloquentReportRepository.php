<?php namespace Portal\Scripts\Repositories\Report;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use MySecurePortal\OldPortal\Models\Scripts\Log;
use Portal\Scripts\Contracts\ReportRepository;
use Portal\Scripts\Models\OldSurveyAnswerLog;

class OldEloquentReportRepository implements ReportRepository {

    public function countCompletedScripts($scriptId = null, Carbon $dateFrom = null, Carbon $dateTo = null, $status = 'COMPLETE')
    {

        $query = Log::select(DB::raw("count(DISTINCT lead_id) as total"))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('company_id', $scriptId)
            ->where('status', $status)
            ->first();

        return (int)$query->total;
    }

    public function countCompletedQuestions($questionId = null, Carbon $dateFrom = null, Carbon $dateTo = null)
    {
        // TODO: Implement countCompletedQuestions() method.
    }

    public function getAllScriptResults($scriptId = null, Carbon $dateFrom = null, Carbon $dateTo = null, $status = 'COMPLETE')
    {
        // Select all results from the answer log ready for transforming
        $query = OldSurveyAnswerLog::with('client', 'client.contactdetails', 'question', 'company')->select("*")
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            //->where('script_id', $scriptId)
            ->get();

        // Return all of the results as an Eloquent Collection
        return $query;
    }

    public function getByQuestionResults(array $questionAndAnswers = null, Carbon $dateFrom = null, Carbon $dateTo = null)
    {
        // TODO: Implement getByQuestionResults() method.
    }

    /**
     * @param integer        $agentId
     * @param \Carbon\Carbon $dateFrom
     * @param \Carbon\Carbon $dateTo
     *
     * @return mixed
     */
    public function getSurveyCountByAgentId($agentId = null, Carbon $dateFrom = null, Carbon $dateTo = null)
    {
        $query = Log::with('agent')->select('status', 'agent_id');

        if (!is_null($agentId))
        {
            $query = $query->where('agent_id', $agentId);
        }

        $query = $query->whereBetween('completed_at', [$dateFrom, $dateTo])
            ->get();

        return $query->toArray();
    }

    public function getSurveyDailyReport(Carbon $startDate, Carbon $endDate)
    {

        $todayQuery = Log::with('agent', 'company')
            ->select('status', 'agent_id', 'company_id')
            ->whereBetween('completed_at', [
                $startDate,
                $endDate,
            ])->get();

        $weekQuery = Log::with('agent', 'company')
            ->select('status', 'agent_id', 'company_id')
            ->whereBetween('completed_at', [
                (new Carbon('this week'))->hour(0)->minute(0)->second(0),
                $endDate,
            ])->get();

        $monthQuery = Log::with('agent', 'company')
            ->select('status', 'agent_id', 'company_id')
            ->whereBetween('completed_at', [
                (new Carbon())->day(1)->hour(0)->minute(0)->second(0),
                $endDate,
            ])->get();

        return [
            'Today' => $todayQuery,
            'This Week' => $weekQuery,
            'This Month' => $monthQuery,
        ];
    }

    /**
     * @param null           $scriptId
     * @param \Carbon\Carbon $dateFrom
     * @param \Carbon\Carbon $dateTo
     *
     * @return mixed
     */
    public function countCompletedScriptsByDate($scriptId = null, Carbon $dateFrom = null, Carbon $dateTo = null, $status = 'COMPLETE')
    {
        // Select all the distinct Lead IDs from the answer log
        $query = Log::select(DB::raw("DATE(created_at) as date, count(DISTINCT lead_id) as total"))
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('company_id', $scriptId)
            ->where('status', $status)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get();

        // Return the count as an integer
        return $query;
    }
}
