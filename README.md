Gitboard
--------

Simple git dashboard.

Usage
-----

php gitboard.php

Options
-------

-d <project directory> : like --git-dir  
-i : number of last days/hours/minutes  
-c : number of last commits  
-h : help  
-v : version  
--no-stats : no statistic  

Example
-------

> php gitboard.php -d=../node

![Gitboard](https://lh5.googleusercontent.com/-A2ZveUUbwCc/Tn3MwQDyzDI/AAAAAAAAAuc/ynkxbkdjyzs/s640/Gitboard.png "Gitboard example")

Alias (git config file)
-----------------------

> [alias]  
> board = "!sh -c 'php /path/to/gitboard/gitboard.php'"