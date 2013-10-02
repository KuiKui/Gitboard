<?php

namespace Gitboard\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommand extends Command
{
    protected $target;

    protected $branch;

    protected $commits;


    protected function configure()
    {
        $this
            ->setName('gitboard')
            ->setDescription('Say hello')
        ;

        //$this->target = $_SERVER['PWD'];
        $this->target = getcwd();
        $this->branch = $this->getCurrentBranch();
        // TODO make configurable
        $this->commits = $this->getCommits(40);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$output->writeln($this->target . '\.git');
        $this->clearScreen();

        $formatter = $this->getHelperSet()->get('formatter');
        $table = $this->getHelperSet()->get('table');

        $table
            /** defaults to TableHelper::LAYOUT_DEFAULT , LAYOUT_COMPACT */
            /** @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Helper/TableHelper.php#L24 */
            ->setLayout($table::LAYOUT_COMPACT)
            ->addRow(array(
                // todo simple format
                $formatter->formatBlock('project', 'info'),
                $this->target,
            ))
            ->addRow(array(
                $formatter->formatBlock('current branch', 'info'),
                $this->branch,
            ))
            ->addRow(array(
                $formatter->formatBlock('current date', 'info'),
                date('d/m/Y H:i:s'),
            ))
        ;
        $table->render($output);

        $table->setRows(array());
        $output->writeln('');
        $output->writeln('');

        $table->setHeaders(array(
            'date','name','hash','message','files'
        ));

        for($i = 0; $i < count($this->commits); $i++)
        {
            if(!isset($this->commits[$i])) continue;
            /*
            displayValue($converter, date('d/m/y H\hi', strtotime($commits[$i]['date'])), 17, "0;33", false, date('d/m/y'));
            displayValue($converter, limitText($commits[$i]['name'], 16), 17);
            displayValue($converter, $commits[$i]['hash'], 8);
            displayValue($converter, limitText($commits[$i]['message'], 70), 71, "0;36");
            displayValue($converter, count($commits[$i]['files']), 9);
            outputf($converter, "\n");
            */
            $date = date('d/m/y H\hi', strtotime($this->commits[$i]['date']));
            // @nico Important - strip non-ansii characters
            $name = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $this->commits[$i]['name']);
            $hash = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $this->commits[$i]['hash']);
            $message = substr($this->commits[$i]['message'],0,6);
            $files = count($this->commits[$i]['files']);

            $table->addRow(array(
                $date,$name,$hash,$message,$files,
            ));

        }

        $table->render($output);

    }

    // TODO move to provider
    protected function getCurrentBranch()
    {
        // doesn't matter if / or \.git on cygwin
        // TODO replace with git command
        $cmd = sprintf("git --git-dir=%s\.git branch | grep \* | sed 's/* //g'", $this->target);
        exec($cmd, $branch);
        if(count($branch)==0)
        {
            exit('No branch selected in ' . $this->target);
        }
        return $branch[0];
    }

    protected function clearScreen()
    {
        //passthru("tput clear");
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // TODO warn on verbose that clearing screen is not supported
            // TODO check for cygwin/msys
        } 
        passthru("tput clear");
        // ncurses_clear() ?
    }

    // TODO move to provider
    protected function getCommits($nbDays)
    {
        $separator = '°';
        $from = date('Y-m-d 00:00:00', strtotime(sprintf("-%s days", $nbDays - 1)));
        // TODO replace with git command
        $cmd = sprintf('git --git-dir=%s/.git log --no-merges --ignore-all-space --since="%s" --format="%%ci%s%%ce%s%%cn%s%%h%s%%s" --numstat', $this->target, $from, $separator, $separator, $separator, $separator);
        exec($cmd, $results);

        $commits = array();
        $commit = array();
        foreach($results as $line)
        {
            if(strlen($line) == 0)
            {
                continue;
            }
            if(strpos($line, $separator) !== false)
            {
                if(count($commit) > 0)
                {
                    $commits[] = $commit;
                }
                $commit = $this->getCommitFromLine($line, $separator);
            }
            else
            {
                $elements = preg_split("/[\s]+/", $line, null, PREG_SPLIT_NO_EMPTY);
                $commit['files'][] = array(
                    'add' => $elements[0],
                    'delete' => $elements[1],
                    'file' => $elements[2],
                );
            }
        }

        if(count($commit) > 0)
        {
            $commits[] = $commit;
        }

        return $commits;
    }

    protected function getCommitFromLine($line, $separator)
    {
        $elements = explode($separator, $line);
        $commit = array(
            'date' => date('Y-m-d H:i:s', strtotime($elements[0])),
            'email' => $elements[1],
            'name' => $elements[2],
            'hash' => $elements[3],
            'message' => $elements[4],
            'files' => array()
        );

        return $commit;
    }
}