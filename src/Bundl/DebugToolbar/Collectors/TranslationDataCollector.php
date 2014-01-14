<?php
namespace Bundl\DebugToolbar\Collectors;

use Cubex\Events\EventManager;
use Cubex\Events\IEvent;
use DebugBar\DataCollector\DataCollector;
use \DebugBar\DataCollector\Renderable;

class TranslationDataCollector extends DataCollector implements Renderable
{
  protected $_translations = [];

  public function __construct()
  {
    EventManager::spy(
      EventManager::CUBEX_TRANSLATE_T,
      [$this, "listenT"]
    );
    EventManager::spy(
      EventManager::CUBEX_TRANSLATE_P,
      [$this, "listenP"]
    );
  }

  public function listenT(IEvent $event, $response)
  {
    if($event->getStr("text") !== $response)
    {
      $this->_translations[$event->getStr("text")] = $response;
    }
  }

  public function listenP(IEvent $event, $response)
  {
    $expect = $event->getInt("number", 0) === 1 ? 'singular' : 'plural';
    if($event->getStr($expect) !== $response)
    {
      $this->_translations[$event->getStr("singular")] = $response;
      $this->_translations[$event->getStr("plural")] = $response;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function collect()
  {
    return $this->_translations;
  }

  /**
   * {@inheritDoc}
   */
  public function getName()
  {
    return 'translations';
  }

  /**
   * {@inheritDoc}
   */
  public function getWidgets()
  {
    if(empty($this->_translations))
    {
      return null;
    }
    return array(
      "translations" => array(
        "icon"    => "tags",
        "widget"  => "PhpDebugBar.Widgets.VariableListWidget",
        "map"     => "translations",
        "default" => "{}"
      )
    );
  }
}
