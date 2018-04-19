<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\AccessTokenInterface;
use \Yii;

/**
 * Server storage for access tokens
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class AccessToken extends MongoStorage implements AccessTokenInterface
{
    // Probability to perform garbage collection (percentage in int)
    const GC_PROBABILITY = 1;

    /**
     * @var array list of access tokens
     */
    protected $_tokens = array();

    /**
     * @return string name of the DB table
     */
    protected function getCollectionName()
    {
        return $this->getOAuth2()->accessTokenTable;
    }

    /**
     * Required by OAuth2\Storage\AccessTokenInterfaces
     *
     * @param mixed $token the access token
     * @return null|array with keys client_id, user_id, expires and (optional) scope, null if not found
     */
    public function getAccessToken($token)
    {
        if(isset($this->_tokens[$token])) {
            return $this->_tokens[$token];
        }
        YII_DEBUG && Yii::trace("Querying access token $token",'oauth2.storage.accesstoken');

        $result = $this->getCollection()->findOne(array('access_token' => $token));

        if($result===null) {
            YII_DEBUG && Yii::trace("Access token '$token' not found",'oauth2.storage.accesstoken');
            return null;
        }

        YII_DEBUG && Yii::trace(
            sprintf("Access token found: %s, client_id: %s, user_id: %s, expires: %s, scope: %s",
                $token,
                $result['client_id'],
                $result['user_id'],
                $result['expires'],
                $result['scope']
            ),
            'oauth2.storage.accesstoken'
        );

        $result['expires'] = strtotime($result['expires']);

        $this->_tokens[$token] = $result;

        return $result;
    }

    /**
     * Required by OAuth2\Storage\AccessTokenInterfaces
     *
     * @param mixed $token to be stored
     * @param mixed $client_id to be stored
     * @param mixed $user_id id to be stored
     * @param mixed $expires as unix timestamp to be stored
     * @param mixed $scope (optional) scopes to be stored as space separated string
     * @return bool whether record was stored successfully
     */
    public function setAccessToken($token, $client_id, $user_id, $expires, $scope = null)
    {
        if(mt_rand(0,100) < self::GC_PROBABILITY) {
            $this->removeExpired();
        }

        $values = array(
            'access_token'  => $token,
            'client_id'     => $client_id,
            'user_id'       => $user_id,
            'expires'       => date('Y-m-d H:i:s', $expires),
            'scope'         => $scope,
        );

        YII_DEBUG && Yii::trace(
            sprintf("Saving access token '%s'. client_id: %s, user_id: %s, expires: %s, scope: %s",
                $token,
                $client_id,
                $user_id,
                $expires,
                $scope
            ),
            'oauth2.storage.accesstoken'
        );

        $result = $this->getCollection()->update(array('access_token' => $token), $values, array('upsert' => true));

        return is_array($result) ? (bool)$result['ok'] : $result;
    }

    /**
     * Remove expired access tokens
     */
    protected function removeExpired()
    {
        YII_DEBUG && Yii::trace("Removing expired access tokens",'oauth2.storage.accesstoken');
        $now = date('Y-m-d H:i:s', new \DateTime());
        $this->getCollection()->remove(array('expires' => array('$lt' => $now)));
    }
}
