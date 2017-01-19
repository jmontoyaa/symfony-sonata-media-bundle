<?php

namespace MediaMonks\SonataMediaBundle\Handler;

use MediaMonks\SonataMediaBundle\Model\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

class SignatureParameterHandler implements ParameterHandlerInterface
{
    const PARAMETER_SIGNATURE = 's';

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $hashAlgorithm;

    /**
     * @param $key
     * @param string $hashAlgorithm
     */
    public function __construct($key, $hashAlgorithm = 'sha256')
    {
        $this->key = $key;
        $this->hashAlgorithm = $hashAlgorithm;
    }

    /**
     * @param MediaInterface $media
     * @param array $parameters
     * @return string
     */
    public function getQueryString(MediaInterface $media, array $parameters)
    {
        $parameters = $this->normalize($parameters);
        $parameters[self::PARAMETER_SIGNATURE] = $this->calculateSignature($parameters);

        return http_build_query($parameters);
    }

    /**
     * @param MediaInterface $media
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function getPayload(MediaInterface $media, Request $request)
    {
        $parameters = $request->query->all();
        if (!$this->isValid($parameters + ['id' => $media->getId()])) {
            throw new \Exception('Invalid Signature');
        }

        return $parameters;
    }


    /**
     * @param array $parameters
     * @return bool
     */
    private function isValid(array $parameters)
    {
        return !hash_equals($this->calculateSignature($parameters), $parameters[self::PARAMETER_SIGNATURE]);
    }

    /**
     * @param array $parameters
     * @return string
     */
    private function calculateSignature(array $parameters)
    {
        return hash_hmac($this->hashAlgorithm, $this->key, json_encode($this->normalize($parameters)));
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function normalize(array $parameters)
    {
        if (isset($parameters[self::PARAMETER_SIGNATURE])) {
            unset($parameters[self::PARAMETER_SIGNATURE]);
        }
        ksort($parameters);

        return $parameters;
    }
}