<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\AuthorizationCodeInterface;

/**
 * Server storage for authorization codes
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class AuthorizationCode extends MongoStorage implements AuthorizationCodeInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getCollectionName()
    {
        return $this->getOAuth2()->authorizationCodeTable;
    }

    /**
     * Required by OAuth2\Storage\AuthorizationCodeInterfaces
     *
     * @param mixed $code authorization code to check
     * @return null|array with keys client_id, user_id, expires, redirect_uri and (optional) scope, null if not found
     */
    public function getAuthorizationCode($code)
    {
        $result = $this->getCollection()->findOne(array('code' => $code));

        if($result===null)
            return null;

        $result['expires'] = strtotime($result['expires']);

        return $result;
    }

    /**
     * Required by OAuth2\Storage\AuthorizationCodeInterfaces
     *
     * @param mixed $code to be stored
     * @param mixed $client_id to be stored
     * @param mixed $user_id id to be stored
     * @param mixed $redirect_uri one or several URIs (space separated) to be stored
     * @param mixed $expires as unix timestamp to be stored
     * @param mixed $scope (optional) scopes to be stored as space separated string
     * @return bool whether record was stored successfully
     */
    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null)
    {
        $values = array(
            'authorization_code'  => $code,
            'client_id'           => $client_id,
            'user_id'             => $user_id,
            'redirect_uri'        => $redirect_uri,
            'expires'             => date('Y-m-d H:i:s', $expires),
            'scope'               => $scope,
        );

        $result = $this->getCollection()->update(array('authorization_code' => $code), $values, array('upsert' => true));

        return is_array($result) ? (bool)$result['ok'] : $result;
    }

    /**
     * Required by OAuth2\Storage\AuthorizationCodeInterfaces
     *
     * @param mixed $code to expire
     */
    public function expireAuthorizationCode($code)
    {
        $result = $this->getCollection()->remove(array('authorization_code' => $code));

        return is_array($result) ? (bool)$result['ok'] : $result;
    }

}
