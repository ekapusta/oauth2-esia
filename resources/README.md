Keys
====

Release new
-----------

    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Tester GOST"  -keyout ekapusta.gost.test.key -out ekapusta.gost.test.cer -newkey GOST2012_512 -pkeyopt paramset:A
    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Another GOST" -keyout another.gost.test.key  -out another.gost.test.cer  -newkey GOST2012_512 -pkeyopt paramset:A
    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Tester RSA"   -keyout ekapusta.rsa.test.key  -out ekapusta.rsa.test.cer  -newkey rsa:2048
    openssl req -x509 -outform pem -nodes -days 18250  -utf8 -subj "/C=RU/CN=Another RSA"  -keyout another.rsa.test.key   -out another.rsa.test.cer   -newkey rsa:2048

View
----

    openssl asn1parse -i -in ekapusta.gost.test.cer
    openssl asn1parse -i -in ekapusta.rsa.test.cer


Extract public keys from certs
------------------------------

    for CERT in *.cer; do openssl x509 -engine gost -noout -pubkey -in $CERT -out ${CERT%.*}.public.key; done


Update ESIA`a public keys
-------------------------

```
wget --no-check-certificate https://esia.gosuslugi.ru/public/esia.zip
unzip esia.zip
mv RSA_PROD.cer esia.prod.cer
mv RSA_TESIA.cer esia.test.cer
openssl x509 -inform der -in "ГОСТ 2012 ПРОД.cer" -out esia.gost.prod.cer
openssl x509 -inform der -in "ГОСТ ТЕСИА 2012.cer" -out esia.gost.test.cer
openssl x509 -engine gost -noout -pubkey -in esia.gost.prod.cer -out esia.gost.prod.public.key
openssl x509 -engine gost -noout -pubkey -in esia.gost.test.cer -out esia.gost.test.public.key
rm -f esia.zip RSA.txt ГОСТ*.cer *.crt
```