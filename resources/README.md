Keys
====

Release new
-----------

For GOST 2012 in the future use param `-newkey GOST2012_512`

    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Tester GOST"  -keyout ekapusta.gost.test.key -out ekapusta.gost.test.cer -newkey GOST2001 -pkeyopt paramset:A
    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Another GOST" -keyout another.gost.test.key  -out another.gost.test.cer  -newkey GOST2001 -pkeyopt paramset:A
    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Tester RSA"   -keyout ekapusta.rsa.test.key  -out ekapusta.rsa.test.cer  -newkey rsa:2048
    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Another RSA"  -keyout another.rsa.test.key   -out another.rsa.test.cer   -newkey rsa:2048

View
----

    openssl asn1parse -i -in ekapusta.gost.test.cer
    openssl asn1parse -i -in ekapusta.rsa.test.cer
