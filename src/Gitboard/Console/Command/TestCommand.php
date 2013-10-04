<?php

namespace Gitboard\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommand extends Command
{
    protected $target;

    protected $branch;


    protected function configure()
    {
        $this
            ->setName('gitboard')
            ->setDescription('Say hello')
        ;

        //$this->target = $_SERVER['PWD'];
        $this->target = getcwd();
        $this->branch = $this->getCurrentBranch();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TABLE
        // @see http://symfony.com/doc/current/components/console/helpers/tablehelper.html

        $output->writeln($this->target . '\.git');


        // TODO add colspan/rowspan to table helper
        // @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Helper/TableHelper.php
        

        $table
            /** defaults to TableHelper::LAYOUT_DEFAULT , LAYOUT_COMPACT */
            /** @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Helper/TableHelper.php#L24 */
            ->setLayout($table::LAYOUT_COMPACT)
            ->setHeaders(array('Hello World !'))
            ->setRows(array(
                array('Hello', 'World', '!'),
            ))
            ->setRows(array(
                array('Hello', 'World', '!'),
            ))
        ;
        $table->render($output);

        $table->setHeaders(array())->setRows(array());

        $table
            ->setLayout($table::LAYOUT_BORDERLESS)
            ->setRows(array(
                array('Hello', 'World', '!'),
            ))
        ;
        $table->render($output);

        $table->setHeaders(array())->setRows(array());


        // FORMATTER / SECTION
        // @see http://symfony.com/doc/current/components/console/helpers/formatterhelper.html
        $formatter = $this->getHelperSet()->get('formatter');
        $formattedLine = $formatter->formatSection(
            'SomeSection',
            'Here is some message related to that section'
        );
        $output->writeln($formattedLine);

        // FORMATTER / BLOCK
        $errorMessages = array('Error!', 'Something went wrong');
        // message, type, boolean extra spacing top/sides
        // info, comment, question, error
        $formattedBlock = $formatter->formatBlock($errorMessages, 'question', true);
        $output->writeln($formattedBlock);


        $terminalDimensions = $this->getApplication()->getTerminalDimensions();

        $block = '';
        for ($i = 1; $i <= ($terminalDimensions[0]-6); $i++) {
            $block = $block . '_';
        }

        $output->writeln($block);

        $formattedBlock = $formatter->formatBlock($block, 'question', true);
        $output->writeln($formattedBlock);

        // COLORs:
        // black, red, green, yellow, blue, magenta, cyan and white.
        // OPTIONS: 
        // bold, underscore, blink, reverse and conceal
        $output->writeln('<bg=yellow;options=reverse>foo</bg=yellow;options=reverse>');

        // PROGRESSHELPER
        // @see http://symfony.com/doc/current/components/console/helpers/progresshelper.html

        $output->writeln('');
        $output->writeln('');
        $output->writeln('');

        $formater = $this->getHelperSet()->get('formatter');
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

    }

    protected function getCurrentBranch()
    {
        // doesn't matter if / or \.git on cygwin
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
        passthru("tput clear");
    }
}