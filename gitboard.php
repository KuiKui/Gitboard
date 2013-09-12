#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
//---------------
// Default values
//---------------
$version = '0.1';
$gitDir = $_SERVER["PWD"];
$iteration = 15;
$nbCommits = 10;
$displayNoMergedBranches = true;
$displayStats = true;
$displayWeb = false;

//--------
// Options
//--------
$options = pflow_getopt( $argv, 'd:i:c:h:v:no-stat:no-merged-branch:display-web');
if(isset($options['h'])) {usage(); exit(); }
if(isset($options['v'])) {outputf("%s\n", $version); exit(); }
if(isset($options['d']) && $options['d']!==false) {$gitDir = $options['d'];}
if(isset($options['i']) && $options['i']!==false && is_numeric($options['i'])) {$iteration = $options['i'];}
if(isset($options['c']) && $options['c']!==false && is_numeric($options['c'])) {$nbCommits = $options['c'];}
if(isset($options['no-merged-branch'])) {$displayNoMergedBranches = false;}
if(isset($options['no-stat'])) {$displayStats = false;}
if(isset($options['display-web'])) {$displayWeb = true;}

//--------
// Compute
//--------
$currentBranch = getCurrentBranch($gitDir);
$commits = getCommits($iteration, $gitDir);
$lastDaysInfos = getBackwardInfos($commits, 'd/m', 'Y-m-d', 'days', $iteration);
$lastHoursInfos = getBackwardInfos($commits, 'H\h', 'Y-m-d H:', 'hours', $iteration);
$lastMinutesInfos = getBackwardInfos($commits, 'H\hi', 'Y-m-d H:i:', 'minutes', $iteration);
$noMergedBranchesInfos = ($displayNoMergedBranches) ? getNoMergedBranchesInfos($gitDir) : array();
$stats = ($displayStats) ? getStats($commits) : array();

//--------
// Display
//--------
if ($displayWeb) {
    global $converter;
    require_once __DIR__.'/vendor/autoload.php';
    $converter = new AnsiToHtmlConverter();

    echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head></head><body><pre style="background-color: black; overflow: auto; padding: 10px 15px; font-family: monospace;color:#fff">';
} else {
    passthru("tput clear");
}


// Display project infos
outputf("\033[0;32mProject\033[0m: %s\n", $gitDir);
outputf("\033[0;32mCurrent branch\033[0m: %s\n", $currentBranch);
outputf("\033[0;32mCurrent date\033[0m: %s\n", date('d/m/Y H:i:s'));
outputf("\n");

// Display time-report
outputf("\033[47;30m%-23s\033[0m%-12s\033[47;30m%-23s\033[0m%-12s\033[47;30m%-23s\033[0m\n", "Last $iteration days", "", "Last $iteration hours", "", "Last $iteration minutes");
outputf("%-8s %-8s %-16s %-8s %-8s %-16s %-8s %-8s %s\n", "Date", "Commits", "Files", "Hour", "Commits", "Files", "Hour", "Commits", "Files");

for($i = 0; $i < $iteration; $i++)
{
    displayValue($lastDaysInfos[$i]['displayDate'], 9);
    displayValue($lastDaysInfos[$i]['nb-commits'], 9, "0;33", true);
    displayValue($lastDaysInfos[$i]['nb-files'], 17, "0;33", true);
    displayValue($lastHoursInfos[$i]['displayDate'], 9);
    displayValue($lastHoursInfos[$i]['nb-commits'], 9, "0;33", true);
    displayValue($lastHoursInfos[$i]['nb-files'], 17, "0;33", true);
    displayValue($lastMinutesInfos[$i]['displayDate'], 9);
    displayValue($lastMinutesInfos[$i]['nb-commits'], 9, "0;33", true);
    displayValue($lastMinutesInfos[$i]['nb-files'], 0, "0;33", true);
    outputf("\n");
}
outputf("\n");

// Display commit-report
outputf("\033[47;30m%-113s%-9s\033[0m\n", "Last $nbCommits commits (within the last $iteration days)", "Files");
for($i = 0; $i < $nbCommits; $i++)
{
    if(!isset($commits[$i])) continue;
    displayValue(date('d/m/y H\hi', strtotime($commits[$i]['date'])), 17, "0;33", false, date('d/m/y'));
    displayValue(limitText($commits[$i]['name'], 16), 17);
    displayValue($commits[$i]['hash'], 8);
    displayValue(limitText($commits[$i]['message'], 70), 71, "0;36");
    displayValue(count($commits[$i]['files']), 9);
    outputf("\n");
}
outputf("\n");

