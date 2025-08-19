<?php

namespace Imagina\Icore\Traits;

use Imagina\Icore\Repositories\Eloquent\CustomBuilder;

/**
 * Trait HasOptionalTraits
 *
 * Dynamically adds methods, relations, and event listeners to a model from specified dynamic traits.
 */
trait HasOptionalTraits
{

  /**
   * @var array List of class paths for dynamic traits to be loaded.
   */
  protected static $dynamicTraits = [];
  /**
   * @var array Cached instances of dynamic trait classes to avoid redundant instantiations.
   */
  private static $dynamicClassInstances = [];
  /**
   * @var array List of registered dynamic methods added to the model.
   */
  private static $optionalTraitsMethods = [];
  private static $optionalTraitsRelations = [];
  private static $optionalTraitsAttributes = [];


  /**
   * Boot the HasOptionalTraits trait.
   *
   * Automatically initializes dynamic traits if the `dynamicTraits` property exists.
   */
  public static function bootHasOptionalTraits()
  {
    if (property_exists(static::class, 'dynamicTraits')) {
      static::instanceClassAsTrait();
    }
  }

  /**
   * Instantiates and initializes all dynamic traits.
   *
   * Processes dynamic classes, adding relations, methods, and event listeners to the model.
   */
  protected static function instanceClassAsTrait()
  {
    foreach (static::$dynamicTraits as $classPath) {
      // Skip if the class doesn't exist
      if (!class_exists($classPath)) continue;

      // Cache the dynamic class instance if not already cached
      if (!isset(static::$dynamicClassInstances[$classPath])) {
        static::$dynamicClassInstances[$classPath] = new $classPath();
      }

      // Fetch the dynamic class instance and its definitions
      $dynamicClass = static::$dynamicClassInstances[$classPath];
      $instances = $dynamicClass->getInstances();

      // Initialize relations, methods, and event listeners
      static::instanceRelations($dynamicClass, $instances['relations'] ?? []);
      static::instanceMethods($dynamicClass, $instances['methods'] ?? []);
      static::instanceEventListeners($dynamicClass, $instances['events'] ?? []);
      static::instanceGetAttributes($dynamicClass, $instances['getAttributes'] ?? []);
    }
  }

  /**
   * Adds dynamic relations to the model.
   *
   * @param object $dynamicClass The dynamic class providing the relations.
   * @param array $relations List of relation names to be added to the model.
   */
  protected static function instanceRelations($dynamicClass, $relations)
  {
    foreach ($relations as $relationName) {
      if (!isset(static::$optionalTraitsRelations[$relationName])) {
        static::$optionalTraitsRelations[] = $relationName;
        static::resolveRelationUsing($relationName, function ($model) use ($dynamicClass, $relationName) {
          return $dynamicClass->{$relationName}($model);
        });
      }
    }
  }

  /**
   * Adds dynamic methods to the model.
   *
   * @param object $dynamicClass The dynamic class providing the methods.
   * @param array $methods List of method names to be added to the model.
   */
  protected static function instanceMethods($dynamicClass, $methods)
  {
    foreach ($methods as $methodName) {
      if (!isset(static::$optionalTraitsMethods[$methodName])) {
        static::$optionalTraitsMethods[$methodName] = function ($model, ...$params) use ($dynamicClass, $methodName) {
          return $dynamicClass->{$methodName}($model, ...$params);
        };
      }
    }
  }

  /**
   * Registers dynamic event listeners for the model.
   *
   * @param object $dynamicClass The dynamic class providing the event listeners.
   * @param array $events Associative array of event names and corresponding method names.
   */
  protected static function instanceEventListeners($dynamicClass, $events)
  {
    foreach ($events as $eventName => $methodName) {
      // Register the event listener dynamically
      static::registerModelEvent($eventName, function ($event) use ($dynamicClass, $methodName, $eventName) {
        $modelParams = !str_contains($eventName, 'WithBindings') ? $event :
          ['bindings' => $event->getEventBindings($eventName), 'model' => $event];
        return $dynamicClass->{$methodName}($modelParams);
      });
    }
  }

  /**
   * Adds dynamic attributes to the model.
   *
   * @param object $dynamicClass The dynamic class providing the methods.
   * @param array $methods List of method names to be added to the model.
   */
  protected static function instanceGetAttributes($dynamicClass, $attributes)
  {
    foreach ($attributes as $attributeName) {
      if (!isset(static::$optionalTraitsAttributes[$attributeName])) {
        static::$optionalTraitsAttributes[$attributeName] = function ($model, ...$params) use ($dynamicClass, $attributeName) {
          return $dynamicClass->{$attributeName}($model);
        };
      }
    }
  }

  /**
   * Executes a dynamically added method on the model.
   *
   */
  public function optionalTraitMethod($method, $params = [], $defaultResponse = null)
  {
    if (isset(static::$optionalTraitsMethods[$method])) {
      return static::$optionalTraitsMethods[$method]($this, ...$params);
    }

    return $defaultResponse;
  }

  /**
   * Retrieves a dynamically added relation on the model.
   */
  public function optionalTraitRelation($relation, $asFunction = false)
  {
    if (in_array($relation, static::$optionalTraitsRelations)) {
      return $asFunction ? $this->{$relation}() : $this->{$relation};
    }
    return null;
  }

  /**
   * Executes a dynamically added attribute on the model.
   *
   */
  public function optionalTraitAttribute($attribute, $defaultResponse = null)
  {
    $key = 'get' . ucfirst($attribute) . 'Attribute';
    if (isset(static::$optionalTraitsAttributes[$key])) {
      return static::$optionalTraitsAttributes[$key]($this);
    }

    return $defaultResponse;
  }

  /**
   * Return the optionalTrait information
   * @return array
   */
  public function getOptionalTraitInformation()
  {
    return [
      'methods' => static::$optionalTraitsMethods,
      'relations' => static::$optionalTraitsRelations,
      'attributes' => static::$optionalTraitsAttributes
    ];
  }

  /**
   * Handles dynamic method calls on the model instance.
   *
   */
  public function __call($method, $parameters)
  {
    if (isset(static::$optionalTraitsMethods[$method])) {
      return static::$optionalTraitsMethods[$method]($this, ...$parameters);
    }

    return parent::__call($method, $parameters);
  }
}
