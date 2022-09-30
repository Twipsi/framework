<?php
declare(strict_types=1);

/*
* This file is part of the Twipsi package.
*
* (c) Petrik Gábor <twipsi@twipsi.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Twipsi\Components\Session\Drivers;

use Twipsi\Support\Chronos;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Components\Session\Interfaces\SessionDriverInterface;

class DatabaseSessionDriver implements SessionDriverInterface
{
  /**
  * Database connection.
  */
  protected IDatabaseConnection $connection;

  /**
  * Database table to query.
  */
  protected string $table;

  /**
  * Validity time in minutes.
  */
  protected int $validity;

  /**
  * Construct driver.
  */
  public function __construct(IDatabaseConnection $connection, string $table, int $minutes)
  {
    $this->connection = $connection;
    $this->table = $table;
    $this->validity = $minutes;
  }

  /**
  * Read session from driver.
  */
  public function read(string $id) :? string
  {
    if (! $current = $this->connection->open($this->table)->find($id)) {
      return null;
    }

    if (! isset($current->stamp) || !isset($current->content)) {
      return null;
    }

    if ($this->validity < Chronos::date()->travel($current->stamp)->minutesPassed()) {
      return null;
    }

    return $current->content;
  }

  /**
  * Write to session driver.
  */
  public function write(string $id, string $content) : void
  {
    $this->connection->open($this->table)
    ->where('id', '=', $id)
    ->updateOrCreate(
      [
        'content' => $content,
        'stamp'   => Chronos::date()->getDateTime(),
      ]
    );
  }

  /**
  * Destroy session in driver.
  */
  public function destroy(string $id) : void
  {
    $this->connection->open($this->table)->where('id', '=', $id)
    ->delete();
  }

  /**
  * Validate all sessions in driver.
  */
  public function clean(int $lifetime) : void
  {
    $this->connection->open($this->table)->where(
      'stamp', '<=', Chronos::date()->subMinutes($this->validity)->getDateTime()
      )->delete();
  }

}
