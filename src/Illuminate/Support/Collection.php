<?php namespace IlluminateExtensions\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Maatwebsite\Excel\Facades\Excel;

class Collection extends BaseCollection
{

    protected $excelStoragePath = 'emails/foundation/collection/toemail';


    protected $errorLeads = null;

    public function toIntegration($integration)
    {
        $integrationClass = "\\MySecurePortal\\Classes\\Leads\\Integrations\\" . $integration['class'];
        $postedResults = new Collection();
        $this->errorLeads = new Collection();
        $this->each(function ($item) use ($integration, $integrationClass, &$postedResults) {

            $int = $integrationClass::withOptionsAndLead($integration, $item);
            $int->send();

            if ($int->isValid)
            {
                $item['result'] = $int->getResult();
                $postedResults->push($item);
            } else {
                $item['result'] = $int->getError();
                $this->errorLeads->push($item);
            }

        });

        return $postedResults;
    }

    public function getIntegrationErrors()
    {
        return $this->errorLeads;
    }
    

    public function mergeAndKeep($newData)
    {

        foreach (array_keys($newData->toArray()) as $key)
        {
            if (isset($this[$key]))
            {
                $theseDetails = $newData[$key];
                $thoseDetails = $this[$key];

                $finalMerge = array_merge($theseDetails, $thoseDetails);

                $this[$key] = $finalMerge;
            } else {
                $this->put($key, $newData[$key]);
            }

        }

        return $this;
    }

    public function limit($top, $start = null)
    {
        if ($top == 0 && is_null($start)) {
            return $this;
        }

        $newCollection = new Collection();

        $i = 1;
        foreach ($this as $single) {
            if (is_null($start) || $i >= $start) {
                $newCollection->push($single);
            }

            if ($i < $top || $top == 0) {
                $i++;
            } else {
                break;
            }

        }

        return $newCollection;
    }

    public function toUrl(array $settings)
    {

        $postedResults = new Collection();
        $this->each(function ($item) use ($settings, &$postedResults) {

            $data = http_build_query(array_merge($settings['defaults'], $item));

            if ($settings['method'] == 'POST') {
                $result = file_get_contents($settings['url'], false, stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => $data,
                    ]
                ]));
            } else {
                $result = file_get_contents($settings['url'] . '?' . $data);
            }

            $item[strtolower($settings['method']) . '_result'] = $result;

            $postedResults->push($item);
        });

        // If email settings are available pass on the completed data set
        if (isset($settings['to']) && isset($settings['filename']) && isset($settings['subject'])) {
            $postedResults->toEmail([
                'to' => $settings['to'],
                'subject' => $settings['subject'],
                'filename' => $settings['filename'],
            ]);
        }

        return $postedResults;
    }

    public function toEmail(array $settings, $xls = true)
    {
        \Mail::send('portal::emails.foundation.collection.toemail', [
            'title' => $settings['subject'],
            'total' => $this->count(),
        ], function ($message) use ($settings, $xls) {
            $filename = $settings['filename'] . '_' . Carbon::now()->format('YmdHis');

            if ($xls)
            {
                $extension = '.xls';
                $xl = $this->toXls($filename, false);
            } else {
                $extension = '.csv';
                $xl = $this->toCsv($filename, false);
            }

            $message->to($settings['to']);
            $message->subject($settings['subject'] . ' - ' . Carbon::now()->format('d/m/Y'));
            $message->from('noreply@mysecureportal.net', 'My Secure Portal');
            $message->attach(storage_path($this->excelStoragePath) . '/' . $filename . $extension);
        });

        return $this;
    }

    public function toXls($filename, $export = true, $password = null)
    {
        $excel = Excel::create($filename, function ($excel) use ($password) {
            $excel->sheet('Results', function ($sheet) use ($password) {
                $sheet->freezeFirstRow();
                $sheet->setAutoFilter();


                $sheet->fromArray($this->zeroMissingHeaders()->toArray());
                if (!is_null($password)) {
                    $sheet->protect($password);
                }
            });
        });

        return $export ? $excel->export('xls') : $excel->store('xls', storage_path($this->excelStoragePath));
    }

    public function zeroMissingHeaders()
    {
        $finishedArray = new Collection();

        $allHeaders = [];

        foreach ($this as $key => $values)
        {
            foreach (array_keys($values) as $saveKey)
            {
                $allHeaders[] = $saveKey;
            }
        }

        $allHeaders = array_unique($allHeaders);

        foreach ($this as $key => $item)
        {
            $returnItem = [];
            foreach ($allHeaders as $header)
            {
                if (!isset($item[$header]))
                {
                    $item[$header] = 0;
                }

                $returnItem[$header] = $item[$header];
            }

            $finishedArray->push($returnItem);
        }


        return $finishedArray;
    }

    public function transformWithHeadings($headings)
    {
        $newCollection = new Collection();

        foreach ($this as $item) {
            $returnArray = [];

            foreach ($headings as $key => $value) {
                $returnArray[$value] = isset($item[$key]) ? $item[$key] : false;
            }

            $newCollection->push($returnArray);
        }

        return $newCollection;
    }

    /**
     * Generates csv file from collection returns contents string or save file to disk
     *
     * @param $filename
     * @param bool $export
     * @return mixed
     */
    public function toCsv($filename, $export = true)
    {
        $csv = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertAll($this->toArray());

        return $export ? $csv->__toString() : file_put_contents(storage_path($this->excelStoragePath) .'/'. $filename . '.csv', $csv->__toString());


    }


}