// Display no merger branches infos
if(count($noMergedBranchesInfos) > 0)
{
    outputf("\033[47;30m%-20s %-15s %-85s\033[0m\n", "No merged branches", "Ahead", "Behind");
    outputf("%-20s %-7s %-7s %-7s %-7s %s\n", "", "Commits", "Files", "Commits", "Files", "Last commit");

    foreach($noMergedBranchesInfos as $name => $noMergedBranche)
    {
        displayValue(limitText($name, 20), 21);
        displayValue($noMergedBranche['distantBranchAheadCommits'], 8, "0;33", true);
        displayValue($noMergedBranche['distantBranchAheadFiles'], 8, "0;33", true);
        displayValue($noMergedBranche['currentBranchAheadCommits'], 8, "0;33", true);
        displayValue($noMergedBranche['currentBranchAheadFiles'], 8, "0;33", true);
        if(isset($noMergedBranche['distantBranchAheadLastCommit']))
        {
            displayValue(date('d/m/y H\hi', strtotime($noMergedBranche['distantBranchAheadLastCommit']['date'])), 17, "0;33", false, date('d/m/y'));
            displayValue(limitText($noMergedBranche['distantBranchAheadLastCommit']['name'], 16), 17);
            displayValue($noMergedBranche['distantBranchAheadLastCommit']['hash'], 8);
            displayValue(limitText($noMergedBranche['distantBranchAheadLastCommit']['message'], 27), 27, "0;36");
        }
        outputf("\n");
    }
    outputf("\n");
}

// Display stats infos
if(count($stats))
{
    outputf("\033[47;30m%-122s\033[0m\n", "Stats for the last ".count($commits)." commits (within the last $iteration days)");
    outputf("%-20s %-20s %-20s %s\n", "", "Commits", "Files", "");
    foreach($stats as $committer => $stat)
    {
        displayValue(limitText($stat['name'], 20), 21);
        displayValue($stat['totalCommits'], 8, "0;33");
        displayValue($stat['percentCommits'].'%', 13);
        displayValue($stat['totalFiles'], 8, "0;33");
        displayValue($stat['percentFiles'].'%', 13);
        displayValue(limitText($committer, 39), 40);
        outputf("\n");
    }
    outputf("\n");
}
if ($displayWeb) {
    echo '</pre></body></html>';
}
//----------
// Functions
//----------
function outputf()
{
    global $displayWeb;

    $args = func_get_args();

    if ($displayWeb) {
        global $converter;
        echo $converter->convert(call_user_func_array("sprintf",$args));

    } else {
        call_user_func_array("printf",$args);
    }
}
function output($str)
{
    global $displayWeb;

    if ($displayWeb) {
        global $converter;
        echo $converter->convert($str);
    } else {
        echo $str;
    }
}
function getCurrentBranch($gitDir)
{
    $cmd = sprintf("git --git-dir='%s/.git' branch | grep \* | sed 's/* //g'", $gitDir);
    exec($cmd, $branch);
    if(count($branch)==0)
    {
        exit('No branch selected in '.$gitDir);
    }
    return $branch[0];
}

