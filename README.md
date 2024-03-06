
# Organizer

An app that helps to organise and track responsibilities. This API is built with **Laravel** and **Breeze API**.

  

## Setting up project

To install all dependencies in project you should run command

`compose install`

  

Next you should copy *.env.example* and and make *.env* file

  

### Integration tests

In order to run integration tests:

`php artisan test`

  
  

### Seeding database

To seed database use command:

`php artisan db:seed`

  
  

### Running server

In order to run app write:

`php artisan server`

  
  

## Configurations

App defaultly uses port **8000**, and for frontend is set up **5173** port. If any changes needed, change configurations in *.env* file.
In order to change port change:

 - FRONTEND_URL
 - SANCTUM_STATEFUL_DOMAINS

In order to change **SESSION_DOMAIN** change, **FRONTEND_URL** and **SANCTUM_STATEFUL_DOMAINS** needs to be chaned aswell.

