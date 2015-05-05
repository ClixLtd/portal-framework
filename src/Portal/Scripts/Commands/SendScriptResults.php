<?php namespace Portal\Scripts\Commands;

use Aws\CloudFront\Exception\Exception;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Support\Facades\App;
use IlluminateExtensions\Support\Collection;
use MySecurePortal\OrderScriptResponse;
use Portal\Foundation\Commands\Command;
use Portal\Foundation\DateTime\SetsStartAndEndDate;
use Portal\Scripts\Models\Orders\ScriptResponseOrder;

class SendScriptResults extends Command implements SelfHandling {
    use SetsStartAndEndDate;

    protected $orderScriptResponseId = null;

    protected $scriptResults = [];

    protected $surveyRepository = null;


    protected function sendScriptResults(ScriptResponseOrder $response)
    {
        // Check the remaining leads
        $remainingLeads = $this->checkRemainingSupply($response);

        // Get results for this order ready to process
        $scriptResults = isset($this->scriptResults[$response->script_id])
            ? $this->scriptResults[$response->script_id]
            : $this->generateScriptResultCollection($response->script_id);


        $newResults = $scriptResults->filter(function($r) use($response) {
            $returnResult = false;



        });

        $requestedResults = new Collection();
        foreach ($newResults as $singleResult)
        {
            $singleReturnResult = [];

            foreach (json_decode($response->questions, true) as $question)
            {
                if (isset($singleResult[$question])) {
                    if ($question == 'optin.date') { $singleResult[$question] = $singleResult[$question]->format($response->date_format); }
                    $singleReturnResult[$question] = is_array($singleResult[$question]) ? implode(', ', $singleResult[$question]) : $singleResult[$question];
                }
            }

            $requestedResults->push($singleReturnResult);
        }

        $returnMethod = 'to' . ucfirst(strtolower($response->send_method));

        $requestedResults->$returnMethod(json_decode($response->send_address, true));

        dd(
            $returnMethod
        );

    }

    protected function checkRemainingSupply(ScriptResponseOrder $response)
    {
        // Check to see if the supply should be unlimited (purchased = 0)
        // if not, make sure the supply hasn't reached its limit.
        if ($response->purchased > 0 && $response->supplied >= $response->purchased)
        {
            throw new Exception('No leads left to be sent - Please create a new order');
        }

        // Return the number of leads left to supply.
        return $response->purchased - $response->supplied;
    }

    protected function generateScriptResultCollection($scriptId)
    {
        $scriptResults = $this->surveyRepository->getAllScriptResults($scriptId, $this->getStartDate(), $this->getEndDate());

        // Save the results locally for quick access at a later date
        $this->scriptResults[$scriptId] = $scriptResults;

        return $scriptResults;
    }

    public function handle()
    {
        if (is_null($this->orderScriptResponseId))
        {
            $allScriptOrders = ScriptResponseOrder::with('details')->whereHas('details', function($query) {
                $query->whereNull('completed_at');
            })->get();
            foreach ($allScriptOrders as $order)
            {
                $this->sendScriptResults($order);
            }
        } else {
            $this->sendScriptResults($this->orderScriptResponseId);
        }
    }

    function __construct(Carbon $startDate = null, Carbon $endDate = null, ScriptResponseOrder $response = null)
    {
        $this->orderScriptResponseId = $response;
        $this->setStartDate($startDate)->setEndDate($endDate);
        $this->surveyRepository = App::make('survey');
    }

}
