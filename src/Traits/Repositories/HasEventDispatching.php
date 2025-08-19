<?php

namespace Imagina\Icore\Traits\Repositories;

use Nwidart\Modules\Facades\Module;

trait HasEventDispatching
{
    public function dispatchesEvents(array $params): void
    {
        $eventName = $params['eventName'];
        $data = $params['data'] ?? [];
        $criteria = $params['criteria'] ?? null;
        $model = $params['model'] ?? null;

        // Define model method callbacks for specific events
        $modelEventCallbacks = [
            'retrievedIndex' => ['object' => $this->model, 'method' => 'retrievedIndexCrudModel', 'args' => ['data' => $data]],
            'retrievedShow' => ['object' => $this->model, 'method' => 'retrievedShowCrudModel', 'args' => ['data' => $data]],
            'creating' => ['object' => $this->model, 'method' => 'creatingCrudModel', 'args' => ['data' => $data]],
            'created' => ['object' => $model, 'method' => 'createdCrudModel', 'args' => ['data' => $data]],
            'updating' => ['object' => $this->model, 'method' => 'updatingCrudModel', 'args' => ['data' => $data, 'params' => $params, 'criteria' => $criteria]],
            'updated' => ['object' => $model, 'method' => 'updatedCrudModel', 'args' => ['data' => $data, 'params' => $params, 'criteria' => $criteria]],
            'deleting' => ['object' => $model, 'method' => 'deletingCrudModel', 'args' => ['params' => $params, 'criteria' => $criteria]],
        ];

        // Execute the matching model method if it exists
        if (isset($modelEventCallbacks[$eventName])) {
            $callback = $modelEventCallbacks[$eventName];
            if (method_exists($callback['object'], $callback['method'])) {
                $callback['object']->{$callback['method']}($callback['args']);
            }
        }

        // Dispatch custom model-defined events (e.g., from config)
        $dispatchesEvents = $this->model->dispatchesEventsWithBindings ?? [];
        if (!empty($dispatchesEvents[$eventName])) {
            foreach ($dispatchesEvents[$eventName] as $event) {
                $moduleName = explode("\\", $event['path'])[1] ?? null;
                if ($moduleName && Module::isEnabled($moduleName)) {
                    event(new $event['path']([
                        'data' => $data,
                        'extraData' => $event['extraData'] ?? [],
                        'criteria' => $criteria,
                        'model' => $model
                    ]));
                }
            }
        }
    }
}
