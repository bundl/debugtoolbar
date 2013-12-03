<?php
/**
 * @author  brooke.bryan
 */

namespace Bundl\DebugToolbar;

use Bundl\DebugToolbar\Collectors\RequestDataCollector;
use Cubex\Bundle\Bundle;
use Cubex\Events\EventManager;
use Cubex\Events\IEvent;
use Cubex\Foundation\Config\ConfigTrait;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;

/**
 * Please add the following to your defaults.ini
 *
 * [dispatch]
 * passthrough[_debugbar] = vendor/maximebf/debugbar/src/DebugBar/Resources
 *
 */
class DebugToolbarBundl extends Bundle
{
  use ConfigTrait;

  /**
   * @var DebugBar
   */
  protected $_debugBar;
  /**
   * @var \DebugBar\JavascriptRenderer
   */
  protected $_debugRender;

  public function init($initialiser = null)
  {
    $toolbarConfig = $this->config("bundl\\debugtoolbar");
    $baseUrl       = $toolbarConfig->getStr("base_url", '/_debugbar');

    if(starts_with($baseUrl, '/'))
    {
      $passthroughs = $this->config("dispatch")->getArr("passthrough");
      if(!isset($passthroughs[substr($baseUrl, 1)]))
      {
        throw new \Exception(
          "Please add the following to your defaults.ini\n" .
          "passthrough[" . $baseUrl . "] = " .
          "vendor/maximebf/debugbar/src/DebugBar/Resources"
        );
      }
    }

    EventManager::listen(
      EventManager::CUBEX_WEBPAGE_RENDER_BODY,
      [$this, "renderBody"]
    );

    EventManager::listen(
      EventManager::CUBEX_WEBPAGE_RENDER_HEAD,
      [$this, "renderHead"]
    );

    EventManager::listen(
      EventManager::CUBEX_LOG,
      [$this, "catchLog"]
    );

    EventManager::listen(
      EventManager::CUBEX_QUERY,
      [$this, "catchQuery"]
    );

    $this->_debugBar = new DebugBar();
    $this->_debugBar->addCollector(new PhpInfoCollector());
    $this->_debugBar->addCollector(new MessagesCollector());
    $this->_debugBar->addCollector(new RequestDataCollector());
    $this->_debugBar->addCollector(new TimeDataCollector());
    $this->_debugBar->addCollector(new MemoryCollector());
    $this->_debugBar->addCollector(new ExceptionsCollector());

    $this->_debugRender = $this->_debugBar->getJavascriptRenderer();
    $this->_debugRender->setBaseUrl($baseUrl);
  }

  public function renderHead(IEvent $event)
  {
    $content = $event->getStr("content");
    $content .= $this->_debugRender->renderHead();
    return $content;
  }

  public function renderBody(IEvent $event)
  {
    $content = $event->getStr("content");
    $content .= $this->_debugRender->render();
    return $content;
  }

  public function catchLog(IEvent $event)
  {
    $this->_debugBar['messages']->addMessage(
      $event->getStr("message"),
      $event->getStr("level")
    );
  }

  public function catchQuery(IEvent $event)
  {
    $this->_debugBar['time']->addMeasure(
      $event->getStr("query"),
      $event->getStr("start_time"),
      $event->getStr("end_time")
    );
  }
}
