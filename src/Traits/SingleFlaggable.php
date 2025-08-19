<?php

namespace Imagina\Icore\Traits;


use http\Params;

trait SingleFlaggable
{
  private $modelData;
  private $isTheSingleFlag;
  private $params;

  public static function bootSingleFlaggable()
  {
    //Listen event after create model
    static::createdWithBindings(function ($model) {
      $model->handleSingleFlag('created');
    });
    //Listen event after update model
    static::updatedWithBindings(function ($model) {
      $model->handleSingleFlag('updated');
    });

    static::deleted(function ($model) {
      $model->handleSingleFlag('deleted');
    });
  }

  /**
   * Instance the params
   *
   * @return void
   */
  private function handleSingleFlag($eventName)
  {
    $this->params = (object)[
      'singleFlagName' => $this->singleFlagName ?? 'default',
      'singleFlaggableCombination' => $this->singleFlaggableCombination ?? [],
      'singleFlagTrueValue' => $this->singleFlagTrueValue ?? 1,
      'singleFlagFalseValue' => $this->singleFlagFalseValue ?? 0,
      'isEnableSingleFlaggable' => $this->isEnableSingleFlaggable ?? true
    ];
    $this->modelData = $this->toArray();

    //Handle the flag
    if ($this->canManageFlag()) {
      $this->isTheSingleFlag = ($this->modelData[$this->params->singleFlagName] == $this->params->singleFlagTrueValue);
      if ($eventName == 'deleted') {
        if ($this->isTheSingleFlag) $this->chooseDynamicFlag();
      } else $this->setSingleFlag();
    }
  }


  /**
   * Validates if the flag can be managed
   *
   * @param $data
   * @return bool
   * @throws \Exception
   */
  public function canManageFlag()
  {
    //Check if flag name exists in data
    if (!isset($this->modelData[$this->params->singleFlagName])) return false;
    //Check isEnableSingleFlaggable
    if (!$this->params->isEnableSingleFlaggable) return false;
    //Validate that all singleFlaggableCombination are in data
    $missingColumns = array_diff($this->params->singleFlaggableCombination, array_keys($this->modelData));
    if (!empty($missingColumns)) {
      $errorMsg = trans("core::common.columnsNotFound", ['columns' => implode(', ', $missingColumns)]);
      throw new \Exception($errorMsg, 500);
    }

    //Default response
    return true;
  }

  /**
   * Turn to false value all records less the current
   *
   * @param $model
   * @return void
   */
  public function setSingleFlag()
  {
    $repository = app($this->repository);
    //If it is the new flag remove others flags
    if ($this->isTheSingleFlag) {
      $query = static::where('id', '!=', $this->modelData["id"]);

      // Including contitions for each combination
      foreach ($this->params->singleFlaggableCombination as $columnName) {
        $query->where($columnName, $this->modelData[$columnName]);
      }

      //Set false value
      $query->update([$this->params->singleFlagName => $this->params->singleFlagFalseValue]);
    } else if ($this->repository) { //Check if there a single flag else choose one
      //Init params
      $params = ['take' => 1, 'filter' => [
        'id' => ['operator' => '!=', 'value' => $this->modelData['id']],
        $this->params->singleFlagName => $this->params->singleFlagTrueValue
      ]];
      // Including contitions for each combination
      foreach ($this->params->singleFlaggableCombination as $columnName) {
        $params['filter'][$columnName] = $this->modelData[$columnName];
      }
      //if no exista  single flag,choose one
      $existSingleFlag = $repository->getItemsBy(json_decode(json_encode($params)));
      if (!$existSingleFlag->first()) $this->chooseDynamicFlag();
    }
  }

  /**
   * Turn to true value first records less the current
   *
   * @return void
   * @throws \Exception
   */
  private function chooseDynamicFlag()
  {
    $query = static::where('id', '!=', $this->modelData['id']);

    // Including contitions for each combination
    foreach ($this->params->singleFlaggableCombination as $columnName) {
      $query->where($columnName, $this->modelData[$columnName]);
    }

    //Update the first record it finds
    $query->take(1)->update([$this->params->singleFlagName => $this->params->singleFlagTrueValue]);
    //remove default value to deleted record
    $this->update([$this->params->singleFlagName => $this->params->singleFlagFalseValue]);
  }
}
