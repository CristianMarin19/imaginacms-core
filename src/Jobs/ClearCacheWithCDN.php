<?php

namespace Imagina\Icore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ClearCacheWithCDN implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $entity;

    /**
     * Create a new job instance.
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        //Get the keys
        $cdnProvider = env("CDN_PROVIDER", null);
        $cdnUrl = env("CDN_URL", null);
        $cdnAccessKey = env("CDN_ACCESS_KEY", null);

        //Get the model URLs to purge
        $urlsToPurge = $this->initCacheClearableData('urls');

        if ($cdnUrl && $cdnProvider && $cdnAccessKey && is_array($urlsToPurge)) {
            $this->client = new \GuzzleHttp\Client();
            switch ($cdnProvider) {
                case "bunny":

                    foreach ($urlsToPurge as $url) {
                        try {
                            $requestUrl = "$cdnUrl/purge?async=true&url=$url";
                            $response = $this->client->request('GET', $requestUrl, [
                                'headers' => [
                                    'AccessKey' => $cdnAccessKey,
                                    'accept' => 'application/json',
                                ],
                            ]);
                            \Log::info("Trait|HasCacheClearable|CDN:: cache cleared for URL: $requestUrl");
                        } catch (\Exception $e) {
                            \Log::info("Trait|HasCacheClearable|CDN:: error clearing for url: $requestUrl");
                        }
                    }
                    break;
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
