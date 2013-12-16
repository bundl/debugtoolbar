<?php
/**
 * @author  brooke.bryan
 */

namespace Bundl\DebugToolbar\Collectors;

use Cubex\Events\EventManager;
use Cubex\Events\IEvent;

class RequestDataCollector extends \DebugBar\DataCollector\RequestDataCollector
{
  const REQUESTDATACOLLECTOR_ADDITIONAL = 'bundl.debugtoolbar.rdc.add.itional';

  protected $_additional = [];

  public function __construct()
  {
    EventManager::listen(
      RequestDataCollector::REQUESTDATACOLLECTOR_ADDITIONAL,
      [$this, "addAdditionalEvent"]
    );
  }

  public function addAdditionalEvent(IEvent $event)
  {
    $this->addAdditional($event->getStr("key"), $event->getStr("value"));
  }

  public function addAdditional($key, $value)
  {
    $this->_additional[$key] = $value;
  }

  public function collect()
  {
    $vars = array('_GET', '_POST', '_SESSION', '_SERVER');
    $data = array();

    foreach($vars as $var)
    {
      if(isset($GLOBALS[$var]))
      {
        $data["$" . $var] = $this->formatVar($GLOBALS[$var]);
      }
    }

    $data['Environment'] = CUBEX_ENV;
    $data['Transaction'] = CUBEX_TRANSACTION;
    $data['Locale']      = defined("LOCALE") ? LOCALE : 'Disabled';

    foreach($this->_additional as $key => $value)
    {
      $data[$key] = $value;
    }

    return $data;
  }
}
