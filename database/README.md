# nba2019

This database is a sample database of NBA stats from the 2018-19 season. It includes player & team data from a subset of teams - mainly ones that made the semifinal round of the playoffs.

There are 3 tables by default:

	* Team data
	* Player / roster data
	* Player statistics

The database is intentionally inefficiently designed as part of the refactoring test. It is missing indexes, and should ideally be structured better. Optimising this is part of some of the more advanced tests.

# Installation

Simply import the sql file into mysql:

```bash
cd database
mysql -u root -h 127.0.0.1 < nba2019.sql
```

This will create a database called `nba2019` & import all data.

# Source
Data exported from [basketball-reference.com](https://www.basketball-reference.com/).