# Instrukcijos paleidimui

## Darbą atliko Vytautas Uoga

## ENV
Projekto kataloge yra .env.example failas.
Susikurti .env failą pagal jį.

## Projektas kurtas Docker aplinkoje.
docker-compose up -d --build
docker-compose exec php composer install

## Migracijų paleidimas (DB struktūros sukūrimas)
docker-compose exec php php bin/console doctrine:migrations:migrate

## Testų paleidimas (Unit ir functional testai)
docker-compose exec php php bin/phpunit

## API dokumentacija 
įrašytas nelmioapidoc bundle
http://localhost:8080/api/doc
galima matyti, kokie endpointai ir kaip testuoti. Testavimui naudojau Postman.
