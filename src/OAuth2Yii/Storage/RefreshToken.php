<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\RefreshTokenInterface;
use \Yii;

/**
 * Serer storage for refresh tokens
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class RefreshToken extends MongoStorage implements RefreshTokenInterface
{
    // Probability to perform garbage collection (percentage in int)
    const GC_PROBABILITY = 100;

    /**
     * @return string name of the DB table
     */
    protected function getCollectionName()
    {
        return $this->getOAuth2()->refreshTokenTable;
    }

    /**
     * Required by \OAuth2\Storage\RefreshTokenInterfaces
     *
     * @param mixed $token refresh token
     * @return array with keys refresh_token, client_id, user_id, expires and scope
     */
    public function getRefreshToken($token)
    {
        $result = $this->getCollection()->findOne(array('refresh_token' => $token));

        if($result===null)
            return null;

        YII_DEBUG && Yii::trace(
            sprintf("Refresh token '%s' found. client_id: %s, user_id: %s, expires: %s, scope: %s",
                $token,
                $result['client_id'],
                $result['user_id'],
                $result['expires'],
                $result['scope']
            ),
            'oauth2.storage.refreshtoken'
        );

        $result['expires'] = strtotime($result['expires']);
        
        return $result;
    }

    /**
     * Required by \OAuth2\Storage\RefreshTokenInterfaces
     *
     * @param mixed $token to be stored
     * @param mixed $client_id to be stored
     * @param mixed $user_id id to be stored
     * @param mixed $expires as unix timestamp to be stored
     * @param mixed $scope (optional) scopes to be stored as space separated string
     * @return bool whether record was stored successfully
     */
    public function setRefreshToken($token, $client_id, $user_id, $expires, $scope = null)
    {
        if(mt_rand(0,100) < self::GC_PROBABILITY) {
            $this->removeExpired();
        }

        $values = array(
            'refresh_token' => $token,
            'client_id'     => $client_id,
            'user_id'       => $user_id,
            'expires'       => date('Y-m-d H:i:s', $expires),
            'scope'         => $scope,
        );

        YII_DEBUG && Yii::trace(
            sprintf("Saving refresh token '%s'. client_id: %s, user_id: %s, expires: %s, scope: %s",
                $token,
                $client_id,
                $user_id,
                $expires,
                $scope
            ),
            'oauth2.storage.refreshtoken'
        );

        $result = $this->getCollection()->update(array('refresh_token' => $token), $values, array('upsert' => true));

        return is_array($result) ? (bool)$result['ok'] : $result;
    }


    /**
     * Required by \OAuth2\Storage\RefreshTokenInterfaces
     *
     * @param mixed $token to unset
     * @return bool whether token was removed
     */
    public function unsetRefreshToken($token)
    {
        $result = $this->getCollection()->remove(array('refresh_token' => $token));

        return is_array($result) ? (bool)$result['ok'] : $result;
    }

    /**
     * Remove expired refresh tokens
     */
    protected function removeExpired()
    {
        YII_DEBUG && Yii::trace("Removing expired refresh tokens",'oauth2.storage.refreshtoken');
        $now = date('Y-m-d H:i:s', new \DateTime());
        $this->getCollection()->remove(array('expires' => array('$lt' => $now)));
    }
}
