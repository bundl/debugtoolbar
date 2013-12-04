<?php
/**
 * @author  brooke.bryan
 */

namespace Bundl\DebugToolbar\Collectors;

class RequestDataCollector extends \DebugBar\DataCollector\RequestDataCollector
{
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

    return $data;
  }
}
