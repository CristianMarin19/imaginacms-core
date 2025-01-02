<?php

namespace Modules\Core\Services;

use Modules\Core\Jobs\ProcessBulkItems;

class BulkService
{

    protected $controller;
    protected $items;

    private $log = "Core::Services||BulkService";

    /**
     * Contruct Basic Params
     */
    public function __construct($params)
    {
        $this->controller = $params['controller'];
        $this->items = $params['items'];
    }

    /**
     * Main Function
     */
    public function execute()
    {

        \Log::info($this->log."|Execute");

        //Validation General
        $this->validationGeneral();

        $msjs = [];  //Final Msjs to Errors
        $itemsFinal = []; //Items to send job
        //Check all Items
        $this->validateEachItem($msjs, $itemsFinal);

        $bulkId = "";
        $totalChunks = 0;
        //Create jobs | chunk process
        $this->createJobs($itemsFinal,$bulkId,$totalChunks);

        // Response
        return ["data" => ["bulkId" => $bulkId, "chunks" => round($totalChunks), "errors"=> count($msjs),"msjs" => $msjs]];

    }

    /**
     * Validation General to array "Items"
     */
    private function validationGeneral()
    {
        if (is_null($this->items)) throw new \Exception(trans("core::common.processBulk.It must be grouped"), 500);
        if (!is_array($this->items)) throw new \Exception(trans("core::common.processBulk.Must be an array"), 500);
        
        //if (count($this->items)>1000) throw new \Exception(trans("core::common.processBulk.limit is 1000 items for request"), 500);
    }

    /**
     * Check each item with validations
     */
    private function validateEachItem(&$msjs, &$itemsFinal)
    {

        //Get relations from model
        $relations = $this->controller->model->modelRelations;

        foreach ($this->items as $key => $item) 
        {
            $operation = isset($item['id']) ? 'update' : 'create';

            try {

                //Validation to Item
                $this->controller->validateRequestApi(new $this->controller->model->requestValidation[$operation]($item));

                //Check relations from model
                foreach ($relations as $relationName => $relation) {

                    //check if relation exist in the item
                    if (array_key_exists($relationName, $item)) {
                        //Get model for this relation
                        $model = new $relation['model'];

                        //Is multiple data
                        if (is_array($item[$relationName])) {
                            //Check each related item
                            foreach ($item[$relationName] as $data) {
                                $this->controller->validateRequestApi(new $model->requestValidation[$operation]($data));
                            }
                        } else {
                            //Is one
                            $this->controller->validateRequestApi(new $model->requestValidation[$operation]($item[$relationName]));
                        }
                    }
                }

                //items Pass validations
                $itemsFinal[] = $item;
            } catch (\Exception $e) {
                $msjs[] = ['type' => "error", 'operation' => $operation, 'msjs' =>  $e->getMessage(), 'item' => $item];
            }
        }

    }

    /**
     * Create JOBS
     */
    private function createJobs($itemsFinal,&$bulkId,&$totalChunks)
    {
        //Validation if all it's ok to create the JOBs
        if (count($itemsFinal) > 0) {
            $chunkSize =  setting('core::chunkSizeToBulkProcess', null, 100);
            $bulkId = "bulk_" . uniqid();
            $totalChunks = ceil((count($itemsFinal)) / $chunkSize);
            $itemsChunked = collect($itemsFinal)->chunk($chunkSize);

            //Create JOBS
            foreach ($itemsChunked as $key => $chunk) {
                $chunkId = $bulkId . "_" . $key;
                ProcessBulkItems::dispatch(get_class($this->controller->model), $chunkId, $key, $chunk->toArray())
                ->onQueue('bulk');
            }
        }
    }


}
