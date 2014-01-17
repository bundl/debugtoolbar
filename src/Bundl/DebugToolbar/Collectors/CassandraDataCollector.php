<?php
/**
 * @author  brooke.bryan
 */

namespace Bundl\DebugToolbar\Collectors;

use Cubex\Events\EventManager;
use Cubex\Events\IEvent;
use DebugBar\DataCollector\TimeDataCollector;

class CassandraDataCollector extends TimeDataCollector
{
  protected $_statements;

  public function __construct($requestStartTime = null)
  {
    parent::__construct($requestStartTime);
    if(class_exists('\Cubex\Cassandra\ColumnFamily'))
    {
      EventManager::listen(
        \Cubex\Cassandra\ColumnFamily::QUERY_EVENT,
        [$this, "getQuery"]
      );
    }
  }

  public function getQuery(IEvent $event)
  {
    $result = $event->getRaw("result");
    $rows   = 0;
    if(is_array($result))
    {
      $rows = count($result);
    }

    $memory = memory_get_peak_usage(true);
    $error  = $event->getArr("error", []);

    $args = [];
    foreach($event->getArr("args", []) as $arg)
    {
      if(is_array($arg) || is_object($arg))
      {
        $args[] = json_encode($arg);
      }
      else if($arg === null)
      {
        $args[] = 'null';
      }
      else if(is_bool($arg))
      {
        $args[] = $arg ? 'true' : 'false';
      }
      else
      {
        $args[] = '"' . $arg . '"';
      }
    }

    $query = sprintf(
      "%s:%s.%s(%s)",
      $event->getStr("keyspace"),
      $event->getStr("column_family"),
      $event->getStr("method"),
      implode(', ', $args)
    );

    $this->_addStatement(
      $query,
      $event->getFloat("execution_time", 0),
      $rows,
      $memory,
      idx($error, 'num', 0),
      idx($error, 'msg', ''),
      (idx($error, 'num', 0) == 0)
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

    if($this->_statements)
    {
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
    return 'cassandra';
  }

  public function getWidgets()
  {
    if(empty($this->_statements))
    {
      return null;
    }

    return array(
      "cassandra"       => array(
        "widget"  => "PhpDebugBar.Widgets.SQLQueriesWidget",
        "map"     => "cassandra",
        "default" => "[]"
      ),
      "cassandra:badge" => array(
        "map"     => "cassandra.nb_statements",
        "default" => 0
      )
    );
  }
}
