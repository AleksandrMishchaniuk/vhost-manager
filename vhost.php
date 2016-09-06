<?php

if(count($argv)>2){
  run($argv);
}else{
  echo "You should type parameters\n";
}

function run($args){
  switch ($args[1]) {
    case 'create':
      createVhost($args[2]);
    break;

    case 'remove':
      removeVhost($args[2]);
    break;

    default:
      echo "You typed wrong parameter '$args[1]'\n";
    break;
  }
}

function getSettings($host_name){
  return [
    'host_path' => "/var/www/{$host_name}",
    'conf_path' => "/etc/apache2/sites-available/{$host_name}.conf",
    'index_file_path' => "/var/www/{$host_name}/index.html",
    'log_path' => "/var/log/apache2/{$host_name}",
    'hosts_file_path' => "/etc/hosts",
  ];
}

function createVhost($host_name){
  $host_path = getSettings($host_name)['host_path'];
  $conf_path = getSettings($host_name)['conf_path'];
  $index_file_path = getSettings($host_name)['index_file_path'];
  $log_path = getSettings($host_name)['log_path'];
  $hosts_file_path = getSettings($host_name)['hosts_file_path'];
  createDir($host_path, '0777');
  createDir($log_path, '0777');
  createFile($conf_path, getConfContent($host_name), '0755');
  createFile($index_file_path, getIndexFileContent($host_name), '0777');
  prependToFile($hosts_file_path, getNewHostString($host_name));
  system("a2ensite {$host_name}.conf");
  system("service apache2 restart");
}

function removeVhost($host_name){
  $hosts_file_path = getSettings($host_name)['hosts_file_path'];
  $log_path = getSettings($host_name)['log_path'];
  $conf_path = getSettings($host_name)['conf_path'];
  system("a2dissite {$host_name}.conf");
  deleteLineFromFile($hosts_file_path, $host_name);
  removeDir($log_path);
  removeFile($conf_path);
  system("service apache2 restart");
}

function deleteLineFromFile($path, $host_name){
  $str = file_get_contents($path);
  $lines = explode("\n", $str);
  try{
    $f = fopen($path, 'w');
    foreach ($lines as $line) {
      if(preg_match("~^.*\s{$host_name}$~", $line)) { continue; }
      fwrite($f, $line . "\n");
    }
    updatedMsg($path);
  }catch(Exception $e){
    echo $e->getMessage();
  }finally{
    fclose($f);
  }
}
function removeDir($path){
  if(!is_dir($path)){
    echo "error - '{$path}' is not directory";
    return false;
  }
  try {
    removeDirRecursive($path);
    removedMsg($path);
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

function removeDirRecursive($path){
  $items = scandir($path);
  foreach ($items as $item) {
    if($item != '.' && $item != '..'){
      $item_path = $path . '/' . $item;
      if(is_dir($item_path)){
        removeDirRecursive($item_path);
      }else{
        unlink($item_path);
      }
    }
  }
  rmdir($path);
}

function removeFile($path){
  if(!file_exists($path)){
    doesNotExistMsg($path);
    return false;
  }
  try {
    unlink($path);
    removedMsg($path);
    return true;
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

function createDir($path, $perms){
  if(is_dir($path)){
    existsMsg($path);
    return false;
  }
  if( mkdir($path) ){
    system("chmod {$perms} {$path}");
    createdMsg($path);
    return true;
  }
}
function createFile($path, $content, $perms){
  if(file_exists($path)){
    existsMsg($path);
    return false;
  }
  if(file_put_contents($path, $content)){
    system("chmod {$perms} {$path}");
    createdMsg($path);
    return true;
  }
}
function prependToFile($path, $content){
  try{
    $f = fopen($path, 'r+');
    $content .= fread($f, filesize($path));
    rewind($f);
    fwrite($f, $content);
    updatedMsg($path);
  }catch(Exception $e){
    echo $e->getMessage();
  }finally{
    fclose($f);
  }
}

function createdMsg($str){
  echo "created - $str\n";
}
function existsMsg($str){
  echo "already exists - $str\n";
}
function doesNotExistMsg($str){
  echo "does not exist - $str\n";
}
function updatedMsg($str){
  echo "updated - $str\n";
}
function removedMsg($str){
  echo "removed - $str\n";
}


function getConfContent($host_name){
  return <<<SETTINGS
<VirtualHost *:80>
    ServerAdmin admin@example.com
    ServerName {$host_name}
    ServerAlias www.{$host_name}
    DocumentRoot /var/www/{$host_name}
    <Directory /var/www/{$host_name}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/{$host_name}/error.log
    CustomLog \${APACHE_LOG_DIR}/{$host_name}/access.log combined
</VirtualHost>
SETTINGS;
}

function getIndexFileContent($host_name){
  return "<h1>Hellow!!! This is host: <strong>$host_name</strong></h1>";
}

function getNewHostString($host_name){
  return "127.0.0.1 	$host_name\n";
}
