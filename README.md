ЕСИА провайдер для OAuth 2.0 Client
===================================

Позволяет аутентифицироваться в ЕСИА и получать персональную информацию аутентифицированного лица.

Сделано как адаптер к PHP League [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

Поддерживаются версии PHP от 5.6 до 8.5.

Покрытие кода юнит-тестами: 100%.

Установка
---------

    composer require ekapusta/oauth2-esia

Использование
-------------

Использование аналогично обычному OAuth 2.0 Client с провайдером `Ekapusta\OAuth2Esia\Provider\EsiaProvider`:


### Конфигурация провайдера

```php
use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Security\JWTSigner\OpenSslCliJwtSigner;
use Ekapusta\OAuth2Esia\Security\Signer\OpensslPkcs7;

$provider = new EsiaProvider([
    'clientId'      => 'XXXXXX',
    'redirectUri'   => 'https://your-system.domain/auth/finish/',
    'defaultScopes' => ['openid', 'fullname', '...'],
    // Для работы с тестовым порталом
    // 'remoteUrl' => 'https://esia-portal1.test.gosuslugi.ru',
    // 'remotePublicKey' => EsiaProvider::RESOURCES.'esia.test.public.key',
    // Для работы с GOST3410_2012_256 подписями (другие уже не поддерживаются порталами Госуслуг)
    'remoteCertificatePath' => EsiaProvider::RESOURCES.'esia.gost.prod.public.key',
], [
    'signer' => new OpensslPkcs7('/path/to/public/certificate.cer', '/path/to/private.key'),
    // Для работы с GOST3410_2012_256 подписями (другие уже не поддерживаются порталами Госуслуг)
    'remoteSigner' => new OpenSslCliJwtSigner('/path/to/openssl'),
]);
```

### Какой из подписателей (signer) использовать?

* RSA ключи уже не поддерживаются.
* Если вы используете PHP с прекомпилированными в openssl GOST алгоритмами, то `OpensslPkcs7` достаточно.
* Если у вас есть openssl-совместимая утилита, то можно использовать `OpensslCli`. У неё есть `toolpath` параметр.
* Если у вас есть утилита, не совместимая с openssl, то можно по образцу `OpensslCli` сделать свою.
* Для целей тестирования используется docker с параметром `'toolpath' => 'docker run --rm -i -v $(pwd):$(pwd) -w $(pwd) rnix/openssl-gost openssl'`.


## Какой из удалённый подписывателей (remote signer) использовать?

* Для подписей GOST3410_2012_256, а они только одни и остались, используйте `OpenSslCliJwtSigner`, передавая ей путь к `openssl`. Для докера используйте `docker run --rm -i -v $(pwd):$(pwd) -v /tmp/tmp -w $(pwd) rnix/openssl-gost openssl'`. `/tmp ` volume важен!
* Аналогично, если у вас есть свои утилиты, то можете сделать аналог `OpenSslCliJwtSigner` со своими деталями имплементации.

### Схема аутентификации

Стандартна.

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

$accessToken = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
$esiaPersonData = $provider->getResourceOwner($accessToken);
var_export($esiaPersonData->toArray());
```


Пример $esiaPersonData
----------------------

```json
{
  "resourceOwnerId": 1000404446,
  "stateFacts": [
    "EntityRoot"
  ],
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
        "id": 14216773,
        "type": "EML",
        "vrfStu": "VERIFIED",
        "value": "EsiaTest006@yandex.ru",
        "verifyingValue": "EsiaTest006@yandex.ru",
        "vrfValStu": "VERIFYING",
        "isCfmCodeExpired": true,
        "eTag": "17DCA3945F1B8B54496F59EB146BDC7DADAD7BC8"
      },
      {
        "stateFacts": [
          "Identifiable"
        ],
        "id": 14249750,
        "type": "PHN",
        "vrfStu": "NOT_VERIFIED",
        "value": "+7(840)0000006",
        "eTag": "943C1145E4973324599CD0E4FF136186502C93C5"
      },
      {
        "stateFacts": [
          "Identifiable"
        ],
        "id": 14244504,
        "type": "MBT",
        "vrfStu": "VERIFIED",
        "value": "+7(000)0000006",
        "verifyingValue": "+7(111)1111111",
        "vrfValStu": "VERIFYING",
        "isCfmCodeExpired": true,
        "eTag": "F3AA3B18B35BC12E53E0B7A7EAF13EC41EBD02AD"
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
        "id": 530,
        "type": "PLV",
        "addressStr": "г Чебоксары, пр-кт Мира",
        "fiasCode": "bb5f4fab-64ea-4042-a61b-9b2bdb55442d",
        "flat": "1",
        "countryId": "RUS",
        "house": "1",
        "zipCode": "428022",
        "city": "Чебоксары",
        "street": "Мира",
        "region": "Чувашская Республика",
        "eTag": "3553085EBBC08CEBFD73957B7D5BAFDFDA096CCA"
      },
      {
        "stateFacts": [
          "Identifiable"
        ],
        "id": 15893,
        "type": "PRG",
        "addressStr": "г Чебоксары, пр-кт Мира",
        "fiasCode": "bb5f4fab-64ea-4042-a61b-9b2bdb55442d",
        "flat": "1",
        "countryId": "RUS",
        "house": "1",
        "zipCode": "428022",
        "city": "Чебоксары",
        "street": "Мира",
        "region": "Чувашская Республика",
        "eTag": "C90BE244DC0650255C9D3078C7C7EDEA8013BB6E"
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
        "id": 3571,
        "type": "RF_PASSPORT",
        "vrfStu": "VERIFIED",
        "series": "5303",
        "number": "925695",
        "issueDate": "01.01.2006",
        "issueId": "006006",
        "issuedBy": "УФМС006",
        "eTag": "2E1F79E93B9DF6F5A579F95069630742D41C6AFB"
      },
      {
        "stateFacts": [
          "EntityRoot"
        ],
        "id": 21213,
        "type": "RF_DRIVING_LICENSE",
        "vrfStu": "NOT_VERIFIED",
        "series": "1222",
        "number": "884455",
        "issueDate": "01.09.2014",
        "expiryDate": "01.08.2024",
        "eTag": "E9D14F10321D0021A1267B8D363B22B102387735"
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
        "id": 17743,
        "name": "Моя птичка",
        "numberPlate": "А123АА111",
        "regCertificate": {
          "series": "1231",
          "number": "231231"
        },
        "eTag": "A99823275D311CB97A371A420A59AA6BB08B42B7"
      }
    ]
  },
  "status": "REGISTERED",
  "verifying": false,
  "rIdDoc": 3571,
  "containsUpCfmCode": false,
  "eTag": "61F2A6BF9D17B97E6B56F8B10EB28A7C814FF0B4"
}
```


Тестирование
------------

Node используется для интерактивного логина на тестовый стенд (бот на базе chromium -- puppeteer)

```bash
vendor/bin/simple-phpunit --debug
```

Ссылки
------

 1. [Единая система идентификации и аутентификации](https://ru.wikipedia.org/wiki/%D0%95%D0%B4%D0%B8%D0%BD%D0%B0%D1%8F_%D1%81%D0%B8%D1%81%D1%82%D0%B5%D0%BC%D0%B0_%D0%B8%D0%B4%D0%B5%D0%BD%D1%82%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%86%D0%B8%D0%B8_%D0%B8_%D0%B0%D1%83%D1%82%D0%B5%D0%BD%D1%82%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%86%D0%B8%D0%B8)
2. [Methodical recommendations](https://digital.gov.ru/documents/metodicheskie-rekomendaczii-po-ispolzovaniyu-esia)
