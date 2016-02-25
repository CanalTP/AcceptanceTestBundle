<?php

namespace CanalTP\AcceptanceTestBundle\Service;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use CanalTP\AcceptanceTestBundle\Behat\MinkExtension\Context\MinkContext;

/**
 * Api examples loader service
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 * @copyright Canal TP (c) 2016
 */
class ApiExamplesLoaderService
{
    /**
     * Examples getter
     *
     * @param string $uri
     * @return mixed
     */
    public function getExamples($uri)
    {
        return $this->call($uri);
    }

    /**
     * Api call
     *
     * @param string $uri
     * @return mixed
     * @throws NotFoundHttpException
     */
    private function call($uri)
    {
        $absoluteUri = $this->getAbsoluteUri($uri);

        $curlHandle = curl_init();

        curl_setopt($curlHandle, CURLOPT_URL, $absoluteUri);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT_MS, 5000);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, false);

        $curlExec = curl_exec($curlHandle);
        $curlError = !$curlExec || curl_errno($curlHandle);
        $contentType = !$curlError ? curl_getinfo($curlHandle, CURLINFO_CONTENT_TYPE) : null;
        curl_close($curlHandle);

        $data = array();
        if ($curlError) {
            throw new NotFoundHttpException(sprintf('Api not reachable: %s', $absoluteUri));
        } else {
            $data = $this->getResult($curlExec, $contentType);
        }

        return $data;
    }

    /**
     * Absolute uri getter
     *
     * @param string $uri
     * @return string
     */
    private function getAbsoluteUri($uri)
    {
        if (strpos($uri, 'http') === false) {
            $context = new MinkContext();
            $uri = $context->getBaseUrl().$uri;
        }

        return $uri;
    }

    /**
     * Result getter according to content type
     *
     * @param mixed $curlExec
     * @param mixed $contentType
     * @return array
     * @throws \Exception
     */
    private function getResult($curlExec, $contentType)
    {
        $examples = array();
        switch ($contentType) {
            case 'application/json':
                $examples = json_decode($curlExec);
                break;
            default:
                throw new \Exception(sprintf('Api content type not supported: %s', $contentType ?: 'null'));
        }

        return $this->getFormattedResult($examples);
    }

    private function getFormattedResult($examples)
    {
        $result = array();
        if (!empty($examples)) {
            $keys = array();
            foreach ($examples as $index => $example) {
                $values = get_object_vars($example);
                if (!$index) {
                    $keys = array_keys($values);
                    $result[] = $keys;
                }
                $result[] = array_values($values);
            }
        }

        return $result;
    }
}
