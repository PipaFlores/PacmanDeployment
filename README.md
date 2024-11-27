# Pacman Remote Server - AI Personality and Cognition (AiPerCog) Research Project

This repository contains a WebGL Pacman build for online deployment and PHP scripts that handle data transactions between the client and server. The client is built on [Unity](https://version.helsinki.fi/hipercog/behavlets/unity_pacman).
This project is part of the [AiPerCog](https://www.helsinki.fi/en/researchgroups/high-performance-cognition/research) research project. 

## Project Structure

### WebGL Build
- Browser-based Pacman game built with Unity (WebGL)
- Located in the root directory (excluding SQL Folder)

### Server-side Components
Located in the `SQL` directory:

#### Core Data Handling
- `savegamedata_json.php`: Handles gameplay data storage with transaction safety and duplicate prevention.
- `utils.php`: Utility functions for REDCap integration and data validation.
- `Flow.php`: Manages Flow State Scale survey data collection.

#### Database Integration
- MySQL database for storing:
  - Player gameplay data
  - Session information
  - Player account information (username, password with hashing, and email)
- REDCap integration for psychometric data collection

#### Data Handling
- Secure data handling with input validation
- Transaction-based game state storage
- Duplicate game prevention
- Integration with REDCap for psychological measurements
- Support for Flow State Scale and other survey tools

## Used Setup

1. LAMP Stack (Linux, Apache, MySQL, PHP)
2. REDCap server for psychometric data collection (hosted at University of Helsinki)
3. MySQL database schema (available upon request)

## Data Collection

The system collects various types of data:

1. **Gameplay Data**
   - Player positions and movements
   - Ghost positions and states
   - Score and game progress
   - Session information

2. **Psychometric Data**
   - Flow Short Scale responses
   - Other psychological measurements via REDCap (done through external link)