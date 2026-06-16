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
# If wget will fail -- open in browser as it can be bot-protected
wget --no-check-certificate https://esia.gosuslugi.ru/public/esia.zip
unzip esia.zip
rm -rf esia.zip esia RSA_PROD_2025_JWK.jwk
mv RSA_PROD_2026_NEW.cer esia.prod.cer
openssl x509 -inform der -in "ГОСТ_PROD_26_27.cer" -out esia.gost.prod.cer
docker run --rm -i -v $(pwd):$(pwd) -v /tmp/tmp -w $(pwd) rnix/openssl-gost openssl x509 -engine gost -noout -pubkey -in esia.gost.prod.cer -out esia.gost.prod.public.key
rm ГОСТ*.cer
mv RSA_TESIA_ACTUAL.cer esia.test.cer
openssl x509 -noout -pubkey -in esia.test.cer -out esia.test.public.key
openssl x509 -inform der -in "TESIA GOST 2026-2027 new.cer" -out esia.gost.test.cer
docker run --rm -i -v $(pwd):$(pwd) -v /tmp/tmp -w $(pwd) rnix/openssl-gost openssl x509 -engine gost -noout -pubkey -in esia.gost.test.cer -out esia.gost.test.public.key
rm TESIA*.cer
```

https://digital.gov.ru/documents/metodicheskie-rekomendaczii-po-ispolzovaniyu-esia
