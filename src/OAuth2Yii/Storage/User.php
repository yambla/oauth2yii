<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\UserCredentialsInterface;

/**
 * Server storage for user data
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class User extends MongoStorage implements UserCredentialsInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getCollectionName()
    {
        return $this->getOAuth2()->userTable;
    }

    /**
     * Required by OAuth2\Storage\UserCredentialsInterfaces
     *
     * @param mixed $username
     * @param mixed $password
     * @return bool whether credentials are valid
     */
    public function checkUserCredentials($username, $password)
    {
        $storedUser = $this->getUserDetails($username);

        return $storedUser['password'] === md5($password);
    }

    /**
     * Required by OAuth2\Storage\UserCredentialsInterfaces
     *
     * @param string $username
     * @return array with keys scope and user_id
     */
    public function getUserDetails($username)
    {
        return $this->getCollection()->findOne(array('username' => $username));
    }

}
