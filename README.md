ESIA Provider for OAuth 2.0 Client
==================================

[![Build Status](https://travis-ci.org/ekapusta/oauth2-esia.svg?branch=develop)](https://travis-ci.org/ekapusta/oauth2-esia)
[![Coverage Status](https://coveralls.io/repos/github/ekapusta/oauth2-esia/badge.svg?branch=develop)](https://coveralls.io/github/ekapusta/oauth2-esia?branch=develop)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/ekapusta/oauth2-esia/blob/develop/LICENSE.md)
<a href="https://esia.gosuslugi.ru/"><img src="https://esia.gosuslugi.ru/idp/resources/img/flt/ru/logo-simple.png" height="16" /></a>


Allows to authenticate in ESIA and get authenticated individual personal information.

Implemented as adapter to the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).


Installing
----------

To install, use composer:

    composer require ekapusta/oauth2-esia

Usage
-----

Usage is the same as the normal client, using `Ekapusta\OAuth2Esia\Provider\EsiaProvider` as the provider:


### Configure provider

```php
use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Security\Signer\OpensslPkcs7;

$provider = new EsiaProvider([
    'clientId'      => 'XXXXXX',
    'redirectUri'   => 'https://your-system.domain/auth/finish/',
    'defaultScopes' => ['openid', 'fullname', '...'],
// For work with test portal version
//  'remoteUrl' => 'https://esia-portal1.test.gosuslugi.ru',
//  'remoteCertificatePath' => EsiaProvider::RESOURCES.'esia.test.cer',
], [
    'signer' => new OpensslPkcs7('/path/to/public/certificate.cer', '/path/to/private.key')
]);
```

### Which signer to use?

* If you use RSA keys, then `OpensslPkcs7` is enough.
* If you use GOST keys and compiled PHP with GOST ciphers, then `OpensslPkcs7` is enough.
* If you use GOST keys and have openssl-compatible tool, then use `OpensslCli`. It has `toolpath` param.
* If you use GOST keys and you are docker-addict, then you can use `'toolpath' => 'docker run --rm -v $(pwd):$(pwd) -w $(pwd) rnix/openssl-gost openssl'`.


### Auth flow

Auth flow is standard.

```php
// https://your-system.domain/auth/start/
$authUrl = $provider->getAuthorizationUrl();
$_SESSION['oauth2.esia.state'] = $provider->getState();
header('Location: '.$authUrl);
exit;

// https://your-system.domain/auth/finish/?state=...&code=...
if ($_SESSION['oauth2.esia.state'] !== $_GET['state']) {
    exit('The guard unravels the crossword.');
}

$token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
$esiaPersonData = $provider->getResourceOwner($accessToken);
var_export($esiaPersonData->toArray());
```

### Simplified facade

If you don't like classes with about 20 public methods, there is simplified facade-class.

```php
use Ekapusta\OAuth2Esia\EsiaService;

$service = new EsiaService($provider);

// https://your-system.domain/auth/start/
$_SESSION['oauth2.esia.state'] = $service->generateState();
$authUrl = $service->getAuthorizationUrl($_SESSION['oauth2.esia.state']);
header('Location: '.$authUrl);
exit;

// https://your-system.domain/auth/finish/?state=...&code=...
$esiaPersonData = $service->getResourceOwner($_SESSION['oauth2.esia.state'], $_GET['state'], $_GET['code'])
var_export($esiaPersonData->toArray());
```

Example $esiaPersonData
-----------------------

```json
{
  "stateFacts": [
    "EntityRoot"
  ],
  "eTag": "61F2A6BF9D17B97E6B56F8B10EB28A7C814FF0B4",
  "firstName": "Имя006",
  "lastName": "Фамилия006",
  "middleName": "Отчество006",
  "birthDate": "26.05.2000",
  "birthPlace": "Москва",
  "gender": "F",
  "trusted": true,
  "citizenship": "RUS",
  "snils": "000-000-600 06",
  "inn": "585204118212",
  "updatedOn": 1523386683,
  "contacts": {
    "stateFacts": [
      "hasSize"
    ],
    "size": 3,
    "eTag": "5F535ACCAEB3018D0AAA8C46027E3CF2C4BD0197",
    "elements": [
      {
        "stateFacts": [
          "Identifiable"
        ],
        "eTag": "F3AA3B18B35BC12E53E0B7A7EAF13EC41EBD02AD",
        "id": 14244504,
        "type": "MBT",
        "vrfStu": "VERIFIED",
        "vrfValStu": "VERIFYING",
        "value": "+7(000)0000006",
        "verifyingValue": "+7(111)1111111",
        "isCfmCodeExpired": true
      },
      {
        "stateFacts": [
          "Identifiable"
        ],
        "eTag": "943C1145E4973324599CD0E4FF136186502C93C5",
        "id": 14249750,
        "type": "PHN",
        "vrfStu": "NOT_VERIFIED",
        "value": "+7(840)0000006"
      },
      {
        "stateFacts": [
          "Identifiable"
        ],
        "eTag": "17DCA3945F1B8B54496F59EB146BDC7DADAD7BC8",
        "id": 14216773,
        "type": "EML",
        "vrfStu": "VERIFIED",
        "vrfValStu": "VERIFYING",
        "value": "EsiaTest006@yandex.ru",
        "verifyingValue": "EsiaTest006@yandex.ru",
        "isCfmCodeExpired": true
      }
    ]
  },
  "addresses": {
    "stateFacts": [
      "hasSize"
    ],
    "size": 2,
    "eTag": "47B43F0210344E272F338073C382C5955651C5E2",
    "elements": [
      {
        "stateFacts": [
          "Identifiable"
        ],
        "eTag": "C90BE244DC0650255C9D3078C7C7EDEA8013BB6E",
        "id": 15893,
        "type": "PRG",
        "region": "Чувашская Республика",
        "zipCode": "428022",
        "addressStr": "г Чебоксары, пр-кт Мира",
        "fiasCode": "bb5f4fab-64ea-4042-a61b-9b2bdb55442d",
        "city": "Чебоксары",
        "countryId": "RUS",
        "street": "Мира",
        "house": "1",
        "flat": "1"
      },
      {
        "stateFacts": [
          "Identifiable"
        ],
        "eTag": "3553085EBBC08CEBFD73957B7D5BAFDFDA096CCA",
        "id": 530,
        "type": "PLV",
        "region": "Чувашская Республика",
        "zipCode": "428022",
        "addressStr": "г Чебоксары, пр-кт Мира",
        "fiasCode": "bb5f4fab-64ea-4042-a61b-9b2bdb55442d",
        "city": "Чебоксары",
        "countryId": "RUS",
        "street": "Мира",
        "house": "1",
        "flat": "1"
      }
    ]
  },
  "documents": {
    "stateFacts": [
      "hasSize"
    ],
    "size": 2,
    "eTag": "E752C6CFC8CBAE112527BF2AA07CB0A173143065",
    "elements": [
      {
        "stateFacts": [
          "EntityRoot"
        ],
        "eTag": "2E1F79E93B9DF6F5A579F95069630742D41C6AFB",
        "id": 3571,
        "type": "RF_PASSPORT",
        "vrfStu": "VERIFIED",
        "series": "5303",
        "number": "925695",
        "issueDate": "01.01.2006",
        "issueId": "006006",
        "issuedBy": "УФМС006"
      },
      {
        "stateFacts": [
          "EntityRoot"
        ],
        "eTag": "E9D14F10321D0021A1267B8D363B22B102387735",
        "id": 21213,
        "type": "RF_DRIVING_LICENSE",
        "vrfStu": "NOT_VERIFIED",
        "series": "1222",
        "number": "884455",
        "issueDate": "01.09.2014",
        "expiryDate": "01.08.2024"
      }
    ]
  },
  "vehicles": {
    "stateFacts": [
      "hasSize"
    ],
    "size": 1,
    "eTag": "9D0855F880F882EBCFD93C329C4720D5DB4058D9",
    "elements": [
      {
        "stateFacts": [
          "Identifiable"
        ],
        "eTag": "A99823275D311CB97A371A420A59AA6BB08B42B7",
        "id": 17743,
        "name": "Моя птичка",
        "numberPlate": "А123АА111",
        "regCertificate": {
          "series": "1231",
          "number": "231231"
        }
      }
    ]
  },
  "status": "REGISTERED",
  "verifying": false,
  "rIdDoc": 3571,
  "containsUpCfmCode": false
}
```


Testing
-------

Node is used for interactive headless chrome auth bot.

```bash
vendor/bin/phpunit --debug
```


About ESIA
----------

There are three ESIA user identification levels:

 * simple
 * standard
 * confrimed

Information system can ask info about user from individuals register.


ESIA user could be:

 * individual
 * individual entrepreneur (individual + flag "is entrepreneur")
 * individual connected to legal entities accounts
 * individual connected to public authorities accounts


Users after individual can be only of confirmed identification level.


User info
---------

After user's permission his/her info can be read through REST.

Scopes
------

To get some info about user system should ask it through "scope" param.
Same param entered in paper-written application for connection to ESIA.

Scope is analog of permissions in mobile apps, but for user's data.

Here are list of possible scopes: fullname, birthdate, gender, snils, inn, id\_doc,
birthplace, medical\_doc, military\_doc, foreign\_passport\_doc, drivers\_licence\_doc,
vehicles, email, mobile, contacts, kid\_fullname.


Security algos
--------------

ESIA REST supports both RSA2048+SHA256 and GOST3410-2001+GOST341194 algos.


Authentication methods
----------------------

There are two ways to authenticate user: SAML 2.0 and OpenID Connect 1.0 (OAuth 2.0 extension).
SAML 2.0 is only for public authorities.

For legal entities OpenID Connect is used.


Terms
-----

ESIA from Russian "ЕСИА", which is "Единая система идентификации и аутентификации".
Translated as "Unified identification and authentication system".

Links
-----

 1. [Единая система идентификации и аутентификации](https://ru.wikipedia.org/wiki/%D0%95%D0%B4%D0%B8%D0%BD%D0%B0%D1%8F_%D1%81%D0%B8%D1%81%D1%82%D0%B5%D0%BC%D0%B0_%D0%B8%D0%B4%D0%B5%D0%BD%D1%82%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%86%D0%B8%D0%B8_%D0%B8_%D0%B0%D1%83%D1%82%D0%B5%D0%BD%D1%82%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%86%D0%B8%D0%B8)
2. [Methodical recommendations](http://minsvyaz.ru/ru/documents/?type=50&directions=13)
