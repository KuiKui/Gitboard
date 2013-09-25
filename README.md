Simple dashboard for a quick overview of Git projects.

Usage
-----

php gitboard.phar

Options
-------

`-d` <project directory> : like --git-dir  
`-i` : number of last days/hours/minutes  
`-c` : number of last commits  
`-h` : help  
`-v` : version  
`--no-merged-branch` : no merged branches infos  
`--no-stat` : no statistic
`--display-web` : convert ansi output to html

Example
-------

    php gitboard.phar -d=../node --no-merged-branch
    php gitboard.phar -d=../node --display-web > gitboard.html

![Gitboard](https://lh5.googleusercontent.com/-A2ZveUUbwCc/Tn3MwQDyzDI/AAAAAAAAAuc/ynkxbkdjyzs/s640/Gitboard.png "Gitboard example")

Alias (git config file)
-----------------------

    [alias]  
      board = "!f() { php /path/to/gitboard/gitboard.php $@; }; f"
