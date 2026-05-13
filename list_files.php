<?php
header('Content-Type: text/plain');
echo "Root files:\n";
print_r(glob("*"));
echo "\nSistema files:\n";
print_r(glob("sistema/*"));
echo "\nIncludes files:\n";
print_r(glob("includes/*"));
