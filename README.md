Simple dashboard for a quick overview of Git projects.

# Usage

In a git repository :

    php gitboard.phar

# Options

`-d` <project directory> : like --git-dir  
`-i` : number of last days/hours/minutes  
`-c` : number of last commits  
`-h` : help  
`-v` : version  
`--no-merged-branch` : no merged branches infos  
`--no-stat` : no statistic  
`--display-web` : convert ansi output to html

# Example

### Console

    php gitboard.phar -d=../node --no-merged-branch

![Gitboard console](https://lh5.googleusercontent.com/-A2ZveUUbwCc/Tn3MwQDyzDI/AAAAAAAAAuc/ynkxbkdjyzs/s640/Gitboard.png "Gitboard console example")

### HTML

    php gitboard.phar -d=../node --display-web > gitboard.html

![Gitboard HTML](https://f.cloud.github.com/assets/1763887/1129153/0e143458-1b83-11e3-94c5-e5ab75500004.png "Gitboard HTML example")


# Alias (git config file)

    [alias]  
      board = "!f() { php /path/to/gitboard/gitboard.phar $@; }; f"
