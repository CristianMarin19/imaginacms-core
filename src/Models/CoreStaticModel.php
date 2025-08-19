<?php

namespace Imagina\Icore\Models;

class CoreStaticModel
{

  protected $records = [];

  public function lists()
  {
    return $this->records;
  }

  public function index()
  {
    //Instance response
    $response = [];
    //AMp status
    foreach ($this->records as $key => $item) {
      $itemData = ['id' => $key, 'title' => $item];
      if (is_array($item)) $itemData = array_merge($itemData, $item);
      array_push($response, $itemData);
    }
    //turns as collection
    $response = collect($response);
    //Apply filters
    $filters = json_decode(request()->input('filter') ?? '{}');
    $modelAttributes = array_keys($response->first());
    foreach ($filters as $name => $value) {
      if (in_array($name, $modelAttributes)) {
        $value = (array)$value;
        $response = $response->whereIn($name, $value);
      }
    }

    //Repsonse
    return $response->values();
  }

  public function show($statusId)
  {
    $response = null;
    //Define the response
    if (isset($this->records[$statusId])) {
      $value = $this->records[$statusId];
      $response = ['id' => $statusId, 'title' => $value];
      if (is_array($value)) $response = array_merge($response, $value);
    }
    //Response
    return $response;
  }
}
