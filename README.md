# Laravel API Organizer
* [General info](#general-info)
* [Technologies](#technologies)
* [Setup](#setup)
* [Usage](#usage)
* [Configuration](#configurations)

## General info
<details>
  <summary>This is SPA Organizer API</summary>
This project includes a simple SPA API built with Laravel Sanctum.
</details>

## Technologies
### Most important technologies used in project:
* Laravel
* SQLite

## Setup
To setup project, follow these steps:
1. run `composer install` to install all packages
2. create _**.env**_ and **.env.test**_ file along the lines of **.env.example**
3. generate application key: `php artisan key:generate`
4. to run migrations: `php artisan migrate`
5. to run application: `php artisan serve`
* if you want to seed database use `php artisan db:seed` command

## Usage
All endpoints are located in the path `/api`. For this API is built [Vue Frontend here.](https://github.com/Mat01a/organizer-vue)

## Configurations
App defaultly uses port 8000, and for frontend is set up 5173 port. If any changes needed, change configurations in .env file. In order to change port change:

    FRONTEND_URL
    SANCTUM_STATEFUL_DOMAINS

In order to change **SESSION_DOMAIN** change, **FRONTEND_URL** and **SANCTUM_STATEFUL_DOMAINS** needs to be changed aswell.