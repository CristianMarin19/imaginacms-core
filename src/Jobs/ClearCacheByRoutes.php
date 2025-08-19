<?php

namespace Imagina\Icore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Iwebhooks\Entities\Log;

class ClearCacheByRoutes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $urls;
    public $entity;

    /**
     * Create a new job instance.
     */
    public function __construct($entity = null, $urls = [])
    {
        $this->entity = $entity;

        if (isset($this->entity->id))
            $this->urls = $this->initCacheClearableData('urls');

        !is_array($urls) ? $urls = [$urls] : false;
        $this->urls = array_merge($this->urls ?? [], $urls);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $client = new \GuzzleHttp\Client();
        if (!empty($this->urls)) {
            foreach ($this->urls as $url) {
                $promise = $client->get($url, ['headers' => ['icache-bypass' => 1]]);
                \Log::info('Route Update Cache: ' . $url);
            }
        }
    }

    /**
     * Return the needed data by cache provider from model
     *
     * @param $type
     * @return mixed|null
     */
    public function initCacheClearableData($type)
    {
        $response = null;
        if (method_exists($this->entity, 'getCacheClearableData')) {
            $cacheClearableData = $this->entity->getCacheClearableData();
            $response = $cacheClearableData[$type] ?? null;
        }
        return $response;
    }
}
