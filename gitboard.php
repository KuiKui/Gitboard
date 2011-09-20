<?php
//---------------
// Default values
//---------------
$version = '0.1';
$gitDir = $_SERVER["PWD"];
$iteration = 15;
$nbCommits = 10;

//--------
// Options
//--------
$options = getopt('d::i::c::h::v::');
if(isset($options['h'])) {usage(); exit(); }
if(isset($options['v'])) {printf("%s\n", $version); exit(); }
if(isset($options['d']) && $options['d']!==false) {$gitDir = $options['d'];}
if(isset($options['i']) && $options['i']!==false && is_numeric($options['i'])) {$iteration = $options['i'];}
if(isset($options['c']) && $options['c']!==false && is_numeric($options['c'])) {$nbCommits = $options['c'];}

//--------
// Compute
//--------
$currentBranch = getCurrentBranch($gitDir);
$commits = getCommits($iteration, $gitDir);
$lastDaysInfos = getBackwardInfos($commits, 'd/m', 'Y-m-d', 'days', $iteration);
$lastHoursInfos = getBackwardInfos($commits, 'H\h', 'Y-m-d H:', 'hours', $iteration);
$lastMinutesInfos = getBackwardInfos($commits, 'H\hi', 'Y-m-d H:i:', 'minutes', $iteration);

//--------
// Display
//--------
passthru("tput clear");

// Display project infos
printf("\033[0;32mProject\033[0m: %s\n", $gitDir);
printf("\033[0;32mCurrent branch\033[0m: %s\n", $currentBranch);
printf("\033[0;32mCurrent date\033[0m: %s\n", date('d/m/Y H:i:s'));
printf("\n");

// Display time-report
printf("\033[47;30m%-23s\033[0m%-12s\033[47;30m%-23s\033[0m%-12s\033[47;30m%-23s\033[0m\n", "Last $iteration days", "", "Last $iteration hours", "", "Last $iteration minutes");
printf("%-8s %-8s %-16s %-8s %-8s %-16s %-8s %-8s %s\n", "Date", "Commits", "Files", "Hour", "Commits", "Files", "Hour", "Commits", "Files");

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
  printf("\n");
}
printf("\n");

// Display commit-report
printf("\033[47;30m%-113s%-9s\033[0m\n", "Last $nbCommits commits (within the last $iteration days)", "Files");
for($i = 0; $i < $nbCommits; $i++)
{
  if(!isset($commits[$i])) continue;
  displayValue(date('d/m/y H\hi', strtotime($commits[$i]['date'])), 17, "0;33", false, date('d/m/y'));
  displayValue(limitText($commits[$i]['name'], 16), 17);
  displayValue($commits[$i]['hash'], 8);
  displayValue(limitText($commits[$i]['message'], 70), 71, "0;36");
  displayValue(count($commits[$i]['files']), 10);
  printf("\n");
}
printf("\n");

//----------
// Functions
//----------

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
  $cmd = sprintf('git --git-dir="%s/.git" log --no-merges --ignore-all-space --since="%s" --format="%%ci%s%%cn%s%%h%s%%s" --numstat', $gitDir, $from, $separator, $separator, $separator);
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
      
      $elements = explode($separator, $line);
      $commit = array(
        'date' => date('Y-m-d H:i:s', strtotime($elements[0])),
        'name' => $elements[1],
        'hash' => $elements[2],
        'message' => $elements[3],
        'files' => array()
      );
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

function usage()
{
  printf("Gitboard : simple git dashboard.
-d <project directory> : like --git-dir
-i : number of last days/hours/minutes
-c : number of last commits
-h : this help
-v : version
");
}

function limitText($str, $limit)
{
  if(strlen($str) <= $limit)
  {
    return $str;
  }
  return substr($str, 0, $limit - 3).'...';
}

function displayValue($value, $padding = 0, $color = null, $onlyIfPositive = false, $onlyIfMatch = null)
{
  $positive = (is_numeric($value) && $value > 0);
  $displayColor = (!is_null($color) && (!$onlyIfPositive || ($onlyIfPositive && $positive)) && (is_null($onlyIfMatch) || (!is_null($onlyIfMatch) && strpos($value, $onlyIfMatch) !== false)));

  if($displayColor)
  {
    printf("\033[".$color."m");
  }

  if($padding > 0)
  {
    printf("%-".$padding."s", $value);
  }
  else
  {
    printf("%s", $value);
  }

  if($displayColor)
  {
    printf("\033[0m");
  }
}
