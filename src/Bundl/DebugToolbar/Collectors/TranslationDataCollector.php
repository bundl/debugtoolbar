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

  public function addTranslation($source, $translated)
  {
    $key                       = md5($source) . '-' . strlen($source);
    $this->_translations[$key] = "<strong>From:</strong> $source".
      "\n<strong>To:</strong> $translated";
  }

  public function listenT(IEvent $event, $response)
  {
    if($event->getStr("text") !== $response)
    {
      $this->addTranslation($event->getStr("text"), $response);
    }
  }

  public function listenP(IEvent $event, $response)
  {
    $expect = $event->getInt("number", 0) === 1 ? 'singular' : 'plural';
    if($event->getStr($expect) !== $response)
    {
      $this->addTranslation($event->getStr("singular"), $response);
      $this->addTranslation($event->getStr("plural"), $response);
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
        "widget"  => "PhpDebugBar.Widgets.KVListWidget",
        "map"     => "translations",
        "default" => "{}"
      )
    );
  }
}
