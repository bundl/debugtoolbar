<?php
/**
 * @author  brooke.bryan
 */

namespace Bundl\DebugToolbar\Collectors;

use Cubex\Events\EventManager;
use Cubex\Events\IEvent;
use DebugBar\DataCollector\TimeDataCollector;

class CubexCoreTimeData extends TimeDataCollector
{
  public function __construct($requestStartTime = null)
  {
    parent::__construct($requestStartTime);
    EventManager::listen(
      EventManager::CUBEX_TIMETRACK_START,
      [$this, "trackStart"]
    );
    EventManager::listen(
      EventManager::CUBEX_TIMETRACK_END,
      [$this, "trackEnd"]
    );
    //$this->addMeasure("Starting Project", PHP_START, microtime(true));
    $this->startMeasure("project.dispatch", "Dispatching Project");
  }

  public function trackStart(IEvent $event)
  {
    $name = $event->getStr("name", null);
    if($name !== null)
    {
      $this->startMeasure($name, $event->getStr("label", $name));
    }
  }

  public function trackEnd(IEvent $event)
  {
    $name = $event->getStr("name", null);
    if($name !== null)
    {
      $this->stopMeasure($name);
    }
  }

  public function getName()
  {
    return 'cubexcoretime';
  }

  public function getWidgets()
  {
    return array(
      "time"           => array(
        "icon"    => "time",
        "tooltip" => "Request Duration",
        "map"     => "time.duration_str",
        "default" => "'0ms'"
      ),
      "cubex_Timeline" => array(
        "widget"  => "PhpDebugBar.Widgets.TimelineWidget",
        "map"     => "cubexcoretime",
        "default" => "{}"
      )
    );
  }
}