function getCommits($nbDays, $gitDir)
{
    $separator = '°';
    $from = date('Y-m-d 00:00:00', strtotime(sprintf("-%s days", $nbDays - 1)));
    $cmd = sprintf('git --git-dir="%s/.git" log --no-merges --ignore-all-space --since="%s" --format="%%ci%s%%ce%s%%cn%s%%h%s%%s" --numstat', $gitDir, $from, $separator, $separator, $separator, $separator);
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
            $commit = getCommitFromLine($line, $separator);
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

function getBackwardInfos($commits, $displayPattern, $pattern, $timeUnit, $iteration)
{
    $infos = array();
    $scanIndex = 0;
    $timeIndex = 0;

    for($i = 0; $i < $iteration; $i++)
    {
        $scannedDate = date($pattern, strtotime(sprintf("-%s %s", $i, $timeUnit)));
        $infos[$timeIndex]['displayDate'] = date($displayPattern, strtotime(sprintf("-%s %s", $i, $timeUnit)));
        $infos[$timeIndex]['nb-commits'] = 0;
        $infos[$timeIndex]['nb-files'] = 0;
        while(isset($commits[$scanIndex]) && strpos($commits[$scanIndex]['date'], $scannedDate) === 0)
        {
            $infos[$timeIndex]['nb-commits']++;
            $infos[$timeIndex]['nb-files'] += count($commits[$scanIndex]['files']);
            $scanIndex++;
        }
        $timeIndex++;
    }

    return $infos;
}

function getNoMergedBranchesInfos($gitDir)
{
    $noMerdegBranchesInfos = array();

    $cmd = sprintf('git --git-dir="%s/.git" branch --no-merged | grep -v "*"', $gitDir);
    exec($cmd, $results);

    foreach($results as $branch)
    {
        $branch = trim($branch);
        $infos = array();

        $cmd = sprintf('git --git-dir="%s/.git" shortlog %s..HEAD | grep -c "^[ ]" | sed "s/ //g"', $gitDir, $branch);
        exec($cmd, $infos);

        $cmd = sprintf('git --git-dir="%s/.git" log --numstat --format="%%aD %%ar %%ci%%x09" %s..HEAD | grep -c "^[0-9]" | sed "s/ //g"', $gitDir, $branch);
        exec($cmd, $infos);

        $cmd = sprintf('git --git-dir="%s/.git" shortlog HEAD..%s | grep -c "^[ ]" | sed "s/ //g"', $gitDir, $branch);
        exec($cmd, $infos);

        $cmd = sprintf('git --git-dir="%s/.git" log --numstat --format="%%aD %%ar %%ci%%x09" HEAD..%s | grep -c "^[0-9]" | sed "s/ //g"', $gitDir, $branch);
        exec($cmd, $infos);

        $separator = '°';
        $cmd = sprintf('git --git-dir="%s/.git" log -n 1 --format="%%ci%s%%ce%s%%cn%s%%h%s%%s" %s | sed "s/:[0-9]\{2\} +[0-9]\{4\}//g"', $gitDir, $separator, $separator, $separator, $separator, $branch);
        exec($cmd, $infos);

        if(count($infos) != 5)
        {
            continue;
        }

        $noMerdegBranchesInfos[$branch] = array(
            'currentBranchAheadCommits' => $infos[0],
            'currentBranchAheadFiles' => $infos[1],
            'distantBranchAheadCommits' => $infos[2],
            'distantBranchAheadFiles' => $infos[3],
            'distantBranchAheadLastCommit' => getCommitFromLine($infos[4], $separator)
        );
    }

    return $noMerdegBranchesInfos;
}

function getStats($commits)
{
    $stats = array();
    $nbCommits = 0;
    $nbFiles = 0;

    foreach($commits as $commit)
    {
        if(!isset($stats[$commit['email']]))
        {
            $stats[$commit['email']] = array(
                'name' => $commit['name'],
                'totalCommits' => 0,
                'percentCommits' => 0,
                'totalFiles' => 0,
                'percentFiles' => 0
            );
        }

        $stats[$commit['email']]['name'] = $commit['name'];
        $stats[$commit['email']]['totalCommits'] += 1;
        $stats[$commit['email']]['totalFiles'] += count($commit['files']);
        $nbCommits++;
        $nbFiles += count($commit['files']);
    }

    foreach($stats as $key => $stat)
    {
        $stats[$key]['percentCommits'] = round($stat['totalCommits'] * 100 / $nbCommits);
        $stats[$key]['percentFiles'] = round($stat['totalFiles'] * 100 / $nbFiles);
    }

    return $stats;
}

function getCommitFromLine($line, $separator)
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

function usage()
{
    outputf("Gitboard : simple git dashboard.
-d <project directory> : like --git-dir
-i : number of last days/hours/minutes
-c : number of last commits
-h : this help
-v : version
--no-merged-branch : no merged branches infos
--no-stat : no statistic
");
}

function limitText($str, $limit)
{
    if(mb_strlen($str, 'UTF-8') <= $limit)
    {
        return $str;
    }

    return mb_substr($str, 0, $limit - 3, 'UTF-8' ).'...';
}

function displayValue($value, $padding = 0, $color = null, $onlyIfPositive = false, $onlyIfMatch = null)
{
    $positive = (is_numeric($value) && $value > 0);
    $displayColor = (!is_null($color) && (!$onlyIfPositive || ($onlyIfPositive && $positive)) && (is_null($onlyIfMatch) || (!is_null($onlyIfMatch) && strpos($value, $onlyIfMatch) !== false)));
    $output = "";

    if($displayColor)
    {
        $output .= sprintf("\033[".$color."m");
    }

    if($padding > 0)
    {
        $output .= utf_8_sprintf("%-".$padding."s", $value);
    }
    else
    {
        $output .= sprintf($value);
    }

    if($displayColor)
    {
        $output .= sprintf("\033[0m");
    }

    output($output);
}

function utf_8_sprintf ()
{
    $args = func_get_args();

    if(!extension_loaded('iconv'))
    {
        return call_user_func_array('sprintf', $args);
    }

    for($i = 1; $i < count($args); $i++)
    {
        $args [$i] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $args [$i]);
    }

    return iconv('ISO-8859-1', 'UTF-8', call_user_func_array('sprintf', $args));
}

function pflow_getopt($argv, $def)
{
    $options = array();
    $args = preg_split('/[ |=]+/', implode(' ', $argv));

    foreach($args as $i => $arg)
    {
        $option = trim($arg, '-');

        if (false !== $pos = strpos($def, $option))
        {
            if (substr($def, $pos + 1, 1) == ':' && array_key_exists($i + 1, $args))
            {
                $options[$option] = $args[$i + 1];
            }
            else
            {
                $options[$option] = true;
            }
        }
    }

    return $options;
}
