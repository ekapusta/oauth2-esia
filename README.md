ESIA client
===========

Allows to authenticate in ESIA and get authenticated individual personal information.


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


Users after individual can be only of confirmed identication level.


User info
---------

After user's permission his/her ifno can be read through REST.

Scopes
------

To get some info about user system should ask it through "scope" param.
Same param entered in paper-written application for connection to ESIA.

Scope is analog of permissions in mobile apps, but for user's data.

Here are possible list of scopes: fullname, birthdate, gender, snils, inn, id\_doc,
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
Translated as "Unified authentication and authentication system".

Links
-----

 1. [Единая система идентификации и аутентификации](https://ru.wikipedia.org/wiki/%D0%95%D0%B4%D0%B8%D0%BD%D0%B0%D1%8F_%D1%81%D0%B8%D1%81%D1%82%D0%B5%D0%BC%D0%B0_%D0%B8%D0%B4%D0%B5%D0%BD%D1%82%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%86%D0%B8%D0%B8_%D0%B8_%D0%B0%D1%83%D1%82%D0%B5%D0%BD%D1%82%D0%B8%D1%84%D0%B8%D0%BA%D0%B0%D1%86%D0%B8%D0%B8)
2. [Methodical recommendations](http://minsvyaz.ru/ru/documents/?type=50&directions=13)
