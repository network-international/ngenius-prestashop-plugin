<?php

/**
 * Hosted Session Ngenius TransferFactory
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

class TransferFactory
{
    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $body = array();

    /**
     * @var api curl uri
     */
    private $uri = '';

    /**
     * @var method
     */
    private $method;

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        if (is_array($request['request'])) {
            return $this->setBody($request['request']['data'])
                ->setMethod($request['request']['method'])
                ->setHeaders(array(
                    '0' => 'Authorization: Bearer ' . $request['token'],
                    '1' => 'Content-Type: application/vnd.ni-payment.v2+json',
                    '2' => 'Accept: application/vnd.ni-payment.v2+json'
                ))
                ->setUri($request['request']['uri']);
        }
    }

    /**
     * Set header for transfer object
     *
     * @param array $headers
     * @return Transferfactory
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Set body for transfer object
     *
     * @param array $body
     * @return Transferfactory
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set method for transfer object
     *
     * @param array $method
     * @return Transferfactory
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set uri for transfer object
     *
     * @param array $uri
     * @return Transferfactory
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Retrieve method from transfer object
     *
     * @return string
     */
    public function getMethod()
    {
        return (string) $this->method;
    }

    /**
     * Retrieve header from transfer object
     *
     * @return Transferfactory
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Retrieve body from transfer object
     *
     * @return Transferfactory
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Retrieve uri from transfer object
     *
     * @return string
     */
    public function getUri()
    {
        return (string) $this->uri;
    }
}
