<?php
namespace OAuth2Yii\Storage;

use \Yii as Yii;
use \CException as CException;

/**
 * Base class for EMongoDb based server storages
 *
 * @author Ken Foncé <ken.fonce@yambla.com>
 */
abstract class MongoStorage extends Storage
{
  /**
   * @var \EMongoDB the connection to use for this storage
   */
  protected $_db;

  /**
   * @return string name of the DB table
   */
  protected abstract function getCollectionName();

  /**
   * Get collection for this storage
   *
   * @throws
   * @return \MongoCollection of this storage
   */
  public function getCollection()
  {
    return $this->getDb()->selectCollection($this->getCollectionName());
  }


  /**
   * Create collection for this storage
   */
  public function createCollection()
  {
    YII_DEBUG && Yii::trace("Creating collection '{$this->getCollectionName()}'", 'oauth2.storage');
    $this->getDb()->createCollection($this->getCollectionName());
  }

  /**
   * @param \OAuth2Yii\Component\ServerComponent $server the server component
   * @param string $db id of the EMongoDB component
   *
   * @throws \CException if the component cannot be found
   */
  public function __construct(\OAuth2Yii\Component\ServerComponent $server, $db)
  {
    parent::__construct($server);

    if(!Yii::app()->hasComponent($db)) {
      throw new CException("Unknown component '$db'");
    }

    $this->_db = Yii::app()->getComponent($db);

    if(!in_array($this->getCollectionName(),  $this->getDb()->getCollectionNames())) {
      $this->createCollection();
    }
  }

  /**
   * @return \MongoDB to use for this storage
   */
  public function getDb()
  {
    return $this->_db->getDbInstance();
  }
}
