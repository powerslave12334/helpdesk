<?php

$img = imagecreatetruecolor(200, 100);
$white = imagecolorallocate($img, 255, 255, 255);
$blue = imagecolorallocate($img, 0, 102, 204);
imagefilledrectangle($img, 0, 0, 200, 100, $white);
imagefilledrectangle($img, 10, 10, 190, 90, $blue);
imagestring($img, 5, 60, 40, 'PRUEBA DRIVE', $white);
imagepng($img, __DIR__.'/public/evidencias/test_evidencia.png');
imagedestroy($img);
echo 'Imagen creada: public/evidencias/test_evidencia.png'.PHP_EOL;
