<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\ClientInterface;
use \OAuth2\Storage\ClientCredentialsInterface;

/**
 * Server storage for client data
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class Client extends MongoStorage implements ClientInterface, ClientCredentialsInterface
{
    /**
     * @return string name of the DB table
     */
    protected function getCollectionName()
    {
        return $this->getOAuth2()->clientTable;
    }

    /**
     * Required by OAuth2\Storage\ClientInterfaces
     *
     * @param mixed $client_id
     * @return array with keys redirect_uri, client_id and optional grant_types
     */
    public function getClientDetails($client_id)
    {
        return $this->getCollection()->findOne(array('client_id' => $client_id));
    }

    public function getClientScope($client_id)
    {
        return '';
    }

    public function isPublicClient($client_id)
    {
        return false;
    }

    /**
     * Required by OAuth2\Storage\ClientInterfaces
     *
     * @param string $client_id
     * @param string $grant_type as defined by RFC 6749
     */
    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            return in_array($grant_type, $details['grant_types']);
        }
        return true;
    }

    /**
     * Required by OAuth2\Storage\ClientCredentialsInterfaces
     *
     * @param string $client_id
     * @param string $client_secret
     * @return bool whether the client credentials are valid
     */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $storedDetails = $this->getClientDetails($client_id);

        return md5($client_secret) === $storedDetails['client_secret'];
    }
}
