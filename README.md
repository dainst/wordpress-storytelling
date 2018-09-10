# Wordpress Storytelling

## Prerequisites

* [Docker](https://www.docker.com/)
* [docker-compose](https://docs.docker.com/compose/)

## Running the wordpress with the storytelling plugin

1. `cp .env_template .env`, adjust the database password as necessary.
2. Run docker-compose: `docker-compose up`. A fresh Wordpress instance should be available at 
[localhost:81](localhost:81). Using the Dashboard, you can activate the plugin. The plugin's code found under src/ in 
this repository is automatically mounted into the Wordpress container - meaning you can edit the plugin code
on your host machine and the changes are directly visible in wordpress.