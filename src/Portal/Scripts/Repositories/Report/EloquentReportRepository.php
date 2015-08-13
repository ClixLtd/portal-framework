<?php namespace Portal\Scripts\Repositories\Report;

use Carbon\Carbon;
use Portal\Scripts\Contracts\ReportRepository;

use \MySecurePortal\OldPortal\Models\Scripts\Log as ScriptLog;

class EloquentReportRepository implements ReportRepository {

    /**
     * @param null           $scriptId
     * @param \Carbon\Carbon $dateFrom
     * @param \Carbon\Carbon $dateTo
     *
     * @return mixed
     */
    public function countCompletedScripts(
        $scriptId = null,
        Carbon $dateFrom = null,
        Carbon $dateTo = null,
        $status = 'COMPLETE'
    ) {

        $query = ScriptLog::select(DB::raw("count(DISTINCT lead_id) as total"))
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('company_id', $scriptId)
                    ->where('status', $status)
                    ->first();

        return (int)$query->total;
    }

    /**
     * @param null           $scriptId
     * @param \Carbon\Carbon $dateFrom
     * @param \Carbon\Carbon $dateTo
     *
     * @return mixed
     */
    public function countCompletedScriptsByDate(
        $scriptId = null,
        Carbon $dateFrom = null,
        Carbon $dateTo = null,
        $status = 'COMPLETE'
    ) {

        dd($scriptId);

        // Select all the distinct Lead IDs from the answer log
        $query = ScriptLog::select(DB::raw("DATE(created_at) as date, count(DISTINCT lead_id) as total"))
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->where('company_id', $scriptId)
                    ->where('status', $status)
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->get();

        // Return the count as an integer
        return $query;
    }

    /**
     * @param null           $questionId
     * @param \Carbon\Carbon $dateFrom
     * @param \Carbon\Carbon $dateTo
     *
     * @return mixed
     */
    public function countCompletedQuestions($questionId = null, Carbon $dateFrom = null, Carbon $dateTo = null)
    {
        // TODO: Implement countCompletedQuestions() method.
    }

    /**
     * @param null           $scriptId
     * @param \Carbon\Carbon $dateFrom
     * @param \Carbon\Carbon $dateTo
     *
     * @return mixed
     */
    public function getAllScriptResults(
        $scriptId = null,
        Carbon $dateFrom = null,
        Carbon $dateTo = null,
        $status = 'COMPLETE'
    ) {
        // TODO: Implement getAllScriptResults() method.
    }

    /**
     * @param array          $questionAndAnswers
     * @param \Carbon\Carbon $dateFrom
     * @param \Carbon\Carbon $dateTo
     *
     * @return mixed
     */
    public function getByQuestionResults(
        array $questionAndAnswers = null,
        Carbon $dateFrom = null,
        Carbon $dateTo = null
    ) {
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
        // TODO: Implement getSurveyCountByAgentId() method.
    }

    public function getSurveyDailyReport(Carbon $startDate, Carbon $endDate)
    {
        // TODO: Implement getSurveyDailyReport() method.
    }
}