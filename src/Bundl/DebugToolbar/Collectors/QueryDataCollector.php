<?php
/**
 * @author  brooke.bryan
 */

namespace Bundl\DebugToolbar\Collectors;

use Cubex\Events\EventManager;
use Cubex\Events\IEvent;
use DebugBar\DataCollector\TimeDataCollector;

class QueryDataCollector extends TimeDataCollector
{
  protected $_statements;

  public function __construct($requestStartTime = null)
  {
    parent::__construct($requestStartTime);
    EventManager::listen(
      EventManager::CUBEX_QUERY,
      [$this, "getQuery"]
    );
  }

  public function getQuery(IEvent $event)
  {
    $result = $event->getRaw("result");
    $rows   = 0;
    if($result instanceof \mysqli_result)
    {
      $rows = $result->num_rows;
    }

    $memory = memory_get_peak_usage(true);

    list($errNo, $errMsg) = $event->getArr("error", ["num" => 0, "msg" => '']);

    $this->_addStatement(
      $event->getStr("query"),
      $event->getFloat("execution_time", 0),
      $rows,
      $memory,
      $errNo,
      $errMsg,
      ($errNo == 0)
    );
  }

  protected function _addStatement(
    $sql, $execTime, $rows, $memory, $errorNumber, $errorMessage, $passed
  )
  {
    $this->_statements[] = [
      'sql'           => $sql,
      'row_count'     => $rows,
      'duration'      => $execTime,
      'duration_str'  => $this->formatDuration($execTime),
      'memory'        => $memory,
      'memory_str'    => $this->formatBytes($memory),
      'is_success'    => $passed,
      'error_code'    => $errorNumber,
      'error_message' => $errorMessage
    ];
  }

  public function collect()
  {
    $data = array(
      'nb_statements'        => 0,
      'nb_failed_statements' => 0,
      'accumulated_duration' => 0,
      'peak_memory_usage'    => 0,
      'statements'           => []
    );

    foreach($this->_statements as $statement)
    {
      $data['statements'][] = $statement;
      $data['nb_statements']++;
      $data['accumulated_duration'] += $statement['duration'];
      $data['peak_memory_usage'] = max(
        $data['peak_memory_usage'],
        $statement['memory']
      );
    }

    $data['accumulated_duration_str'] = $this->formatDuration(
      $data['accumulated_duration']
    );
    $data['peak_memory_usage_str']    = $this->formatBytes(
      $data['peak_memory_usage']
    );

    return $data;
  }

  public function getName()
  {
    return 'queries';
  }

  public function getWidgets()
  {
    return array(
      "database"       => array(
        "widget"  => "PhpDebugBar.Widgets.SQLQueriesWidget",
        "map"     => "queries",
        "default" => "[]"
      ),
      "database:badge" => array(
        "map"     => "queries.nb_statements",
        "default" => 0
      )
    );
  }
}
