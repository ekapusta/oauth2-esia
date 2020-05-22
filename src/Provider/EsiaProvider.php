<?php

namespace Ekapusta\OAuth2Esia\Provider;

use Ekapusta\OAuth2Esia\Interfaces\Provider\ProviderInterface;
use Ekapusta\OAuth2Esia\Interfaces\Security\SignerInterface;
use Ekapusta\OAuth2Esia\Interfaces\Token\ScopedTokenInterface;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use InvalidArgumentException;
use Lcobucci\JWT\Parsing\Encoder;
use Lcobucci\JWT\Signer as RemoteSignerInterface;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

class EsiaProvider extends AbstractProvider implements ProviderInterface
{
    use BearerAuthorizationTrait;

    const RESOURCES = __DIR__.'/../../resources/';

    protected $defaultScopes = ['openid', 'fullname'];

    protected $remoteUrl = 'https://esia.gosuslugi.ru';

    protected $remotePublicKey = self::RESOURCES.'esia.prod.public.key';

    /**
     * @var SignerInterface
     */
    private $signer;

    /**
     * @var RemoteSignerInterface
     */
    private $remoteSigner;

    /**
     * @var Encoder
     */
    private $encoder;

    public function __construct(array $options = [], array $collaborators = [])
    {
        // Backward compatibility as of rename remoteCertificatePath -> remotePublicKey
        if (isset($options['remoteCertificatePath'])) {
            $options['remotePublicKey'] = $options['remoteCertificatePath'];
        }

        parent::__construct($options, $collaborators);
        if (!filter_var($this->remoteUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Remote URL is not provided!');
        }
        if (!file_exists($this->remotePublicKey)) {
            throw new InvalidArgumentException('Remote public key is not provided!');
        }

        if (isset($collaborators['signer']) && $collaborators['signer'] instanceof SignerInterface) {
            $this->signer = $collaborators['signer'];
            $this->encoder = new Encoder();
        } else {
            throw new InvalidArgumentException('Signer is not provided!');
        }

        if (isset($collaborators['remoteSigner']) && $collaborators['remoteSigner'] instanceof RemoteSignerInterface) {
            $this->remoteSigner = $collaborators['remoteSigner'];
        }
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->getUrl('/aas/oauth2/ac');
    }

    protected function getAuthorizationParameters(array $options)
    {
        $options = [
            'access_type' => 'online',
            'approval_prompt' => null,
            'timestamp' => $this->getTimeStamp(),
        ] + parent::getAuthorizationParameters($options);

        return $this->withClientSecret($options);
    }

    /**
     * @return array
     */
    private function withClientSecret(array $params)
    {
        $message = $params['scope'].$params['timestamp'].$params['client_id'].$params['state'];
        $signature = $this->signer->sign($message);
        $params['client_secret'] = $this->encoder->base64UrlEncode($signature);

        return $params;
    }

    protected function getRandomState($length = 32)
    {
        return Uuid::uuid4()->toString();
    }

    public function generateState()
    {
        return $this->getRandomState();
    }

    private function getTimeStamp()
    {
        return date('Y.m.d H:i:s O');
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getUrl('/aas/oauth2/te');
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $embeds = $this->getResourceOwnerEmbeds($token);

        return $this->getUrl('/rs/prns/'.$token->getResourceOwnerId().'?embed=('.implode(',', $embeds).')');
    }

    private function getResourceOwnerEmbeds(ScopedTokenInterface $token)
    {
        $allowedScopes = $token->getScopes();

        $embedsToScopes = [
            'contacts.elements' => [
                'contacts',
                'email',
                'mobile',
            ],
            'addresses.elements' => [
                'contacts',
            ],
            'documents.elements' => [
                'id_doc',
                'medical_doc',
                'military_doc',
                'foreign_passport_doc',
                'drivers_licence_doc',
                'birth_cert_doc',
                'residence_doc',
                'temporary_residence_doc',
            ],
            'vehicles.elements' => [
                'vehicles',
            ],
            'organizations.elements' => [
                'usr_org',
            ],
        ];

        $allowedEmbeds = [];
        foreach ($embedsToScopes as $embed => $scopes) {
            if (count(array_intersect($allowedScopes, $scopes))) {
                $allowedEmbeds[] = $embed;
            }
        }

        return $allowedEmbeds;
    }

    private function getUrl($path)
    {
        return $this->remoteUrl.$path;
    }

    protected function getDefaultScopes()
    {
        return $this->defaultScopes;
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function getAccessTokenRequest(array $params)
    {
        $params = $params + [
            'scope' => 'openid',
            'state' => $this->getRandomState(),
            'timestamp' => $this->getTimeStamp(),
            'token_type' => 'Bearer',
        ];

        return parent::getAccessTokenRequest($this->withClientSecret($params));
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400 || isset($data['error'])) {
            throw new IdentityProviderException(isset($data['error']) ? $data['error'] : $response->getReasonPhrase(), $response->getStatusCode(), (string) $response->getBody());
        }
    }

    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new EsiaAccessToken($response, $this->remoteCertificatePath, $this->remoteSigner);
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $response = ['resourceOwnerId' => $token->getResourceOwnerId()] + $response;

        return new GenericResourceOwner($response, 'resourceOwnerId');
    }
}
