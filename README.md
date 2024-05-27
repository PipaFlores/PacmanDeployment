# Pacman Remote Server - AI Personality and Cognition (AiPerCog) Research Project

This repo contains the WebGL Pacman build to be deployed online and php scripts handling the data transactions between the client and the server. 

## Build

Pacman is built on [Unity](https://version.helsinki.fi/hipercog/behavlets/unity_pacman), with WebGL for browser-based deployment. The build consist of all files, with the exceptions of the "SQL" and "OldScripts" folders.

## Data handling

SQL folder contains the php scripts handling register, login and gameplay data. They work with a MySQL database (TODO: Include reference to database structure)

## Work to be done

- Synchronize user_ids with Redcap API's record_id generation for psychometric tools.