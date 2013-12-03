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

    return $data;
  }
}
